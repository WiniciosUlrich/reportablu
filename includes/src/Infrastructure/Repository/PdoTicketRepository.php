<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Repository;

use PDO;
use ReportaBlu\Domain\Contracts\TicketReadRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketWriteRepositoryInterface;
use ReportaBlu\Domain\TicketStatus;

// Repository concreto de tickets.
// Encapsula SQL para a aplicacao depender de contratos, nao de queries.
final class PdoTicketRepository extends BasePdoRepository implements TicketReadRepositoryInterface, TicketWriteRepositoryInterface
{
    public function fetchPublicSolved(array $filters, int $limit = 12): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = (int) ($filters['category_id'] ?? 0);

        // Compatibilidade: permite evoluir schema sem quebrar leitura legada.
        $hasProtocolTable = $this->tableExists('ticket_protocols');
        $protocolSelect = $hasProtocolTable ? 'tp.protocol_code' : 'NULL AS protocol_code';
        $protocolJoin = $hasProtocolTable ? 'LEFT JOIN ticket_protocols tp ON tp.ticket_id = t.id' : '';

        $sql = "SELECT
                    t.id,
                    t.titulo,
                    t.descricao,
                    t.localizacao,
                    t.resolved_at,
                    c.nome AS categoria,
                    {$protocolSelect}
                FROM tickets t
                INNER JOIN categories c ON c.id = t.category_id
                {$protocolJoin}
                WHERE t.status = :status";

        $params = [
            'status' => TicketStatus::SOLVED,
        ];

        if ($search !== '') {
            $sql .= ' AND (t.titulo LIKE :search_title OR t.descricao LIKE :search_description OR t.localizacao LIKE :search_location)';
            $searchValue = '%' . $search . '%';
            $params['search_title'] = $searchValue;
            $params['search_description'] = $searchValue;
            $params['search_location'] = $searchValue;
        }

        if ($categoryId > 0) {
            $sql .= ' AND t.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $sql .= ' ORDER BY t.resolved_at DESC, t.updated_at DESC LIMIT :limit';

        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll() ?: [];
    }

    public function fetchDashboardTickets(array $filters, ?int $userId, bool $isAdmin): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $hasProtocolTable = $this->tableExists('ticket_protocols');
        $protocolSelect = $hasProtocolTable ? 'tp.protocol_code' : 'NULL AS protocol_code';
        $protocolJoin = $hasProtocolTable ? 'LEFT JOIN ticket_protocols tp ON tp.ticket_id = t.id' : '';

        $where = [];
        $params = [];

        // Regra de acesso por perfil aplicada no repositorio de consulta.
        if (!$isAdmin) {
            $where[] = 't.user_id = :user_id';
            $params['user_id'] = (int) $userId;
        }

        if ($search !== '') {
            $where[] = '(t.titulo LIKE :search_title OR t.descricao LIKE :search_description OR t.localizacao LIKE :search_location)';
            $searchValue = '%' . $search . '%';
            $params['search_title'] = $searchValue;
            $params['search_description'] = $searchValue;
            $params['search_location'] = $searchValue;
        }

        if ($status !== '' && TicketStatus::isValid($status)) {
            $where[] = 't.status = :status';
            $params['status'] = $status;
        }

        if ($categoryId > 0) {
            $where[] = 't.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $whereSql = $where === [] ? '1 = 1' : implode(' AND ', $where);

        $sql = "SELECT
                    t.id,
                    t.titulo,
                    t.status,
                    t.localizacao,
                    t.created_at,
                    t.updated_at,
                    c.nome AS categoria,
                    u.nome AS solicitante,
                    {$protocolSelect}
                FROM tickets t
                INNER JOIN categories c ON c.id = t.category_id
                INNER JOIN users u ON u.id = t.user_id
                {$protocolJoin}
                WHERE {$whereSql}
                ORDER BY t.created_at DESC";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public function fetchById(int $ticketId, ?int $viewerUserId, bool $isAdmin): ?array
    {
        $hasProtocolTable = $this->tableExists('ticket_protocols');
        $protocolSelect = $hasProtocolTable
            ? 'tp.protocol_code,
                tp.created_at AS protocol_created_at'
            : 'NULL AS protocol_code,
                NULL AS protocol_created_at';
        $protocolJoin = $hasProtocolTable ? 'LEFT JOIN ticket_protocols tp ON tp.ticket_id = t.id' : '';

        $sql = "SELECT
                    t.id,
                    t.user_id,
                    t.titulo,
                    t.descricao,
                    t.localizacao,
                    t.status,
                    t.created_at,
                    t.updated_at,
                    t.resolved_at,
                    c.id AS category_id,
                    c.nome AS categoria,
                    u.nome AS solicitante,
                    u.email AS solicitante_email,
                    {$protocolSelect}
                FROM tickets t
                INNER JOIN categories c ON c.id = t.category_id
                INNER JOIN users u ON u.id = t.user_id
                {$protocolJoin}
                WHERE t.id = :id";

        $params = ['id' => $ticketId];

        // Evita vazamento de chamado para usuario nao administrador.
        if (!$isAdmin) {
            $sql .= ' AND t.user_id = :user_id';
            $params['user_id'] = (int) $viewerUserId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $ticket = $statement->fetch();
        return $ticket !== false ? $ticket : null;
    }

    public function fetchStats(?int $userId, bool $isAdmin): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'aberto' THEN 1 ELSE 0 END) AS abertos,
                    SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) AS em_andamento,
                    SUM(CASE WHEN status = 'solucionado' THEN 1 ELSE 0 END) AS solucionados,
                    SUM(CASE WHEN status = 'fechado' THEN 1 ELSE 0 END) AS fechados
                FROM tickets";

        $params = [];

        if (!$isAdmin) {
            $sql .= ' WHERE user_id = :user_id';
            $params['user_id'] = (int) $userId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetch() ?: [];
    }

    public function create(array $payload): int
    {
        // Escrita isolada em um metodo pequeno (coesao e manutencao).
        $statement = $this->pdo->prepare(
            'INSERT INTO tickets (user_id, category_id, titulo, descricao, localizacao, status, created_at, updated_at)
             VALUES (:user_id, :category_id, :titulo, :descricao, :localizacao, :status, NOW(), NOW())'
        );

        $statement->execute([
            'user_id' => (int) ($payload['user_id'] ?? 0),
            'category_id' => (int) ($payload['category_id'] ?? 0),
            'titulo' => (string) ($payload['titulo'] ?? ''),
            'descricao' => (string) ($payload['descricao'] ?? ''),
            'localizacao' => (string) ($payload['localizacao'] ?? ''),
            'status' => (string) ($payload['status'] ?? TicketStatus::OPEN),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findStatusMetadata(int $ticketId): ?array
    {
        $statement = $this->pdo->prepare('SELECT status, resolved_at FROM tickets WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $ticketId]);

        $metadata = $statement->fetch();
        return $metadata !== false ? $metadata : null;
    }

    public function updateStatus(int $ticketId, string $status, ?string $resolvedAt): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE tickets
             SET status = :status, resolved_at = :resolved_at, updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'status' => $status,
            'resolved_at' => $resolvedAt,
            'id' => $ticketId,
        ]);
    }
}
