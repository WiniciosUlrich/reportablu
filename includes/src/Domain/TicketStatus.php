<?php
declare(strict_types=1);

namespace ReportaBlu\Domain;

final class TicketStatus
{
    public const OPEN = 'aberto';
    public const IN_PROGRESS = 'em_andamento';
    public const SOLVED = 'solucionado';
    public const CLOSED = 'fechado';

    private const LABELS = [
        self::OPEN => 'Aberto',
        self::IN_PROGRESS => 'Em andamento',
        self::SOLVED => 'Solucionado',
        self::CLOSED => 'Fechado',
    ];

    private const CSS_CLASS = [
        self::OPEN => 'status-aberto',
        self::IN_PROGRESS => 'status-em-andamento',
        self::SOLVED => 'status-solucionado',
        self::CLOSED => 'status-fechado',
    ];

    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function isValid(string $status): bool
    {
        return isset(self::LABELS[$status]);
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? 'Indefinido';
    }

    public static function cssClass(string $status): string
    {
        return self::CSS_CLASS[$status] ?? self::CSS_CLASS[self::OPEN];
    }
}
