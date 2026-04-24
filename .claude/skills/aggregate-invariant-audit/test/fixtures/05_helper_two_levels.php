<?php

declare(strict_types=1);

namespace SkillTest;

enum ShipmentStatus: string {
    case Packed = 'packed';
    case Shipped = 'shipped';
}

final class HelperTwoLevels
{
    private ShipmentStatus $status = ShipmentStatus::Packed;
    /** @var list<object> */
    private array $events = [];

    public function ship(): void
    {
        if ($this->status !== ShipmentStatus::Packed) {
            throw new \DomainException('Not packed.');
        }

        $this->transition();
    }

    private function transition(): void
    {
        $this->commit();
    }

    private function commit(): void
    {
        $this->status = ShipmentStatus::Shipped;
        $this->raise(new \stdClass());
    }

    private function raise(object $event): void
    {
        $this->events[] = $event;
    }
}
