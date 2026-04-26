<?php
declare(strict_types=1);

namespace ReportaBlu\Application;

use PDOException;
use ReportaBlu\Application\Exceptions\ValidationException;
use ReportaBlu\Domain\Contracts\CategoryRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketFileRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketHistoryRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketProtocolRepositoryInterface;
use ReportaBlu\Domain\Contracts\TicketWriteRepositoryInterface;
use ReportaBlu\Domain\Contracts\TransactionManagerInterface;
use ReportaBlu\Domain\TicketProtocolGenerator;
use ReportaBlu\Domain\TicketStatus;
use RuntimeException;
use Throwable;

final class TicketCreationService
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private TicketWriteRepositoryInterface $ticketWriteRepository,
        private TicketHistoryRepositoryInterface $ticketHistoryRepository,
        private TicketFileRepositoryInterface $ticketFileRepository,
        private TicketProtocolRepositoryInterface $ticketProtocolRepository,
        private TransactionManagerInterface $transactionManager,
        private AttachmentService $attachmentService,
        private TicketProtocolGenerator $protocolGenerator
    ) {
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed>|null $uploadedFiles
     * @return array{ticket_id:int,protocol_code:string}
     */
    public function create(array $input, int $userId, ?array $uploadedFiles): array
    {
        $title = trim((string) ($input['titulo'] ?? ''));
        $description = trim((string) ($input['descricao'] ?? ''));
        $location = trim((string) ($input['localizacao'] ?? ''));
        $categoryId = (int) ($input['categoria'] ?? 0);

        $errors = [];

        if ($title === '') {
            $errors[] = 'Informe um titulo para o chamado.';
        }

        if (strlen($description) < 15) {
            $errors[] = 'A descricao deve ter ao menos 15 caracteres.';
        }

        if ($location === '') {
            $errors[] = 'Informe a localizacao.';
        }

        if ($categoryId <= 0 || !$this->categoryRepository->exists($categoryId)) {
            $errors[] = 'Selecione uma categoria valida.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $storedFiles = $this->attachmentService->storeManyFromRequest($uploadedFiles);

        try {
            return $this->transactionManager->transactional(function () use ($userId, $categoryId, $title, $description, $location, $storedFiles): array {
                $ticketId = $this->ticketWriteRepository->create([
                    'user_id' => $userId,
                    'category_id' => $categoryId,
                    'titulo' => $title,
                    'descricao' => $description,
                    'localizacao' => $location,
                    'status' => TicketStatus::OPEN,
                ]);

                $this->ticketHistoryRepository->add($ticketId, TicketStatus::OPEN, 'Chamado criado pelo morador.');

                $protocolCode = $this->createProtocol($ticketId);

                foreach ($storedFiles as $storedFile) {
                    $this->ticketFileRepository->add(
                        $ticketId,
                        (string) $storedFile['original_name'],
                        (string) $storedFile['file_path'],
                        (string) $storedFile['mime_type'],
                        (int) $storedFile['file_size']
                    );
                }

                return [
                    'ticket_id' => $ticketId,
                    'protocol_code' => $protocolCode,
                ];
            });
        } catch (Throwable $exception) {
            $this->attachmentService->cleanupStoredFiles($storedFiles);

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Nao foi possivel registrar o chamado agora. Tente novamente.');
        }
    }

    private function createProtocol(int $ticketId): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $protocolCode = $this->protocolGenerator->generate();

            try {
                $this->ticketProtocolRepository->create($ticketId, $protocolCode);
                return $protocolCode;
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() !== '23000') {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Nao foi possivel gerar um protocolo unico para o chamado.');
    }
}
