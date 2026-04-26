<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface TicketAssignmentRepositoryInterface
{
    public function assign(int $ticketId, string $department, string $note, int $assignedByUserId): void;

    public function latestByTicket(int $ticketId): ?array;
}
