<?php

declare(strict_types=1);

namespace SkillTest;

use App\Domain\ValueObject\Uuid;
use App\Domain\Barbershop\ValueObject\Slot;

enum OrderStatus: string {
    case Draft = 'draft';
    case Placed = 'placed';
}

final class CleanAggregate
{
    private OrderStatus $status;
    /** @var list<object> */
    private array $recordedEvents = [];

    public function __construct(
        private readonly Uuid $id,
        private readonly Slot $slot,
    ) {
        $this->status = OrderStatus::Draft;
        $this->recordThat(new \stdClass());
    }

    public function place(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new \DomainException('Only draft orders can be placed.');
        }

        $this->status = OrderStatus::Placed;
        $this->recordThat(new \stdClass());
    }

    private function recordThat(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
