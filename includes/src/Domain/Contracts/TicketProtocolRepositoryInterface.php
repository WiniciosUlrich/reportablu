<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface TicketProtocolRepositoryInterface
{
    public function create(int $ticketId, string $protocolCode): void;

    public function findByTicket(int $ticketId): ?array;
}
