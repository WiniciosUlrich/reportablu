<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use ReportaBlu\Domain\Contracts\TicketProtocolRepositoryInterface;
use RuntimeException;

final class PdoTicketProtocolRepository extends BasePdoRepository implements TicketProtocolRepositoryInterface
{
    public function create(int $ticketId, string $protocolCode): void
    {
        if (!$this->tableExists('ticket_protocols')) {
            throw new RuntimeException('Tabela ticket_protocols nao encontrada. Execute o sql/schema.sql para atualizar o banco.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO ticket_protocols (ticket_id, protocol_code, created_at)
             VALUES (:ticket_id, :protocol_code, NOW())'
        );

        $statement->execute([
            'ticket_id' => $ticketId,
            'protocol_code' => $protocolCode,
        ]);
    }

    public function findByTicket(int $ticketId): ?array
    {
        if (!$this->tableExists('ticket_protocols')) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT protocol_code, created_at
             FROM ticket_protocols
             WHERE ticket_id = :ticket_id
             LIMIT 1'
        );

        $statement->execute(['ticket_id' => $ticketId]);

        $result = $statement->fetch();
        return $result !== false ? $result : null;
    }
}
