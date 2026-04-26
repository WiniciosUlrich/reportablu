<?php
declare(strict_types=1);

namespace ReportaBlu\Domain;

final class DepartmentCatalog
{
    private const DEPARTMENTS = [
        'iluminacao' => 'Iluminacao publica',
        'infraestrutura' => 'Infraestrutura urbana',
        'transito' => 'Transito e mobilidade',
        'saneamento' => 'Saneamento',
        'limpeza_urbana' => 'Limpeza urbana',
        'fiscalizacao' => 'Fiscalizacao',
        'zeladoria' => 'Zeladoria',
    ];

    public static function all(): array
    {
        return self::DEPARTMENTS;
    }

    public static function exists(string $department): bool
    {
        return isset(self::DEPARTMENTS[$department]);
    }

    public static function label(string $department): string
    {
        return self::DEPARTMENTS[$department] ?? 'Setor nao informado';
    }
}
