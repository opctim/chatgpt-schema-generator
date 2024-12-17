<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator\Tests\Fixtures\Dto;

class User
{
    /**
     * User ID.
     */
    private int $id;

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
     * List of addresses the user has lived in.
     *
     * @var Address[]
     */
    private array $previousAddresses;
}