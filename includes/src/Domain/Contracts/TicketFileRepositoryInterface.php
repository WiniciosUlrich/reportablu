<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface TicketFileRepositoryInterface
{
    public function add(int $ticketId, string $originalName, string $filePath, string $mimeType, int $fileSize): void;

    public function listByTicket(int $ticketId): array;
}
