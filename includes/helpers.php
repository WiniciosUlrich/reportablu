<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function formatDateTime(?string $dateTime): string
{
    if ($dateTime === null || $dateTime === '') {
        return '-';
    }

    $timestamp = strtotime($dateTime);
    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y H:i', $timestamp);
}

function formatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1048576) {
        return round($bytes / 1024, 2) . ' KB';
    }

    if ($bytes < 1073741824) {
        return round($bytes / 1048576, 2) . ' MB';
    }

    return round($bytes / 1073741824, 2) . ' GB';
}

function statusLabel(string $status): string
{
    $labels = [
        'aberto' => 'Aberto',
        'em_andamento' => 'Em andamento',
        'solucionado' => 'Solucionado',
        'fechado' => 'Fechado',
    ];

    return $labels[$status] ?? 'Indefinido';
}

function statusClass(string $status): string
{
    $classMap = [
        'aberto' => 'status-aberto',
        'em_andamento' => 'status-em-andamento',
        'solucionado' => 'status-solucionado',
        'fechado' => 'status-fechado',
    ];

    return $classMap[$status] ?? 'status-aberto';
}