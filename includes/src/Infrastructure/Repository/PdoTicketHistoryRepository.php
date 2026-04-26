<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use ReportaBlu\Domain\Contracts\TicketHistoryRepositoryInterface;

final class PdoTicketHistoryRepository extends BasePdoRepository implements TicketHistoryRepositoryInterface
{
    public function add(int $ticketId, string $status, string $note): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ticket_status_history (ticket_id, status, note, created_at)
             VALUES (:ticket_id, :status, :note, NOW())'
        );

        $statement->execute([
            'ticket_id' => $ticketId,
            'status' => $status,
            'note' => $note,
        ]);
    }

    public function listByTicket(int $ticketId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT status, note, created_at
             FROM ticket_status_history
             WHERE ticket_id = :ticket_id
             ORDER BY created_at DESC'
        );

        $statement->execute(['ticket_id' => $ticketId]);
        return $statement->fetchAll() ?: [];
    }
}
