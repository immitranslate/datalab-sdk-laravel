<?php

namespace ImmiTranslate\Datalab\DTO;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Validator;

class FormFillingResultResponse
{
    /**
     * @param  array<int, string>|null  $fieldsFilled
     * @param  array<int, string>|null  $fieldsNotFound
     * @param  array<string, mixed>  $costBreakdown
     * @param  array<string, mixed>  $versions
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?string $status,
        public readonly ?bool $success,
        public readonly ?string $error,
        public readonly ?string $outputFormat,
        public readonly ?string $outputBase64,
        public readonly ?array $fieldsFilled,
        public readonly ?array $fieldsNotFound,
        public readonly ?float $runtime,
        public readonly ?int $pageCount,
        public readonly array $costBreakdown,
        public readonly array $versions,
        public readonly array $raw,
    ) {}

    public static function fromHttpResponse(Response $response): self
    {
        $raw = $response->json();
        $raw = is_array($raw) ? $raw : [];

        $validated = Validator::make(
            $raw,
            [
                'status' => ['nullable', 'string'],
                'success' => ['nullable', 'boolean'],
                'error' => ['nullable', 'string'],
                'output_format' => ['nullable', 'string'],
                'output_base64' => ['nullable', 'string'],
                'fields_filled' => ['nullable', 'array'],
                'fields_filled.*' => ['string'],
                'fields_not_found' => ['nullable', 'array'],
                'fields_not_found.*' => ['string'],
                'runtime' => ['nullable', 'numeric'],
                'page_count' => ['nullable', 'integer'],
                'cost_breakdown' => ['nullable', 'array'],
                'versions' => ['nullable', 'array'],
            ]
        )->valid();

        $fieldsFilled = is_array($validated['fields_filled'] ?? null)
            ? array_map(static fn (string $value): string => $value, $validated['fields_filled'])
            : null;

        $fieldsNotFound = is_array($validated['fields_not_found'] ?? null)
            ? array_map(static fn (string $value): string => $value, $validated['fields_not_found'])
            : null;

        return new self(
            httpStatus: $response->status(),
            status: $validated['status'] ?? null,
            success: $validated['success'] ?? null,
            error: $validated['error'] ?? null,
            outputFormat: $validated['output_format'] ?? null,
            outputBase64: $validated['output_base64'] ?? null,
            fieldsFilled: $fieldsFilled,
            fieldsNotFound: $fieldsNotFound,
            runtime: is_numeric($validated['runtime'] ?? null) ? (float) $validated['runtime'] : null,
            pageCount: is_numeric($validated['page_count'] ?? null) ? (int) $validated['page_count'] : null,
            costBreakdown: $validated['cost_breakdown'] ?? [],
            versions: $validated['versions'] ?? [],
            raw: $raw,
        );
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isTerminalStatus(): bool
    {
        return in_array($this->status, ['complete', 'failed', 'error', 'cancelled'], true);
    }

    public function isSuccess(): bool
    {
        return $this->isComplete() && $this->success === true;
    }

}
