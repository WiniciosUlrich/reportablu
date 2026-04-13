<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function currentUserId(): ?int
{
    if (!isLoggedIn()) {
        return null;
    }

    return (int) $_SESSION['user']['id'];
}

function currentUserName(): string
{
    if (!isLoggedIn()) {
        return 'Visitante';
    }

    return (string) $_SESSION['user']['name'];
}

function currentUserRole(): string
{
    if (!isLoggedIn()) {
        return 'visitante';
    }

    return (string) ($_SESSION['user']['role'] ?? 'morador');
}

function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function requireLogin(): void
{
    if (isLoggedIn()) {
        return;
    }

    setFlash('error', 'Faca login para continuar.');
    redirect('login.php');
}

function requireGuest(): void
{
    if (!isLoggedIn()) {
        return;
    }

    redirect('dashboard.php');
}