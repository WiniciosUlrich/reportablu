<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

// ISP: contrato de escrita isolado para evitar interfaces inchadas.
interface TicketWriteRepositoryInterface
{
    public function create(array $payload): int;

    public function findStatusMetadata(int $ticketId): ?array;

    public function updateStatus(int $ticketId, string $status, ?string $resolvedAt): void;
}
