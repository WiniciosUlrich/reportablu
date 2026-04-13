<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

function renderHeader(string $title): void
{
    $appName = env('APP_NAME', 'ReportaBlu');
    $flash = getFlash();
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> | <?= h((string) $appName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="bg-shape shape-a"></div>
    <div class="bg-shape shape-b"></div>

    <header class="site-header">
        <div class="container header-wrap">
            <a class="brand" href="index.php">
                <span class="brand-mark">RB</span>
                <span>
                    <strong><?= h((string) $appName) ?></strong>
                    <small>Portal de chamados da cidade</small>
                </span>
            </a>

            <nav class="nav-links">
                <a class="nav-link" href="index.php">Inicio</a>

                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php"><?= isAdmin() ? 'Painel geral' : 'Meus chamados' ?></a>
                    <a class="nav-link nav-action" href="new_ticket.php">Novo chamado</a>
                    <a class="nav-link" href="logout.php">Sair</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Entrar</a>
                    <a class="nav-link nav-action" href="register.php">Criar conta</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container main-content">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= h((string) $flash['type']) ?>">
                <?= h((string) $flash['message']) ?>
            </div>
        <?php endif; ?>
<?php
}

function renderFooter(): void
{
    $appName = env('APP_NAME', 'ReportaBlu');
    ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p><?= h((string) $appName) ?> - Plataforma web para registro de chamados.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
<?php
}