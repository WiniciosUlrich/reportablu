<?php
declare(strict_types=1);

namespace ReportaBlu\Application\Exceptions;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /**
     * @var string[]
     */
    private array $errors;

    /**
     * @param string[] $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct($errors[0] ?? 'Erro de validacao.');
        $this->errors = $errors;
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
