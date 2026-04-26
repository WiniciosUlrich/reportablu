<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface TicketResponseRepositoryInterface
{
    public function add(int $ticketId, int $authorUserId, string $authorName, string $message): void;

    public function listByTicket(int $ticketId): array;
}
