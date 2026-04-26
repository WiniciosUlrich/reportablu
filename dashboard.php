<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

$ticketFacade = \ReportaBlu\Application\AppFactory::ticketFacade(db(), __DIR__);

$dashboardData = $ticketFacade->dashboardData($_GET, currentUserId(), isAdmin());
$categories = $dashboardData['categories'] ?? [];
$stats = $dashboardData['stats'] ?? [];
$tickets = $dashboardData['tickets'] ?? [];
$validStatuses = $dashboardData['valid_statuses'] ?? [];
$activeFilters = $dashboardData['active_filters'] ?? [];

$search = (string) ($activeFilters['search'] ?? '');
$statusFilter = (string) ($activeFilters['status'] ?? '');
$categoryFilter = (int) ($activeFilters['category_id'] ?? 0);

renderHeader(isAdmin() ? 'Painel geral' : 'Meus chamados');
?>

<section class="section-head">
    <h1><?= isAdmin() ? 'Painel geral de chamados' : 'Historico de chamados' ?></h1>
    <p>
        <?= isAdmin() ? 'Visualize chamados de todos os moradores e atualize o andamento.' : 'Filtre e acompanhe o status dos seus chamados.' ?>
    </p>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <h2><?= (int) ($stats['total'] ?? 0) ?></h2>
        <p>Total</p>
    </article>
    <article class="stat-card">
        <h2><?= (int) ($stats['abertos'] ?? 0) ?></h2>
        <p>Abertos</p>
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
    <form class="filter-form" method="get" action="dashboard.php">
        <input
            type="text"
            name="q"
            placeholder="Buscar por titulo, descricao ou localizacao"
            value="<?= h($search) ?>"
        >

        <select name="status">
            <option value="">Todos os status</option>
            <?php foreach ($validStatuses as $statusOption): ?>
                <option value="<?= h($statusOption) ?>" <?= $statusFilter === $statusOption ? 'selected' : '' ?>>
                    <?= h(statusLabel($statusOption)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="categoria">
            <option value="0">Todas as categorias</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>>
                    <?= h((string) $category['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-primary" type="submit">Aplicar filtros</button>
    </form>

    <div class="table-wrapper">
        <table class="ticket-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Protocolo</th>
                    <th>Titulo</th>
                    <th>Categoria</th>
                    <th>Localizacao</th>
                    <?php if (isAdmin()): ?>
                        <th>Solicitante</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Atualizado</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tickets) === 0): ?>
                    <tr>
                        <td colspan="<?= isAdmin() ? '10' : '9' ?>" class="empty-state">Nenhum chamado encontrado.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= (int) $ticket['id'] ?></td>
                        <td><?= h((string) ($ticket['protocol_code'] ?? '-')) ?></td>
                        <td><?= h((string) $ticket['titulo']) ?></td>
                        <td><?= h((string) $ticket['categoria']) ?></td>
                        <td><?= h((string) $ticket['localizacao']) ?></td>
                        <?php if (isAdmin()): ?>
                            <td><?= h((string) $ticket['solicitante']) ?></td>
                        <?php endif; ?>
                        <td>
                            <span class="status-badge <?= h(statusClass((string) $ticket['status'])) ?>">
                                <?= h(statusLabel((string) $ticket['status'])) ?>
                            </span>
                        </td>
                        <td><?= formatDateTime((string) $ticket['created_at']) ?></td>
                        <td><?= formatDateTime((string) $ticket['updated_at']) ?></td>
                        <td><a class="card-link" href="ticket_detail.php?id=<?= (int) $ticket['id'] ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php renderFooter(); ?>