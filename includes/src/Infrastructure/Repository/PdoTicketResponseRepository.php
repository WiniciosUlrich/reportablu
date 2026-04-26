<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use ReportaBlu\Domain\Contracts\TicketResponseRepositoryInterface;
use RuntimeException;

final class PdoTicketResponseRepository extends BasePdoRepository implements TicketResponseRepositoryInterface
{
    public function add(int $ticketId, int $authorUserId, string $authorName, string $message): void
    {
        if (!$this->tableExists('ticket_responses')) {
            throw new RuntimeException('Tabela ticket_responses nao encontrada. Execute o sql/schema.sql para atualizar o banco.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO ticket_responses (ticket_id, author_user_id, author_name, message, created_at)
             VALUES (:ticket_id, :author_user_id, :author_name, :message, NOW())'
        );

        $statement->execute([
            'ticket_id' => $ticketId,
            'author_user_id' => $authorUserId,
            'author_name' => $authorName,
            'message' => $message,
        ]);
    }

    public function listByTicket(int $ticketId): array
    {
        if (!$this->tableExists('ticket_responses')) {
            return [];
        }

        $statement = $this->pdo->prepare(
            'SELECT author_name, message, created_at
             FROM ticket_responses
             WHERE ticket_id = :ticket_id
             ORDER BY created_at ASC'
        );

        $statement->execute(['ticket_id' => $ticketId]);
        return $statement->fetchAll() ?: [];
    }
}
