<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator\Tests\Fixtures\Dto;

class Address
{
    /**
     * Street name.
     */
    private string $street;

    /**
     * City name.
     */
    private string $city;

    /**
     * Postal code.
     */
    private string $postalCode;
}