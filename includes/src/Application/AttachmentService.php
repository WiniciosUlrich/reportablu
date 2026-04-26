<?php
declare(strict_types=1);

namespace ReportaBlu\Application;

use ReportaBlu\Application\Exceptions\ValidationException;
use ReportaBlu\Domain\Contracts\AttachmentStorageInterface;
use RuntimeException;

final class AttachmentService
{
    public function __construct(
        private AttachmentStorageInterface $storage
    ) {
    }

    /**
     * @param array<string,mixed>|null $uploadInput
     * @return array<int, array{original_name:string,file_path:string,mime_type:string,file_size:int,absolute_path:string}>
     */
    public function storeManyFromRequest(?array $uploadInput): array
    {
        if ($uploadInput === null) {
            return [];
        }

        $normalizedFiles = $this->normalizeFiles($uploadInput);
        if ($normalizedFiles === []) {
            return [];
        }

        $stored = [];
        $errors = [];

        foreach ($normalizedFiles as $fileData) {
            if (($fileData['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($fileData['name'] ?? '') === '') {
                continue;
            }

            try {
                $stored[] = $this->storage->storeUploadedFile($fileData);
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            $this->cleanupStoredFiles($stored);
            throw new ValidationException($errors);
        }

        return $stored;
    }

    /**
     * @param array<int, array{absolute_path:string}> $storedFiles
     */
    public function cleanupStoredFiles(array $storedFiles): void
    {
        foreach ($storedFiles as $storedFile) {
            if (!isset($storedFile['absolute_path'])) {
                continue;
            }

            $this->storage->remove((string) $storedFile['absolute_path']);
        }
    }

    /**
     * @param array<string,mixed> $uploadInput
     * @return array<int, array{name:string,tmp_name:string,size:int,error:int}>
     */
    private function normalizeFiles(array $uploadInput): array
    {
        if (!isset($uploadInput['name']) || !is_array($uploadInput['name'])) {
            return [];
        }

        $count = count($uploadInput['name']);
        $normalized = [];

        for ($index = 0; $index < $count; $index++) {
            $normalized[] = [
                'name' => (string) ($uploadInput['name'][$index] ?? ''),
                'tmp_name' => (string) ($uploadInput['tmp_name'][$index] ?? ''),
                'size' => (int) ($uploadInput['size'][$index] ?? 0),
                'error' => (int) ($uploadInput['error'][$index] ?? UPLOAD_ERR_NO_FILE),
            ];
        }

        return $normalized;
    }
}
