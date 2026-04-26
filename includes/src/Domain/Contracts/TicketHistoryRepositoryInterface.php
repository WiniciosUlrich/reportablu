<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface TicketHistoryRepositoryInterface
{
    public function add(int $ticketId, string $status, string $note): void;

    public function listByTicket(int $ticketId): array;
}
