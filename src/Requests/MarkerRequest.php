<?php

namespace ImmiTranslate\Datalab\Requests;

use Illuminate\Support\Facades\Http;
use ImmiTranslate\Datalab\DTO\MarkerResponse;
use ImmiTranslate\Datalab\DTO\MarkerResultResponse;
use ImmiTranslate\Datalab\Enums\DatalabMode;
use ImmiTranslate\Datalab\Enums\DatalabOutput;
use InvalidArgumentException;
use RuntimeException;

class MarkerRequest
{
    /** @var array<string, mixed> */
    protected array $payload = [];

    /** @var array<int, array{name: string, contents: string, filename: string}> */
    protected array $files = [];

    protected int $pollIntervalSeconds;

    public function __construct(
        protected string $endpoint,
        protected string $apiKey,
        protected int $defaultPollIntervalSeconds = 5,
        ?int $pollIntervalSeconds = null,
    ) {
        $this->payload['mode'] = DatalabMode::Fast->value;
        $this->pollIntervalSeconds = max(0, $pollIntervalSeconds ?? $this->defaultPollIntervalSeconds);
    }

    public function fileUrl(?string $fileUrl): static
    {
        return $this->set('file_url', $fileUrl);
    }

    public function mode(DatalabMode $mode): static
    {
        return $this->set('mode', $mode->value);
    }

    public function maxPages(?int $maxPages): static
    {
        return $this->set('max_pages', $maxPages);
    }

    public function pageRange(?string $pageRange): static
    {
        return $this->set('page_range', $pageRange);
    }

    public function paginate(bool $paginate = true): static
    {
        return $this->setBoolean('paginate', $paginate);
    }

    public function addBlockIds(bool $addBlockIds = true): static
    {
        return $this->setBoolean('add_block_ids', $addBlockIds);
    }

    public function disableImageExtraction(bool $disableImageExtraction = true): static
    {
        return $this->setBoolean('disable_image_extraction', $disableImageExtraction);
    }

    public function disableImageCaptions(bool $disableImageCaptions = true): static
    {
        return $this->setBoolean('disable_image_captions', $disableImageCaptions);
    }

    public function outputFormat(DatalabOutput $outputFormat): static
    {
        return $this->set('output_format', $outputFormat->value);
    }

    public function outputFormats(DatalabOutput ...$outputFormats): static
    {
        if ($outputFormats === []) {
            unset($this->payload['output_format']);

            return $this;
        }

        $formats = array_map(
            static fn (DatalabOutput $outputFormat): string => $outputFormat->value,
            $outputFormats
        );

        return $this->set('output_format', implode(',', $formats));
    }

    public function skipCache(bool $skipCache = true): static
    {
        return $this->setBoolean('skip_cache', $skipCache);
    }

    public function saveCheckpoint(bool $saveCheckpoint = true): static
    {
        return $this->setBoolean('save_checkpoint', $saveCheckpoint);
    }

    public function pageSchema(array|string|null $pageSchema): static
    {
        return $this->setJsonOrString('page_schema', $pageSchema);
    }

    public function segmentationSchema(array|string|null $segmentationSchema): static
    {
        return $this->setJsonOrString('segmentation_schema', $segmentationSchema);
    }

    public function additionalConfig(array|string|null $additionalConfig): static
    {
        return $this->setJsonOrString('additional_config', $additionalConfig);
    }

    public function workflowStepDataId(?int $workflowStepDataId): static
    {
        return $this->set('workflowstepdata_id', $workflowStepDataId);
    }

    /**
     * @param  array<int, string>|string|null  $extras
     */
    public function extras(array|string|null $extras): static
    {
        if ($extras === null) {
            unset($this->payload['extras']);

            return $this;
        }

        if (is_array($extras)) {
            return $this->set('extras', implode(',', $extras));
        }

        return $this->set('extras', $extras);
    }

    /**
     * Optional. Overrides the webhook URL configured at the account level for this request only.
     */
    public function webhookUrl(?string $webhookUrl): static
    {
        return $this->set('webhook_url', $webhookUrl);
    }

    public function file(string $path, ?string $filename = null): static
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File path [{$path}] is not readable.");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidArgumentException("Failed to read file [{$path}].");
        }

        $this->files[] = [
            'name' => 'file',
            'contents' => $contents,
            'filename' => $filename ?? basename($path),
        ];

        return $this;
    }

    public function execute(): MarkerResultResponse
    {
        return $this->executeSync();
    }

    public function executeSync(): MarkerResultResponse
    {
        $submission = $this->executeAsync();

        if (! $submission->isSuccess() || $submission->requestId === null) {
            throw new RuntimeException(
                'Unable to start marker request. Check request payload and API key configuration.'
            );
        }

        do {
            $result = $this->checkResult($submission->requestId);

            if ($result->isTerminalStatus()) {
                return $result;
            }

            if ($this->pollIntervalSeconds > 0) {
                usleep($this->pollIntervalSeconds * 1_000_000);
            }
        } while (true);
    }

    public function executeAsync(): MarkerResponse
    {
        if ($this->apiKey === '') {
            throw new InvalidArgumentException(
                'Datalab API key is not configured. Set datalab-sdk-laravel.api_key.'
            );
        }

        $request = Http::asMultipart()
            ->acceptJson()
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
            ]);

        foreach ($this->files as $file) {
            $request = $request->attach(
                $file['name'],
                $file['contents'],
                $file['filename']
            );
        }

        return MarkerResponse::fromHttpResponse(
            $request->post($this->resolveUrl('marker'), $this->payload)
        );
    }

    public function checkResult(string $requestId): MarkerResultResponse
    {
        return MarkerResultResponse::fromHttpResponse(
            Http::acceptJson()
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                ])
                ->get($this->resolveUrl('marker/'.rawurlencode($requestId)))
        );
    }

    protected function resolveUrl(string $path): string
    {
        return rtrim($this->endpoint, '/').'/'.ltrim($path, '/');
    }

    protected function set(string $key, mixed $value): static
    {
        if ($value === null) {
            unset($this->payload[$key]);

            return $this;
        }

        $this->payload[$key] = $value;

        return $this;
    }

    protected function setBoolean(string $key, bool $value): static
    {
        return $this->set($key, $value ? 'true' : 'false');
    }

    protected function setJsonOrString(string $key, array|string|null $value): static
    {
        if ($value === null) {
            unset($this->payload[$key]);

            return $this;
        }

        if (is_array($value)) {
            return $this->set($key, json_encode($value, JSON_THROW_ON_ERROR));
        }

        return $this->set($key, $value);
    }
}
