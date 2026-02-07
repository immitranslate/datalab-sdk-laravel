<?php

namespace ImmiTranslate\Datalab\DTO;

use Illuminate\Http\Client\Response;

class ExtractionSchemaResultResponse
{
    /**
     * @param  array<string, mixed>|null  $suggestions
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?bool $success,
        public readonly ?string $error,
        public readonly ?array $suggestions,
        public readonly ?string $status,
        public readonly ?int $pageCount,
        public readonly ?int $totalCost,
        public readonly array $raw,
    ) {}

    public static function fromHttpResponse(Response $response): self
    {
        $raw = $response->json();
        $raw = is_array($raw) ? $raw : [];

        $suggestions = $raw['suggestions'] ?? null;

        if (! is_array($suggestions) && $suggestions !== null) {
            $suggestions = null;
        }

        return new self(
            httpStatus: $response->status(),
            success: is_bool($raw['success'] ?? null) ? $raw['success'] : null,
            error: self::stringOrNull($raw['error'] ?? null),
            suggestions: $suggestions,
            status: self::stringOrNull($raw['status'] ?? null),
            pageCount: self::intOrNull($raw['page_count'] ?? null),
            totalCost: self::intOrNull($raw['total_cost'] ?? null),
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
}
