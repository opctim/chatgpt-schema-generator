<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator\Result;

use ArrayAccess;

readonly class JsonSchema implements ArrayAccess
{
    public function __construct(
        private string $name,
        private array $schema
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getJson(): string
    {
        return json_encode(
            $this->getSchema(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function __toString(): string
    {
        return $this->getJson();
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->schema[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->schema[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {}

    public function offsetUnset(mixed $offset): void
    {}
}