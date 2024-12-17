<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator\Tests;

use Opctim\ChatGpt\SchemaGenerator\Generator;
use Opctim\ChatGpt\SchemaGenerator\Tests\Fixtures\Dto\User;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class GeneratorTest extends TestCase
{
    private Generator $generator;


    public function __construct()
    {
        parent::__construct();

        $this->generator = new Generator();
    }

    /**
     * @throws ReflectionException
     */
    public function test(): void
    {
        $schema = $this->generator->generateSchema(User::class);

        self::assertJson((string)$schema);

        self::assertIsArray($schema->getSchema());

        self::assertEquals($schema->getName(), $schema['name']);

        self::assertJsonStringEqualsJsonFile(__DIR__ . '/../schema/User.schema.json', $schema->getJson());
    }
}
