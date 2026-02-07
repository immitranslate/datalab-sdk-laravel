<?php

namespace ImmiTranslate\Datalab\Requests;

use Illuminate\Support\Facades\Http;
use ImmiTranslate\Datalab\DTO\ExtractionSchemaResponse;
use ImmiTranslate\Datalab\DTO\ExtractionSchemaResultResponse;
use InvalidArgumentException;
use RuntimeException;

class ExtractionSchemaRequest
{
    /** @var array<string, mixed> */
    protected array $payload = [];

    protected int $pollIntervalSeconds;

    public function __construct(
        protected string $endpoint,
        protected string $apiKey,
        protected int $defaultPollIntervalSeconds = 5,
        ?int $pollIntervalSeconds = null,
    ) {
        $this->pollIntervalSeconds = max(0, $pollIntervalSeconds ?? $this->defaultPollIntervalSeconds);
    }

    /**
     * @param  string  $checkpointId  The checkpoint ID provided from the MarkerResultResponse object
     * @return $this
     */
    public function checkpoint(string $checkpointId): static
    {
        $checkpointId = trim($checkpointId);

        if ($checkpointId === '') {
            throw new InvalidArgumentException('The checkpoint ID cannot be empty.');
        }

        $this->payload['checkpoint_id'] = $checkpointId;

        return $this;
    }

    /**
     * Optional. Overrides the webhook URL configured at the account level for this request only.
     */
    public function webhookUrl(?string $webhookUrl): static
    {
        if ($webhookUrl === null || trim($webhookUrl) === '') {
            unset($this->payload['webhook_url']);

            return $this;
        }

        $this->payload['webhook_url'] = $webhookUrl;

        return $this;
    }

    public function generate(): ExtractionSchemaResultResponse
    {
        return $this->generateSync();
    }

    public function generateSync(): ExtractionSchemaResultResponse
    {
        $submission = $this->generateAsync();

        if (! $submission->isSuccess()) {
            throw new RuntimeException(
                'Unable to start extraction schema generation. Check checkpoint ID and API key configuration.'
            );
        }

        $requestCheckUrl = $submission->requestCheckUrl;

        if ($requestCheckUrl === null || trim($requestCheckUrl) === '') {
            throw new RuntimeException(
                'Extraction schema generation started but no request_check_url was returned.'
            );
        }

        do {
            $result = $this->checkResultByUrl($requestCheckUrl);

            if ($result->isTerminalStatus()) {
                return $result;
            }

            if ($this->pollIntervalSeconds > 0) {
                usleep($this->pollIntervalSeconds * 1_000_000);
            }
        } while (true);
    }

    public function generateAsync(): ExtractionSchemaResponse
    {
        if ($this->apiKey === '') {
            throw new InvalidArgumentException(
                'Datalab API key is not configured. Set datalab-sdk-laravel.api_key.'
            );
        }

        if (! isset($this->payload['checkpoint_id'])) {
            throw new InvalidArgumentException('checkpoint_id is required. Call checkpoint() before generate().');
        }

        return ExtractionSchemaResponse::fromHttpResponse(
            Http::acceptJson()
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                ])
                ->post($this->resolveUrl('marker/extraction/gen_schemas'), $this->payload)
        );
    }

    public function checkResult(string $requestId): ExtractionSchemaResultResponse
    {
        return $this->checkResultByUrl(
            $this->resolveUrl('marker/extraction/gen_schemas/'.rawurlencode($requestId))
        );
    }

    public function checkResultByUrl(string $requestCheckUrl): ExtractionSchemaResultResponse
    {
        return ExtractionSchemaResultResponse::fromHttpResponse(
            Http::acceptJson()
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                ])
                ->get($requestCheckUrl)
        );
    }

    protected function resolveUrl(string $path): string
    {
        return rtrim($this->endpoint, '/').'/'.ltrim($path, '/');
    }
}
