<?php

declare(strict_types=1);

namespace App\Infrastructure\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prevent duplicate bookings: partial unique index on (stylist_id, start_time) for active statuses';
    }

    public function up(Schema $schema): void
    {
        // Partial index: rejected bookings free the slot, so only pending and confirmed rows
        // participate in the uniqueness constraint. SQLite supports WHERE on CREATE UNIQUE INDEX.
        $this->addSql(
            "CREATE UNIQUE INDEX uq_booking_stylist_start_active
                ON barbershop_bookings (stylist_id, start_time)
                WHERE status IN ('pending', 'confirmed')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uq_booking_stylist_start_active');
    }
}
