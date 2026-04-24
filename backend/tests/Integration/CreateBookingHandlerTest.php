<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Barbershop\Command\CreateBooking\CreateBookingCommand;
use App\Application\Barbershop\Command\CreateBooking\CreateBookingCommandHandler;
use App\Domain\Barbershop\Entity\Business;
use App\Domain\Barbershop\Entity\Service;
use App\Domain\Barbershop\Entity\Stylist;
use App\Domain\Barbershop\Exception\SlotAlreadyTakenException;
use App\Infrastructure\Barbershop\Repository\DoctrineBookingRepository;
use App\Infrastructure\ValueObject\Uuid;
use App\Infrastructure\ValueObject\UuidFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class CreateBookingHandlerTest extends TestCase
{
    private CreateBookingCommandHandler $handler;
    private EntityManager $em;

    private const STYLIST_ID = 'bbbbbbbb-bbbb-bbbb-bbbb-aaaaaaaaaaaa';
    private const SERVICE_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
    private const START_TIME = '2099-06-01 10:00:00';

    protected function setUp(): void
    {
        $config = ORMSetup::createXMLMetadataConfiguration(
            paths: [__DIR__ . '/../../config/doctrine'],
            isDevMode: true,
        );

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $this->em = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        // Apply the partial unique index from the migration
        $this->em->getConnection()->executeStatement(
            "CREATE UNIQUE INDEX uq_booking_stylist_start_active
                ON barbershop_bookings (stylist_id, start_time)
                WHERE status IN ('pending', 'confirmed')"
        );

        $business = new Business(Uuid::fromString('11111111-1111-1111-1111-111111111111'), "Test Shop", 'test-shop');
        $service  = new Service(Uuid::fromString(self::SERVICE_ID), 'Haircut', 30, 350.0, 'CZK');
        $stylist  = new Stylist(Uuid::fromString(self::STYLIST_ID), 'Alice');
        $business->addService($service);
        $business->addStylist($stylist);
        $this->em->persist($business);
        $this->em->flush();

        $this->handler = new CreateBookingCommandHandler(
            new DoctrineBookingRepository($this->em),
            $this->em,
            new UuidFactory(),
        );
    }

    public function testDuplicateSubmitThrowsSlotAlreadyTaken(): void
    {
        $command = new CreateBookingCommand(
            stylistId:       self::STYLIST_ID,
            serviceId:       self::SERVICE_ID,
            startTime:       self::START_TIME,
            customerName:    'John Doe',
            customerContact: 'john@example.com',
        );

        // First submission succeeds
        $this->handler->handle($command);

        // Second identical submission must be rejected
        $this->expectException(SlotAlreadyTakenException::class);
        $this->handler->handle($command);
    }

    public function testFirstBookingPersistsExactlyOneRow(): void
    {
        $command = new CreateBookingCommand(
            stylistId:       self::STYLIST_ID,
            serviceId:       self::SERVICE_ID,
            startTime:       self::START_TIME,
            customerName:    'John Doe',
            customerContact: 'john@example.com',
        );

        $this->handler->handle($command);

        $count = $this->em->getConnection()
            ->fetchOne("SELECT COUNT(*) FROM barbershop_bookings");

        self::assertSame('1', (string) $count);
    }
}
