<?php
declare(strict_types=1);

namespace ReportaBlu\Application;

use ReportaBlu\Application\Exceptions\AuthorizationException;
use ReportaBlu\Application\Exceptions\NotFoundException;
use ReportaBlu\Application\Exceptions\ValidationException;
use ReportaBlu\Domain\Contracts\TicketAssignmentRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketHistoryRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketResponseRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketWriteRepositoryInterface;
use ReportaBlu\Domain\Contracts\TransactionManagerInterface;
use ReportaBlu\Domain\DepartmentCatalog;
use ReportaBlu\Domain\TicketStatus;

// Service de comando/workflow: concentra alteracoes de estado do chamado.
final class TicketWorkflowService
{
    public function __construct(
        private TicketWriteRepositoryInterface $ticketWriteRepository,
        private TicketHistoryRepositoryInterface $ticketHistoryRepository,
        private TicketAssignmentRepositoryInterface $ticketAssignmentRepository,
        private TicketResponseRepositoryInterface $ticketResponseRepository,
        private TransactionManagerInterface $transactionManager
    ) {
    }

    public function updateStatus(int $ticketId, string $newStatus, string $note, bool $isAdmin): void
    {
        // Regra de autorizacao fica na aplicacao para manter comportamento consistente.
        if (!$isAdmin) {
            throw new AuthorizationException('Apenas administradores podem alterar status.');
        }

        $newStatus = trim($newStatus);
        if (!TicketStatus::isValid($newStatus)) {
            throw new ValidationException(['Status invalido.']);
        }

        $currentMetadata = $this->ticketWriteRepository->findStatusMetadata($ticketId);
        if ($currentMetadata === null) {
            throw new NotFoundException('Chamado nao encontrado.');
        }

        $resolvedAt = $currentMetadata['resolved_at'] ?? null;
        if ($newStatus === TicketStatus::SOLVED && ($resolvedAt === null || $resolvedAt === '')) {
            $resolvedAt = date('Y-m-d H:i:s');
        }

        if ($newStatus === TicketStatus::OPEN || $newStatus === TicketStatus::IN_PROGRESS) {
            $resolvedAt = null;
        }

        $note = trim($note);
        $historyNote = $note !== '' ? $note : 'Atualizacao de status pelo administrador.';

        // Transacao evita historico sem atualizacao de status (ou vice-versa).
        $this->transactionManager->transactional(function () use ($ticketId, $newStatus, $resolvedAt, $historyNote): void {
            $this->ticketWriteRepository->updateStatus($ticketId, $newStatus, $resolvedAt);
            $this->ticketHistoryRepository->add($ticketId, $newStatus, $historyNote);
        });
    }

    public function assignDepartment(int $ticketId, string $department, string $note, int $assignedByUserId, bool $isAdmin): void
    {
        if (!$isAdmin) {
            throw new AuthorizationException('Apenas administradores podem encaminhar chamados.');
        }

        if (!DepartmentCatalog::exists($department)) {
            throw new ValidationException(['Setor invalido para encaminhamento.']);
        }

        $currentMetadata = $this->ticketWriteRepository->findStatusMetadata($ticketId);
        if ($currentMetadata === null) {
            throw new NotFoundException('Chamado nao encontrado.');
        }

        $note = trim($note);
        $historyNote = $note !== '' ? $note : 'Chamado encaminhado para o setor ' . DepartmentCatalog::label($department) . '.';
        $status = (string) $currentMetadata['status'];

        // Encaminhamento e registro historico sao atomicos.
        $this->transactionManager->transactional(function () use ($ticketId, $department, $note, $assignedByUserId, $status, $historyNote): void {
            $this->ticketAssignmentRepository->assign($ticketId, $department, $note, $assignedByUserId);
            $this->ticketHistoryRepository->add($ticketId, $status, $historyNote);
        });
    }

    public function addResponse(int $ticketId, int $authorUserId, string $authorName, string $message, bool $isAdmin): void
    {
        if (!$isAdmin) {
            throw new AuthorizationException('Apenas administradores podem responder chamados.');
        }

        $message = trim($message);
        if ($message === '' || strlen($message) < 5) {
            throw new ValidationException(['Informe uma resposta com ao menos 5 caracteres.']);
        }

        $currentMetadata = $this->ticketWriteRepository->findStatusMetadata($ticketId);
        if ($currentMetadata === null) {
            throw new NotFoundException('Chamado nao encontrado.');
        }

        $status = (string) $currentMetadata['status'];

        // Resposta e historico juntos para manter trilha de auditoria coesa.
        $this->transactionManager->transactional(function () use ($ticketId, $authorUserId, $authorName, $message, $status): void {
            $this->ticketResponseRepository->add($ticketId, $authorUserId, $authorName, $message);
            $this->ticketHistoryRepository->add($ticketId, $status, 'Resposta enviada ao cidadao pelo atendente.');
        });
    }
}
