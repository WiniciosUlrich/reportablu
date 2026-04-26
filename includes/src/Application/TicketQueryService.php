<?php
declare(strict_types=1);

namespace ReportaBlu\Application;

use ReportaBlu\Domain\Contracts\CategoryRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketAssignmentRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketFileRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketHistoryRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketReadRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketResponseRepositoryInterface;
use ReportaBlu\Domain\TicketStatus;

// Service de leitura (queries): evita misturar comando e consulta na mesma classe.
final class TicketQueryService
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private TicketReadRepositoryInterface $ticketReadRepository,
        private TicketFileRepositoryInterface $ticketFileRepository,
        private TicketHistoryRepositoryInterface $ticketHistoryRepository,
        private TicketAssignmentRepositoryInterface $ticketAssignmentRepository,
        private TicketResponseRepositoryInterface $ticketResponseRepository
    ) {
    }

    public function categories(): array
    {
        // Consulta simples delegada ao repositorio para manter controller enxuto.
        return $this->categoryRepository->all();
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function homeData(array $filters): array
    {
        // Normaliza filtros em um formato consistente para a camada de dados.
        $normalizedFilters = [
            'search' => trim((string) ($filters['q'] ?? '')),
            'category_id' => (int) ($filters['categoria'] ?? 0),
        ];

        return [
            'categories' => $this->categoryRepository->all(),
            'stats' => $this->ticketReadRepository->fetchStats(null, true),
            'solved_tickets' => $this->ticketReadRepository->fetchPublicSolved($normalizedFilters, 12),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function dashboardData(array $filters, ?int $userId, bool $isAdmin): array
    {
        $status = trim((string) ($filters['status'] ?? ''));

        if (!TicketStatus::isValid($status)) {
            $status = '';
        }

        $normalizedFilters = [
            'search' => trim((string) ($filters['q'] ?? '')),
            'status' => $status,
            'category_id' => (int) ($filters['categoria'] ?? 0),
        ];

        return [
            'categories' => $this->categoryRepository->all(),
            'stats' => $this->ticketReadRepository->fetchStats($userId, $isAdmin),
            'tickets' => $this->ticketReadRepository->fetchDashboardTickets($normalizedFilters, $userId, $isAdmin),
            'valid_statuses' => TicketStatus::all(),
            'active_filters' => $normalizedFilters,
        ];
    }

    public function ticketDetail(int $ticketId, ?int $viewerUserId, bool $isAdmin): ?array
    {
        // Compoe dados agregados em um unico payload para simplificar a camada de UI.
        $ticket = $this->ticketReadRepository->fetchById($ticketId, $viewerUserId, $isAdmin);
        if ($ticket === null) {
            return null;
        }

        return [
            'ticket' => $ticket,
            'files' => $this->ticketFileRepository->listByTicket($ticketId),
            'history' => $this->ticketHistoryRepository->listByTicket($ticketId),
            'assignment' => $this->ticketAssignmentRepository->latestByTicket($ticketId),
            'responses' => $this->ticketResponseRepository->listByTicket($ticketId),
        ];
    }
}
