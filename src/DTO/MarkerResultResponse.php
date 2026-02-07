<?php

namespace ImmiTranslate\Datalab\DTO;

use Illuminate\Http\Client\Response;

class MarkerResultResponse
{
    /**
     * @param  array<string, mixed>  $chunks
     * @param  array<string, mixed>  $json
     * @param  array<string, mixed>  $segmentationResults
     * @param  array<string, mixed>  $images
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $costBreakdown
     * @param  array<string, mixed>  $versions
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?string $status,
        public readonly ?string $outputFormat,
        public readonly array $chunks,
        public readonly array $json,
        public readonly ?string $markdown,
        public readonly ?string $html,
        public readonly ?string $extractionSchemaJson,
        public readonly array $segmentationResults,
        public readonly array $images,
        public readonly array $metadata,
        public readonly ?bool $success,
        public readonly ?string $error,
        public readonly ?float $parseQualityScore,
        public readonly ?int $pageCount,
        public readonly ?int $totalCost,
        public readonly array $costBreakdown,
        public readonly ?float $runtime,
        public readonly ?string $checkpointId,
        public readonly array $versions,
        public readonly array $raw,
    ) {}

    public static function fromHttpResponse(Response $response): self
    {
        $raw = $response->json();
        $raw = is_array($raw) ? $raw : [];

        return new self(
            httpStatus: $response->status(),
            status: self::stringOrNull($raw['status'] ?? null),
            outputFormat: self::stringOrNull($raw['output_format'] ?? null),
            chunks: self::arrayOrEmpty($raw['chunks'] ?? null),
            json: self::arrayOrEmpty($raw['json'] ?? null),
            markdown: self::stringOrNull($raw['markdown'] ?? null),
            html: self::stringOrNull($raw['html'] ?? null),
            extractionSchemaJson: self::stringOrNull($raw['extraction_schema_json'] ?? null),
            segmentationResults: self::arrayOrEmpty($raw['segmentation_results'] ?? null),
            images: self::arrayOrEmpty($raw['images'] ?? null),
            metadata: self::arrayOrEmpty($raw['metadata'] ?? null),
            success: is_bool($raw['success'] ?? null) ? $raw['success'] : null,
            error: self::stringOrNull($raw['error'] ?? null),
            parseQualityScore: self::floatOrNull($raw['parse_quality_score'] ?? null),
            pageCount: self::intOrNull($raw['page_count'] ?? null),
            totalCost: self::intOrNull($raw['total_cost'] ?? null),
            costBreakdown: self::arrayOrEmpty($raw['cost_breakdown'] ?? null),
            runtime: self::floatOrNull($raw['runtime'] ?? null),
            checkpointId: self::stringOrNull($raw['checkpoint_id'] ?? null),
            versions: self::arrayOrEmpty($raw['versions'] ?? null),
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

    protected static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    protected static function intOrNull(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    protected static function floatOrNull(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function arrayOrEmpty(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
