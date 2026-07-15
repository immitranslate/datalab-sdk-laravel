<?php

namespace ImmiTranslate\Datalab\DTO;

class ValidationDetail
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
