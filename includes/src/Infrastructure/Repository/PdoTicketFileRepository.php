<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use ReportaBlu\Domain\Contracts\TicketFileRepositoryInterface;

final class PdoTicketFileRepository extends BasePdoRepository implements TicketFileRepositoryInterface
{
    public function add(int $ticketId, string $originalName, string $filePath, string $mimeType, int $fileSize): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ticket_files (ticket_id, original_name, file_path, mime_type, file_size, uploaded_at)
             VALUES (:ticket_id, :original_name, :file_path, :mime_type, :file_size, NOW())'
        );

        $statement->execute([
            'ticket_id' => $ticketId,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);
    }

    public function listByTicket(int $ticketId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, original_name, file_path, mime_type, file_size, uploaded_at
             FROM ticket_files
             WHERE ticket_id = :ticket_id
             ORDER BY uploaded_at DESC'
        );

        $statement->execute(['ticket_id' => $ticketId]);
        return $statement->fetchAll() ?: [];
    }
}
