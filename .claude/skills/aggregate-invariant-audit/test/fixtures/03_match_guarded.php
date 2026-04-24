<?php

declare(strict_types=1);

namespace SkillTest;

enum DoorStatus: string {
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}

final class MatchGuarded
{
    private DoorStatus $status = DoorStatus::Closed;

    public function lock(): void
    {
        $this->status = match ($this->status) {
            DoorStatus::Closed => DoorStatus::Locked,
            default => throw new \DomainException("Cannot lock from {$this->status->value}"),
        };
    }
}
