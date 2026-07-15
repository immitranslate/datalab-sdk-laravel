<?php

namespace ImmiTranslate\Datalab\DTO;

/**
 * @deprecated Datalab is deprecating the Marker API. It is being replaced by the Convert API.
 */
class MarkerValidationDetail
{
    /**
     * @param  array<int, string>  $loc
     */
    public function __construct(
        public readonly array $loc,
        public readonly string $msg,
        public readonly string $type,
    ) {}

    /**
     * @param  array<string, mixed>  $detail
     */
    public static function fromArray(array $detail): self
    {
        $loc = [];

        foreach (($detail['loc'] ?? []) as $segment) {
            $loc[] = (string) $segment;
        }

        return new self(
            loc: $loc,
            msg: (string) ($detail['msg'] ?? ''),
            type: (string) ($detail['type'] ?? ''),
        );
    }
}
