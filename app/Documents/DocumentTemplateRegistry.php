<?php

namespace App\Documents;

use InvalidArgumentException;

final class DocumentTemplateRegistry
{
    public function __construct(
        private readonly string $templatesPath,
        private readonly array $templates,
    ) {
    }

    public function get(string $type): DocumentTemplate
    {
        $definition = $this->templates[$type] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown document type: {$type}");
        }

        return new DocumentTemplate(
            type: $type,
            name: $definition['name'],
            path: $this->templatesPath.DIRECTORY_SEPARATOR.$definition['template'],
        );
    }

    public function all(): array
    {
        return array_map(
            fn (string $type) => $this->get($type),
            array_keys($this->templates),
        );
    }
}
