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
            'district' => trim((string) ($filters['bairro'] ?? '')),
        ];

        $tickets = $this->ticketReadRepository->fetchDashboardTickets($normalizedFilters, $userId, $isAdmin);

        return [
            'categories' => $this->categoryRepository->all(),
            'stats' => $this->ticketReadRepository->fetchStats($userId, $isAdmin),
            'tickets' => $tickets,
            'valid_statuses' => TicketStatus::all(),
            'active_filters' => $normalizedFilters,
            'charts' => [
                'status' => $this->statusChart($tickets),
                'category' => $this->groupChart($tickets, 'categoria', 'Sem categoria'),
                'responsible' => $this->groupChart($tickets, 'responsavel', 'Nao atribuido'),
            ],
            'recent_updates' => $this->ticketReadRepository->fetchRecentUpdates($userId, $isAdmin, 8),
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

    private function statusChart(array $tickets): array
    {
        $labels = TicketStatus::all();
        $totals = array_fill_keys($labels, 0);

        foreach ($tickets as $ticket) {
            $status = (string) ($ticket['status'] ?? '');
            if (isset($totals[$status])) {
                $totals[$status]++;
            }
        }

        $rows = [];
        foreach ($totals as $status => $total) {
            $rows[] = [
                'label' => TicketStatus::label($status),
                'value' => $total,
            ];
        }

        return $rows;
    }

    private function groupChart(array $tickets, string $field, string $fallbackLabel): array
    {
        $totals = [];

        foreach ($tickets as $ticket) {
            $key = trim((string) ($ticket[$field] ?? ''));
            if ($key === '') {
                $key = $fallbackLabel;
            }

            if (!isset($totals[$key])) {
                $totals[$key] = 0;
            }

            $totals[$key]++;
        }

        arsort($totals);

        $rows = [];
        foreach ($totals as $label => $value) {
            $rows[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $rows;
    }
}
