<?php

declare(strict_types=1);

namespace SkillTest;

enum TicketStatus: string {
    case Open = 'open';
    case Closed = 'closed';
    case Archived = 'archived';
}

final class BareSetters
{
    private TicketStatus $status = TicketStatus::Open;

    public function close(): void
    {
        $this->status = TicketStatus::Closed;
    }

    public function archive(): void
    {
        $this->status = TicketStatus::Archived;
    }

    public function reopen(): void
    {
        $this->status = TicketStatus::Open;
    }
}
