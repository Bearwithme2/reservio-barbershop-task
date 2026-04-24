<?php

declare(strict_types=1);

namespace SkillTest;

enum JobStatus: string {
    case Pending = 'pending';
    case Done = 'done';
}

final class HelperOneLevel
{
    private JobStatus $status = JobStatus::Pending;
    /** @var list<object> */
    private array $events = [];

    public function complete(): void
    {
        if ($this->status !== JobStatus::Pending) {
            throw new \DomainException('Already done.');
        }

        $this->finalize();
    }

    private function finalize(): void
    {
        $this->status = JobStatus::Done;
        $this->raise(new \stdClass());
    }

    private function raise(object $event): void
    {
        $this->events[] = $event;
    }
}
