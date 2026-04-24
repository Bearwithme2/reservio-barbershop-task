<?php

declare(strict_types=1);

namespace SkillTest;

use App\Domain\ValueObject\Uuid;

final class PrimitiveConstructor
{
    public function __construct(
        private readonly Uuid $id,
        private readonly string $name,
        private readonly string $email,
        private readonly \DateTimeImmutable $registeredAt,
    ) {
    }
}
