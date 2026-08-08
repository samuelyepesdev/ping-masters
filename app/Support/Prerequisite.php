<?php

namespace App\Support;

use JsonSerializable;

class Prerequisite implements JsonSerializable
{
    private function __construct(
        public readonly bool $satisfied,
        public readonly string $label,
        public readonly string $message,
        public readonly string $createUrl,
        public readonly string $createLabel = 'Crear ahora',
    ) {}

    public static function make(bool $satisfied, string $label, string $message, string $createUrl, string $createLabel = 'Crear ahora'): self
    {
        return new self($satisfied, $label, $message, $createUrl, $createLabel);
    }

    public function jsonSerialize(): array
    {
        return [
            'satisfied' => $this->satisfied,
            'label' => $this->label,
            'message' => $this->message,
            'createUrl' => $this->createUrl,
            'createLabel' => $this->createLabel,
        ];
    }
}
