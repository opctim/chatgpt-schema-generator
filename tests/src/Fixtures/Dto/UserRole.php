<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator\Tests\Fixtures\Dto;

enum UserRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';
}
