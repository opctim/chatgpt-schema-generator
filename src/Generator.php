<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator;

use Opctim\ChatGpt\SchemaGenerator\Result\JsonSchema;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use ReflectionClass;
use ReflectionEnum;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class Generator
{
    private PropertyInfoExtractor $propertyInfoExtractor;

    private DocBlockFactoryInterface $docBlockFactory;


    public function __construct()
    {
        $reflectionExtractor = new ReflectionExtractor();
        $phpDocExtractor = new PhpDocExtractor();

        $this->propertyInfoExtractor = new PropertyInfoExtractor(
            [ $phpDocExtractor, $reflectionExtractor ],
            [ $phpDocExtractor, $reflectionExtractor ],
            [ $phpDocExtractor, $reflectionExtractor ]
        );

        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /**
     * @throws ReflectionException
     */
    public function generateSchema(string $className): JsonSchema
    {
        $reflectionClass = new ReflectionClass($className);

        $schema = [
            'name' => $reflectionClass->getShortName(),
            'strict' => false,
            'schema' => $this->mapObject($className)
        ];

        return new JsonSchema(
            $reflectionClass->getShortName(),
            $schema
        );
    }

    /**
     * @throws ReflectionException
     */
    protected function mapObject(string $className): array
    {
        $reflectionClass = new ReflectionClass($className);

        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();

            $propertyTypes = $this->propertyInfoExtractor->getTypes($className, $propertyName);
            $propertyDescription = $this->getPropertyDescription($property);

            $schema['properties'][$propertyName] = $this->mapTypesToSchema($propertyTypes, $propertyDescription);

            if ($this->isRequired($property)) {
                $schema['required'][] = $propertyName;
            }
        }

        return $schema;
    }

    /**
     * @throws ReflectionException
     */
    protected function mapTypesToSchema(?array $types, ?string $description): array
    {
        $schema = [];

        if ($description) {
            $schema['description'] = $description;
        }

        if ($types) {
            foreach ($types as $type) {
                switch ($type->getBuiltinType()) {
                    case 'string':
                        $schema['type'] = 'string';
                    break;

                    case 'int':
                        $schema['type'] = 'integer';
                    break;

                    case 'float':
                        $schema['type'] = 'number';
                    break;

                    case 'bool':
                        $schema['type'] = 'boolean';
                    break;

                    case 'array':
                        $schema['type'] = 'array';
                        $valueTypes = $type->getCollectionValueTypes();

                        if ($valueTypes) {
                            $schema['items'] = $this->mapTypesToSchema($valueTypes, null);
                        } else {
                            $schema['items'] = [ 'type' => 'string' ]; // Default for arrays
                        }
                    break;

                    case 'object':
                        $className = $type->getClassName();

                        if (
                            $className
                            && enum_exists($className)
                        ) {
                            // Enum Handling
                            $schema = $this->mapEnumToSchema($className, $description);
                        } elseif ($className) {
                            // Nested Class
                            $schema = $this->mapObject($className);
                        } else {
                            $schema['type'] = 'object';
                        }
                    break;
                }
            }
        }

        return $schema;
    }

    /**
     * @throws ReflectionException
     */
    protected function mapEnumToSchema(string $enumClass, ?string $description): array
    {
        $reflectionEnum = new ReflectionEnum($enumClass);
        $cases = $reflectionEnum->getCases();

        $result = [
            'type' => 'string',
            'enum' => array_map(fn($case) => $case->getValue(), $cases),
        ];

        if ($description) {
            $result['description'] = $description;
        }

        return $result;
    }

    protected function getPropertyDescription(ReflectionProperty $property): ?string
    {
        $docComment = $property->getDocComment();

        if ($docComment) {
            $docBlock = $this->docBlockFactory->create($docComment);

            return $docBlock->getSummary();
        }

        return null;
    }

    protected function isRequired(ReflectionProperty $property): bool
    {
        return !$property->getType()?->allowsNull();
    }
}
