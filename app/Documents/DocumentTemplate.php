<?php

namespace App\Documents;

final readonly class DocumentTemplate
{
    public function __construct(
        public string $type,
        public string $name,
        public string $path,
    ) {
    }
}
