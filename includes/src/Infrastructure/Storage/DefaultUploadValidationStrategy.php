<?php
declare(strict_types=1);

namespace ReportaBlu\Infrastructure\Storage;

use ReportaBlu\Domain\Contracts\UploadValidationStrategyInterface;

final class DefaultUploadValidationStrategy implements UploadValidationStrategyInterface
{
    private const MAX_FILE_SIZE = 5242880;

    /**
     * @var string[]
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

    public function validate(array $fileData): ?string
    {
        $name = (string) ($fileData['name'] ?? 'arquivo');
        $error = (int) ($fileData['error'] ?? UPLOAD_ERR_NO_FILE);
        $size = (int) ($fileData['size'] ?? 0);
        $tmpName = (string) ($fileData['tmp_name'] ?? '');

        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'Nenhum arquivo enviado para ' . $name . '.';
        }

        if ($error !== UPLOAD_ERR_OK) {
            return 'Falha no upload do arquivo ' . $name . '.';
        }

        if ($size <= 0) {
            return 'O arquivo ' . $name . ' esta vazio.';
        }

        if ($size > self::MAX_FILE_SIZE) {
            return 'O arquivo ' . $name . ' excede o limite de 5MB.';
        }

        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return 'Extensao nao permitida para o arquivo ' . $name . '.';
        }

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return 'Arquivo temporario invalido para ' . $name . '.';
        }

        return null;
    }
}
