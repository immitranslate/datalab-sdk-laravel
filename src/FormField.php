<?php

namespace ImmiTranslate\Datalab;

class FormField
{
    public function __construct(
        public readonly string $fieldKey,
        public readonly string $description,
        public readonly mixed $value = null,
    ) {}

    /**
     * @return array{value: mixed, description: string}
     */
    public function toFieldDataEntry(): array
    {
        return [
            'value' => $this->value,
            'description' => $this->description,
        ];
    }
}
