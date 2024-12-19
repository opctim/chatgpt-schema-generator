# OpenAI ChatGPT JSON Schema Generator

[![Latest Stable Version](http://poser.pugx.org/opctim/chatgpt-schema-generator/v)](https://packagist.org/packages/opctim/chatgpt-schema-generator) [![Total Downloads](http://poser.pugx.org/opctim/chatgpt-schema-generator/downloads)](https://packagist.org/packages/opctim/chatgpt-schema-generator) [![Latest Unstable Version](http://poser.pugx.org/opctim/chatgpt-schema-generator/v/unstable)](https://packagist.org/packages/opctim/chatgpt-schema-generator) [![License](http://poser.pugx.org/opctim/chatgpt-schema-generator/license)](https://packagist.org/packages/opctim/chatgpt-schema-generator) [![PHP Version Require](http://poser.pugx.org/opctim/chatgpt-schema-generator/require/php)](https://packagist.org/packages/opctim/chatgpt-schema-generator)

Easily generate JSON Schemas for OpenAI ChatGPT prompts using your PHP DTOs!

This package allows you to dynamically create JSON Schemas based on your DTO (Data Transfer Object) classes. 
These schemas can be passed to OpenAI’s ChatGPT API to ensure consistent, structured, and strongly-typed JSON responses. 
Ideal for developers who want precise control over ChatGPT outputs in their applications.

## Key Features

-	Seamless Schema Generation: Automatically generate JSON Schemas from your PHP DTO classes.
-	Enum Support: Define strict enums in your DTO, and ChatGPT will adhere to your choices.
-	Integration Ready: Easily pass the generated schemas to OpenAI APIs for structured responses.
-	Serialization Support: Deserialize the JSON response back into your DTO with ease.
-	Clean and Reusable Code: Write less boilerplate and focus on building smarter applications.

## Requirements

- PHP >= 8.3

## Installation

```shell
composer require opctim/chatgpt-schema-generator
```

## Usage

```php
<?php

use Opctim\ChatGpt\SchemaGenerator\Generator;
use Opctim\ChatGpt\SchemaGenerator\Tests\Fixtures\Dto\User;

// Generate the schema based on your DTO
$schema = $this->generator->generateSchema(User::class);

// Specify the schema (you can play with this here: https://platform.openai.com/playground/chat?models=gpt-4o)
$responseFormat = [
    'type' => 'json_schema',
    'json_schema' => $schema->getSchema()
];

$prompt = 'Generate an example user and return based on the input schema with the name ' . $schema->getName();
$model = 'gpt-4o';
$temperature = 0.5;

$answer = $myChatClient->chat($model, $temperature, $prompt, $responseFormat);

// Deserialize from the response :)
$user = $serializer->deserialize($answer, User::class, 'json');

// Done, ready to use your example user object!
```

## Excluding Properties & Classes

You can either replace properties:

```php
<?php
declare(strict_types=1);

use Opctim\ChatGpt\SchemaGenerator\Attribute\Excluded;

class User
{
    #[Excluded]
    private int $id;
    
    /**
     * This will be ignored too!
     * 
     * @internal 
     */
    private int $someNumber;
    
    // [...]
}
```

Or the whole class:

```php
<?php
declare(strict_types=1);

use Opctim\ChatGpt\SchemaGenerator\Attribute\Excluded;

#[Excluded]
class User
{
    // [...]
}
```

### Union Types

Union types will be returned as oneOf in the JSON schema.

If your class has a property that looks like this:

```php
<?php
declare(strict_types=1);

use Opctim\ChatGpt\SchemaGenerator\Attribute\Excluded;

class User
{
    private Address|Account $addressOrAccount;
}
```

The result will be:

```json
{
  "name": "User",
  "strict": false,
  "schema": {
    "type": "object",
    "properties": {
      "addressOrAccount": {
        "oneOf": [
          {
            "type": "object",
            "properties": {
              "street": {
                "description": "Street name.",
                "type": "string"
              },
              "city": {
                "description": "City name.",
                "type": "string"
              },
              "postalCode": {
                "description": "Postal code.",
                "type": "string"
              }
            },
            "required": [
              "street",
              "city",
              "postalCode"
            ]
          },
          {
            "type": "object",
            "properties": {
              "bankName": {
                "description": "The name of the bank.",
                "type": "string"
              }
            },
            "required": [
              "bankName"
            ]
          }
        ]
      }
    },
    "required": [
      "addressOrAccount"
    ]
  }
}
```

### Schema output example
```php
<?php

use Opctim\ChatGpt\SchemaGenerator\Generator;
use Opctim\ChatGpt\SchemaGenerator\Tests\Fixtures\Dto\User;

$schema = $this->generator->generateSchema(User::class);

$schema->getName(); // Returns the name of the schema, to be referenced in your custom ChatGPT prompt

$schema->getSchema(); // Returns the schema as array
$schema['name']; // The schema can also be accessed as an array

$schema->getJson(); // Returns the same as (string)$schema

echo (string)$schema;
```

**Result**

```json
{
  "name": "User",
  "strict": false,
  "schema": {
    "type": "object",
    "properties": {
      "role": {
        "type": "string",
        "enum": [
          "admin",
          "editor",
          "viewer"
        ],
        "description": "User role."
      },
      "address": {
        "type": "object",
        "properties": {
          "street": {
            "description": "Street name.",
            "type": "string"
          },
          "city": {
            "description": "City name.",
            "type": "string"
          },
          "postalCode": {
            "description": "Postal code.",
            "type": "string"
          }
        },
        "required": [
          "street",
          "city",
          "postalCode"
        ]
      },
      "tags": {
        "description": "List of tags for the user.",
        "type": "array",
        "items": {
          "type": "string"
        }
      },
      "previousAddresses": {
        "description": "List of addresses the user has lived in.",
        "type": "array",
        "items": {
          "type": "object",
          "properties": {
            "street": {
              "description": "Street name.",
              "type": "string"
            },
            "city": {
              "description": "City name.",
              "type": "string"
            },
            "postalCode": {
              "description": "Postal code.",
              "type": "string"
            }
          },
          "required": [
            "street",
            "city",
            "postalCode"
          ]
        }
      },
      "addressOrAccount": {
        "oneOf": [
          {
            "type": "object",
            "properties": {
              "street": {
                "description": "Street name.",
                "type": "string"
              },
              "city": {
                "description": "City name.",
                "type": "string"
              },
              "postalCode": {
                "description": "Postal code.",
                "type": "string"
              }
            },
            "required": [
              "street",
              "city",
              "postalCode"
            ]
          },
          {
            "type": "object",
            "properties": {
              "bankName": {
                "description": "The name of the bank.",
                "type": "string"
              }
            },
            "required": [
              "bankName"
            ]
          }
        ]
      }
    },
    "required": [
      "role",
      "address",
      "tags",
      "previousAddresses",
      "addressOrAccount"
    ]
  }
}
```

## Tests

Tests are located inside the `tests/` folder and can be run with `vendor/bin/phpunit`:

```shell
composer install

vendor/bin/phpunit
```
