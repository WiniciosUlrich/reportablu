<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface AttachmentStorageInterface
{
    /**
     * @param array{name:string,tmp_name:string,size:int,error:int} $fileData
     * @return array{original_name:string,file_path:string,mime_type:string,file_size:int,absolute_path:string}
     */
    public function storeUploadedFile(array $fileData): array;

    public function remove(string $absolutePath): void;
}
