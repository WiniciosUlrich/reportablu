<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Storage;

use ReportaBlu\Domain\Contracts\AttachmentStorageInterface;
use ReportaBlu\Domain\Contracts\UploadValidationStrategyInterface;
use RuntimeException;

// Implementacao concreta de storage local.
// Encapsula detalhes de filesystem atras da interface AttachmentStorageInterface.
final class LocalAttachmentStorage implements AttachmentStorageInterface
{
    public function __construct(
        private string $uploadDirectory,
        private string $publicPrefix,
        private UploadValidationStrategyInterface $validationStrategy
    ) {
        // Preparacao da infraestrutura local fica escondida dentro do modulo.
        if (!is_dir($this->uploadDirectory)) {
            if (!mkdir($this->uploadDirectory, 0775, true) && !is_dir($this->uploadDirectory)) {
                throw new RuntimeException('Nao foi possivel criar a pasta de uploads.');
            }
        }
    }

    public function storeUploadedFile(array $fileData): array
    {
        // Strategy: regra de validacao injetada, permitindo trocar algoritmo sem alterar storage.
        $error = $this->validationStrategy->validate($fileData);
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $originalName = (string) $fileData['name'];
        $tmpName = (string) $fileData['tmp_name'];
        $fileSize = (int) $fileData['size'];

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $safeFileName = bin2hex(random_bytes(18));

        if ($extension !== '') {
            $safeFileName .= '.' . $extension;
        }

        $absolutePath = rtrim($this->uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeFileName;

        // Operacao de I/O isolada para manter a camada de aplicacao livre de detalhes tecnicos.
        if (!move_uploaded_file($tmpName, $absolutePath)) {
            throw new RuntimeException('Nao foi possivel salvar o arquivo ' . $originalName . '.');
        }

        $mimeType = function_exists('mime_content_type')
            ? (string) mime_content_type($absolutePath)
            : 'application/octet-stream';

        return [
            'original_name' => $originalName,
            'file_path' => rtrim($this->publicPrefix, '/') . '/' . $safeFileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'absolute_path' => $absolutePath,
        ];
    }

    public function remove(string $absolutePath): void
    {
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }
}
