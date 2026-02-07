<?php

namespace ImmiTranslate\Datalab\Requests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use ImmiTranslate\Datalab\DTO\FormFillingResponse;
use ImmiTranslate\Datalab\DTO\FormFillingResultResponse;
use ImmiTranslate\Datalab\FormField;
use InvalidArgumentException;
use RuntimeException;

class FormFillingRequest
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
        $this->payload['confidence_threshold'] = '0.5';
        $this->payload['skip_cache'] = 'false';
        $this->pollIntervalSeconds = max(0, $pollIntervalSeconds ?? $this->defaultPollIntervalSeconds);
    }

    /**
     * @param  array<int, FormField>  $fields
     */
    public function fields(array $fields): static
    {
        Validator::make(
            ['fields' => $fields],
            ['fields' => ['required', 'array', 'min:1']]
        )->validate();

        $fieldData = [];
        $fieldMeta = [];

        foreach ($fields as $field) {
            if (! $field instanceof FormField) {
                throw new InvalidArgumentException(
                    'All items passed to fields() must be instances of '.FormField::class
                );
            }

            $fieldData[$field->fieldKey] = $field->toFieldDataEntry();
            $fieldMeta[] = [
                'field_key' => $field->fieldKey,
                'description' => $field->description,
            ];
        }

        Validator::make(
            ['fields' => $fieldMeta],
            [
                'fields.*.field_key' => ['required', 'string'],
                'fields.*.description' => ['required', 'string'],
            ]
        )->validate();

        $this->payload['field_data'] = json_encode($fieldData, JSON_THROW_ON_ERROR);

        return $this;
    }

    /**
     * @param  array<string, array{value: mixed, description: string}>|string  $fieldData
     */
    public function fieldData(array|string $fieldData): static
    {
        if (is_array($fieldData)) {
            Validator::make(
                ['field_data' => $fieldData],
                [
                    'field_data' => ['required', 'array', 'min:1'],
                    'field_data.*.description' => ['required', 'string'],
                ]
            )->validate();
        } else {
            Validator::make(
                ['field_data' => $fieldData],
                ['field_data' => ['required', 'string']]
            )->validate();
        }

        $this->payload['field_data'] = is_array($fieldData)
            ? json_encode($fieldData, JSON_THROW_ON_ERROR)
            : $fieldData;

        return $this;
    }

    public function fileUrl(?string $fileUrl): static
    {
        return $this->set('file_url', $fileUrl);
    }

    public function context(?string $context): static
    {
        return $this->set('context', $context);
    }

    public function confidenceThreshold(float $confidenceThreshold): static
    {
        Validator::make(
            ['confidence_threshold' => $confidenceThreshold],
            ['confidence_threshold' => ['numeric', 'between:0,1']]
        )->validate();

        return $this->set('confidence_threshold', (string) $confidenceThreshold);
    }

    public function pageRange(?string $pageRange): static
    {
        return $this->set('page_range', $pageRange);
    }

    public function skipCache(bool $skipCache = true): static
    {
        return $this->set('skip_cache', $skipCache ? 'true' : 'false');
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

    public function execute(): FormFillingResultResponse
    {
        return $this->executeSync();
    }

    public function executeSync(): FormFillingResultResponse
    {
        $submission = $this->executeAsync();

        if (! $submission->isSuccess()) {
            throw new RuntimeException(
                'Unable to start form filling request. Check field_data and API key configuration.'
            );
        }

        $requestCheckUrl = $submission->requestCheckUrl;

        if ($requestCheckUrl === null || trim($requestCheckUrl) === '') {
            throw new RuntimeException(
                'Form filling started but no request_check_url was returned.'
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

    public function executeAsync(): FormFillingResponse
    {
        Validator::make(
            [
                'api_key' => $this->apiKey,
                'field_data' => $this->payload['field_data'] ?? null,
            ],
            [
                'api_key' => ['required', 'string'],
                'field_data' => ['required', 'string'],
            ],
            [
                'api_key.required' => 'Datalab API key is not configured. Set datalab-sdk-laravel.api_key.',
                'field_data.required' => 'field_data is required. Call fields() or fieldData() before execute().',
            ]
        )->validate();

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

        return FormFillingResponse::fromHttpResponse(
            $request->post($this->resolveUrl('fill'), $this->payload)
        );
    }

    public function checkResult(string $requestId): FormFillingResultResponse
    {
        return $this->checkResultByUrl(
            $this->resolveUrl('fill/'.rawurlencode($requestId))
        );
    }

    public function checkResultByUrl(string $requestCheckUrl): FormFillingResultResponse
    {
        return FormFillingResultResponse::fromHttpResponse(
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

    protected function set(string $key, mixed $value): static
    {
        if ($value === null) {
            unset($this->payload[$key]);

            return $this;
        }

        $this->payload[$key] = $value;

        return $this;
    }
}
