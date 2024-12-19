<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator;

use Opctim\ChatGpt\SchemaGenerator\Attribute\Excluded;
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
use Symfony\Component\PropertyInfo\Type;

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
    public function generateSchema(string $className, bool $strict = false): JsonSchema
    {
        $reflectionClass = new ReflectionClass($className);

        $schema = [
            'name' => $reflectionClass->getShortName(),
            'strict' => $strict,
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

            $propertyTypes = $this->getPropertyTypes($className, $propertyName);
            $propertyDescription = $this->getPropertyDescription($property);

            // No valid types on property -> skip
            if (empty($propertyTypes)) {
                continue;
            }

            // Skip property if @internal PHPDoc tag or #[Excluded] attribute is set
            if ($this->isPropertyExcluded($property)) {
                continue;
            }

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
    protected function getPropertyTypes(string $className, string $propertyName): array
    {
        return array_filter(
            $this->propertyInfoExtractor->getTypes($className, $propertyName),
            function(Type $type) {
                if (
                    $type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT
                    && $type->getClassName()
                ) {
                    $reflectionClass = new ReflectionClass($type->getClassName());

                    if ($this->isClassExcluded($reflectionClass)) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * @throws ReflectionException
     */
    protected function mapTypesToSchema(?array $types, ?string $description): array
    {
        $types = array_map(
            fn(Type $type) => $this->mapTypeToSchema($type, $description),
            $types
        );

        // Only one type for the property, so returning it
        if (count($types) === 1) {
            return $types[0];
        }

        // Multiple types, return oneOf with all type schemes
        return [
            'oneOf' => $types
        ];
    }

    /**
     * @throws ReflectionException
     */
    protected function mapTypeToSchema(Type $type, ?string $description): array
    {
        $schema = [];

        if ($description) {
            $schema['description'] = $description;
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_STRING:
                $schema['type'] = 'string';
            break;

            case Type::BUILTIN_TYPE_INT:
                $schema['type'] = 'integer';
            break;

            case Type::BUILTIN_TYPE_FLOAT:
                $schema['type'] = 'number';
            break;

            case Type::BUILTIN_TYPE_BOOL:
                $schema['type'] = 'boolean';
            break;

            case Type::BUILTIN_TYPE_ARRAY:
                $schema['type'] = 'array';
                $valueTypes = $type->getCollectionValueTypes();

                if ($valueTypes) {
                    $schema['items'] = $this->mapTypesToSchema($valueTypes, null);
                }
            break;

            case Type::BUILTIN_TYPE_OBJECT:
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

    protected function isClassExcluded(ReflectionClass $class): bool
    {
        // Skip class if Excluded attribute is set
        if (!empty($class->getAttributes(Excluded::class))) {
            return true;
        }

        return false;
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

    protected function isPropertyExcluded(ReflectionProperty $property): bool
    {
        // Skip property if Excluded attribute is set
        if (!empty($property->getAttributes(Excluded::class))) {
            return true;
        }

        $docComment = $property->getDocComment();

        if ($docComment) {
            $docBlock = $this->docBlockFactory->create($docComment);

            // Skip property if @internal tag is set
            if ($docBlock->hasTag('internal')) {
                return true;
            }
        }

        return false;
    }

    protected function isRequired(ReflectionProperty $property): bool
    {
        return !$property->getType()?->allowsNull();
    }
}
