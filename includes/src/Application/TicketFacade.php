<?php
declare(strict_types=1);

namespace ReportaBlu\Application;

use ReportaBlu\Domain\DepartmentCatalog;
use ReportaBlu\Domain\TicketStatus;

// Facade: ponto unico para orquestrar casos de uso de chamados.
// A UI depende desta interface simples, nao dos detalhes internos dos services.
final class TicketFacade
{
    public function __construct(
        private TicketCreationService $ticketCreationService,
        private TicketQueryService $ticketQueryService,
        private TicketWorkflowService $ticketWorkflowService
    ) {
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed>|null $uploadedFiles
     * @return array{ticket_id:int,protocol_code:string}
     */
    public function createTicket(array $input, int $userId, ?array $uploadedFiles): array
    {
        // Encaminha para o service especializado de criacao (SRP).
        return $this->ticketCreationService->create($input, $userId, $uploadedFiles);
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function homeData(array $filters): array
    {
        // Consulta centralizada em service de leitura para manter coesao.
        return $this->ticketQueryService->homeData($filters);
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function dashboardData(array $filters, ?int $userId, bool $isAdmin): array
    {
        return $this->ticketQueryService->dashboardData($filters, $userId, $isAdmin);
    }

    public function ticketDetail(int $ticketId, ?int $viewerUserId, bool $isAdmin): ?array
    {
        return $this->ticketQueryService->ticketDetail($ticketId, $viewerUserId, $isAdmin);
    }

    public function categories(): array
    {
        return $this->ticketQueryService->categories();
    }

    public function updateStatus(int $ticketId, string $newStatus, string $note, bool $isAdmin): void
    {
        // Workflow isolado: regra de negocio fica fora da UI.
        $this->ticketWorkflowService->updateStatus($ticketId, $newStatus, $note, $isAdmin);
    }

    public function assignDepartment(int $ticketId, string $department, string $note, int $assignedByUserId, bool $isAdmin): void
    {
        $this->ticketWorkflowService->assignDepartment($ticketId, $department, $note, $assignedByUserId, $isAdmin);
    }

    public function addResponse(int $ticketId, int $authorUserId, string $authorName, string $message, bool $isAdmin): void
    {
        $this->ticketWorkflowService->addResponse($ticketId, $authorUserId, $authorName, $message, $isAdmin);
    }

    public function validStatuses(): array
    {
        return TicketStatus::all();
    }

    public function departments(): array
    {
        return DepartmentCatalog::all();
    }
}
