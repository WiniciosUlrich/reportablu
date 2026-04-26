<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use ReportaBlu\Domain\Contracts\TicketAssignmentRepositoryInterface;
use RuntimeException;

final class PdoTicketAssignmentRepository extends BasePdoRepository implements TicketAssignmentRepositoryInterface
{
    public function assign(int $ticketId, string $department, string $note, int $assignedByUserId): void
    {
        if (!$this->tableExists('ticket_assignments')) {
            throw new RuntimeException('Tabela ticket_assignments nao encontrada. Execute o sql/schema.sql para atualizar o banco.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO ticket_assignments (ticket_id, department, note, assigned_by_user_id, assigned_at)
             VALUES (:ticket_id, :department, :note, :assigned_by_user_id, NOW())'
        );

        $statement->execute([
            'ticket_id' => $ticketId,
            'department' => $department,
            'note' => $note,
            'assigned_by_user_id' => $assignedByUserId,
        ]);
    }

    public function latestByTicket(int $ticketId): ?array
    {
        if (!$this->tableExists('ticket_assignments')) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                ta.department,
                ta.note,
                ta.assigned_at,
                ta.assigned_by_user_id,
                u.nome AS assigned_by_name
             FROM ticket_assignments ta
             INNER JOIN users u ON u.id = ta.assigned_by_user_id
             WHERE ta.ticket_id = :ticket_id
             ORDER BY ta.assigned_at DESC
             LIMIT 1'
        );

        $statement->execute(['ticket_id' => $ticketId]);

        $result = $statement->fetch();
        return $result !== false ? $result : null;
    }
}
