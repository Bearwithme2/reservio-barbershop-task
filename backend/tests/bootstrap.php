<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Doctrine\Type\UuidType;
use Doctrine\DBAL\Types\Type;

if (!Type::hasType(UuidType::NAME)) {
    Type::addType(UuidType::NAME, UuidType::class);
}
