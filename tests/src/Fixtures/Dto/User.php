<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator\Tests\Fixtures\Dto;

use Opctim\ChatGpt\SchemaGenerator\Attribute\Excluded;

class User
{
    /**
     * User ID. Should be excluded from the JSON schema because of the @internal PHPDoc
     *
     * @internal
     */
    private int $id;

    /**
     * Should be excluded from the JSON schema because of the Excluded attribute
     */
    #[Excluded]
    private int $excluded;

    /**
     * User role.
     */
    private UserRole $role;

    /**
     * Address of the user.
     */
    private Address $address;

    /**
     * List of tags for the user.
     *
     * @var string[]
     */
    private array $tags;

    /**
     * An object that should be excluded because the Excluded attribute is set on it.
     */
    private ExcludedClass $excludedClass;

    /**
     * List of addresses the user has lived in.
     *
     * @var Address[]
     */
    private array $previousAddresses;

    /**
     * Either an address or an account. Should return oneOf.
     */
    private Address|Account $addressOrAccount;
}