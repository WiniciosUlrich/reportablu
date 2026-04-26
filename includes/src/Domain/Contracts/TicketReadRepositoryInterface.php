<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

// ISP: contrato de leitura separado do contrato de escrita.
// Services de consulta dependem apenas destas operacoes.
interface TicketReadRepositoryInterface
{
    public function fetchPublicSolved(array $filters, int $limit = 12): array;

    public function fetchDashboardTickets(array $filters, ?int $userId, bool $isAdmin): array;

    public function fetchById(int $ticketId, ?int $viewerUserId, bool $isAdmin): ?array;

    public function fetchStats(?int $userId, bool $isAdmin): array;
}
