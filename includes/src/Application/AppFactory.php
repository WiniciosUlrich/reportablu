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

final class AppFactory
{
    public static function ticketFacade(PDO $pdo, string $projectRoot): TicketFacade
    {
        $categoryRepository = new PdoCategoryRepository($pdo);
        $ticketRepository = new PdoTicketRepository($pdo);
        $ticketHistoryRepository = new PdoTicketHistoryRepository($pdo);
        $ticketFileRepository = new PdoTicketFileRepository($pdo);
        $ticketProtocolRepository = new PdoTicketProtocolRepository($pdo);
        $ticketAssignmentRepository = new PdoTicketAssignmentRepository($pdo);
        $ticketResponseRepository = new PdoTicketResponseRepository($pdo);
        $transactionManager = new PdoTransactionManager($pdo);

        $validationStrategy = new DefaultUploadValidationStrategy();
        $attachmentStorage = new LocalAttachmentStorage(
            $projectRoot . '/uploads',
            'uploads/',
            $validationStrategy
        );

        $attachmentService = new AttachmentService($attachmentStorage);

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

        $queryService = new TicketQueryService(
            $categoryRepository,
            $ticketRepository,
            $ticketFileRepository,
            $ticketHistoryRepository,
            $ticketAssignmentRepository,
            $ticketResponseRepository
        );

        $workflowService = new TicketWorkflowService(
            $ticketRepository,
            $ticketHistoryRepository,
            $ticketAssignmentRepository,
            $ticketResponseRepository,
            $transactionManager
        );

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
