<?php

namespace ImmiTranslate\Datalab\DTO;

use Illuminate\Http\Client\Response;

class MarkerResponse
{
    /**
     * @param  array<string, mixed>  $versions
     * @param  array<int, MarkerValidationDetail>  $detail
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly int $status,
        public readonly ?string $requestId,
        public readonly ?string $requestCheckUrl,
        public readonly ?bool $success,
        public readonly ?string $error,
        public readonly array $versions,
        public readonly array $detail,
        public readonly array $raw,
    ) {}

    public static function fromHttpResponse(Response $response): self
    {
        $raw = $response->json();
        $raw = is_array($raw) ? $raw : [];

        $detail = [];

        foreach (($raw['detail'] ?? []) as $item) {
            if (is_array($item)) {
                $detail[] = MarkerValidationDetail::fromArray($item);
            }
        }

        $versions = $raw['versions'] ?? [];

        if (! is_array($versions)) {
            $versions = [];
        }

        return new self(
            status: $response->status(),
            requestId: self::stringOrNull($raw['request_id'] ?? null),
            requestCheckUrl: self::stringOrNull($raw['request_check_url'] ?? null),
            success: is_bool($raw['success'] ?? null) ? $raw['success'] : null,
            error: self::stringOrNull($raw['error'] ?? null),
            versions: $versions,
            detail: $detail,
            raw: $raw,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status >= 200
            && $this->status < 300
            && $this->success === true;
    }

    public function isValidationError(): bool
    {
        return $this->status === 422 && $this->detail !== [];
    }

    protected static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
