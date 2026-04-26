<?php
declare(strict_types=1);

namespace ReportaBlu\Domain\Contracts;

interface UploadValidationStrategyInterface
{
    /**
     * @param array{name:string,tmp_name:string,size:int,error:int} $fileData
     */
    public function validate(array $fileData): ?string;
}
