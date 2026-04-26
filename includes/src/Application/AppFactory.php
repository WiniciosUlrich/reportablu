<?php
declare(strict_types=1);

namespace ReportaBlu\Application;

use PDO;
use ReportaBlu\Domain\TicketProtocolGenerator;
use ReportaBlu\Infrastructure\Database\PdoTransactionManager;
use ReportaBlu\Infrastructure\Repository\PdoCategoryRepository;
use ReportaBlu\Infrastructure\Repository\PdoTicketAssignmentRepository;
use ReportaBlu\Infrastructure\Repository\PdoTicketFileRepository;
use ReportaBlu\Infrastructure\Repository\PdoTicketHistoryRepository;
use ReportaBlu\Infrastructure\Repository\PdoTicketRepository;
use ReportaBlu\Infrastructure\Repository\PdoTicketResponseRepository;
use ReportaBlu\Infrastructure\Repository\PdoTicketProtocolRepository;
use ReportaBlu\Infrastructure\Repository\PdoUserRepository;
use ReportaBlu\Infrastructure\Storage\DefaultUploadValidationStrategy;
use ReportaBlu\Infrastructure\Storage\LocalAttachmentStorage;

// Factory: centraliza a criacao dos objetos da aplicacao em um unico ponto.
// Isso reduz acoplamento nos controllers e facilita troca de implementacoes.
final class AppFactory
{
    public static function ticketFacade(PDO $pdo, string $projectRoot): TicketFacade
    {
        // Repositories concretos ficam restritos a camada Infrastructure.
        // Services recebem contratos/interfaces (DIP), nao detalhes de PDO.
        $categoryRepository = new PdoCategoryRepository($pdo);
        $ticketRepository = new PdoTicketRepository($pdo);
        $ticketHistoryRepository = new PdoTicketHistoryRepository($pdo);
        $ticketFileRepository = new PdoTicketFileRepository($pdo);
        $ticketProtocolRepository = new PdoTicketProtocolRepository($pdo);
        $ticketAssignmentRepository = new PdoTicketAssignmentRepository($pdo);
        $ticketResponseRepository = new PdoTicketResponseRepository($pdo);
        $transactionManager = new PdoTransactionManager($pdo);

        // Composicao + Strategy: validacao de upload pode ser trocada sem mexer no storage.
        $validationStrategy = new DefaultUploadValidationStrategy();
        $attachmentStorage = new LocalAttachmentStorage(
            $projectRoot . '/uploads',
            'uploads/',
            $validationStrategy
        );

        $attachmentService = new AttachmentService($attachmentStorage);

        // Cada service tem responsabilidade unica (coesao alta / SRP).
        $creationService = new TicketCreationService(
            $categoryRepository,
            $ticketRepository,
            $ticketHistoryRepository,
            $ticketFileRepository,
            $ticketProtocolRepository,
            $transactionManager,
            $attachmentService,
            new TicketProtocolGenerator()
        );

        // Service dedicado a leitura para separar comandos de consultas.
        $queryService = new TicketQueryService(
            $categoryRepository,
            $ticketRepository,
            $ticketFileRepository,
            $ticketHistoryRepository,
            $ticketAssignmentRepository,
            $ticketResponseRepository
        );

        // Service dedicado ao fluxo operacional do chamado.
        $workflowService = new TicketWorkflowService(
            $ticketRepository,
            $ticketHistoryRepository,
            $ticketAssignmentRepository,
            $ticketResponseRepository,
            $transactionManager
        );

        // Facade simplifica o uso da camada de aplicacao para a camada de UI.
        return new TicketFacade(
            $creationService,
            $queryService,
            $workflowService
        );
    }

    public static function authService(PDO $pdo): AuthService
    {
        return new AuthService(new PdoUserRepository($pdo));
    }
}
