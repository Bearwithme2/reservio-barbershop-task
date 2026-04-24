<?php

declare(strict_types=1);

namespace SkillTest;

final class BoolStateMarker
{
    private bool $isCancelled = false;
    private ?\DateTimeImmutable $cancelledAt = null;

    public function cancel(): void
    {
        $this->isCancelled = true;
        $this->cancelledAt = new \DateTimeImmutable();
    }
}
