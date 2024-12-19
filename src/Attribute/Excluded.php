<?php
declare(strict_types=1);

namespace Opctim\ChatGpt\SchemaGenerator\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
class Excluded
{

}