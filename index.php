<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$ticketFacade = \ReportaBlu\Application\AppFactory::ticketFacade(db(), __DIR__);

$search = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = (int) ($_GET['categoria'] ?? 0);

$homeData = $ticketFacade->homeData($_GET);
$categories = $homeData['categories'] ?? [];
$stats = $homeData['stats'] ?? [];
$solvedTickets = $homeData['solved_tickets'] ?? [];

renderHeader('Portal da cidade');
?>

<section class="hero">
    <div class="hero-copy">
        <p class="kicker">Conecte moradores e servicos urbanos</p>
        <h1>ReportaBlu: o portal para registrar e acompanhar chamados da cidade</h1>
        <p>
            Abra chamados com localizacao e anexos, acompanhe o andamento em tempo real e consulte historicos.
        </p>

        <div class="hero-cta">
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-primary" href="new_ticket.php">Abrir novo chamado</a>
                <a class="btn btn-secondary" href="dashboard.php">Acompanhar meus chamados</a>
            <?php else: ?>
                <a class="btn btn-primary" href="register.php">Criar conta</a>
                <a class="btn btn-secondary" href="login.php">Entrar no portal</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <h2><?= (int) ($stats['total'] ?? 0) ?></h2>
        <p>Chamados registrados</p>
    </article>
    <article class="stat-card">
        <h2><?= (int) ($stats['abertos'] ?? 0) ?></h2>
        <p>Chamados abertos</p>
    </article>
    <article class="stat-card">
        <h2><?= (int) ($stats['em_andamento'] ?? 0) ?></h2>
        <p>Em andamento</p>
    </article>
    <article class="stat-card">
        <h2><?= (int) ($stats['solucionados'] ?? 0) ?></h2>
        <p>Solucionados</p>
    </article>
</section>

<section class="panel">
    <div class="section-head">
        <h2>Chamados solucionados</h2>
        <p>Consulte os ultimos chamados finalizados para acompanhar melhorias na cidade.</p>
    </div>

    <form class="filter-form" method="get" action="index.php">
        <input
            type="text"
            name="q"
            placeholder="Buscar por titulo, descricao ou localizacao"
            value="<?= h($search) ?>"
        >

        <select name="categoria">
            <option value="0">Todas as categorias</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>>
                    <?= h((string) $category['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-primary" type="submit">Filtrar</button>
    </form>

    <div class="ticket-grid">
        <?php if (count($solvedTickets) === 0): ?>
            <p class="empty-state">Nenhum chamado solucionado encontrado com os filtros informados.</p>
        <?php endif; ?>

        <?php foreach ($solvedTickets as $ticket): ?>
            <article class="ticket-card solved-card">
                <div class="card-top">
                    <span class="status-badge status-solucionado">Solucionado</span>
                    <small><?= formatDateTime((string) $ticket['resolved_at']) ?></small>
                </div>

                <h3><?= h((string) $ticket['titulo']) ?></h3>
                <p><?= h((string) $ticket['descricao']) ?></p>

                <ul class="meta-list">
                    <li><strong>Protocolo:</strong> <?= h((string) ($ticket['protocol_code'] ?? '-')) ?></li>
                    <li><strong>Categoria:</strong> <?= h((string) $ticket['categoria']) ?></li>
                    <li><strong>Local:</strong> <?= h((string) $ticket['localizacao']) ?></li>
                </ul>

                <a class="card-link" href="ticket_detail.php?id=<?= (int) $ticket['id'] ?>">Ver detalhes</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php renderFooter(); ?>