<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

$pdo = db();

$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$categoryFilter = (int) ($_GET['categoria'] ?? 0);

$validStatuses = ['aberto', 'em_andamento', 'solucionado', 'fechado'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$categoryStmt = $pdo->query('SELECT id, nome FROM categories ORDER BY nome');
$categories = $categoryStmt->fetchAll();

$statsSql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'aberto' THEN 1 ELSE 0 END) AS abertos,
                SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) AS em_andamento,
                SUM(CASE WHEN status = 'solucionado' THEN 1 ELSE 0 END) AS solucionados,
                SUM(CASE WHEN status = 'fechado' THEN 1 ELSE 0 END) AS fechados
            FROM tickets";

if (!isAdmin()) {
    $statsSql .= ' WHERE user_id = :user_id';
}

$statsStmt = $pdo->prepare($statsSql);
if (isAdmin()) {
    $statsStmt->execute();
} else {
    $statsStmt->execute(['user_id' => currentUserId()]);
}

$stats = $statsStmt->fetch() ?: [];

$where = [];
$params = [];

if (!isAdmin()) {
    $where[] = 't.user_id = :user_id';
    $params['user_id'] = currentUserId();
}

if ($search !== '') {
    $where[] = '(t.titulo LIKE :search_title OR t.descricao LIKE :search_description OR t.localizacao LIKE :search_location)';
    $searchValue = '%' . $search . '%';
    $params['search_title'] = $searchValue;
    $params['search_description'] = $searchValue;
    $params['search_location'] = $searchValue;
}

if ($statusFilter !== '') {
    $where[] = 't.status = :status';
    $params['status'] = $statusFilter;
}

if ($categoryFilter > 0) {
    $where[] = 't.category_id = :category_id';
    $params['category_id'] = $categoryFilter;
}

$whereSql = count($where) > 0 ? implode(' AND ', $where) : '1 = 1';

$listSql = "SELECT
                t.id,
                t.titulo,
                t.status,
                t.localizacao,
                t.created_at,
                t.updated_at,
                c.nome AS categoria,
                u.nome AS solicitante
            FROM tickets t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN users u ON u.id = t.user_id
            WHERE " . $whereSql . "
            ORDER BY t.created_at DESC";

$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$tickets = $listStmt->fetchAll();

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
                        <td colspan="<?= isAdmin() ? '9' : '8' ?>" class="empty-state">Nenhum chamado encontrado.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= (int) $ticket['id'] ?></td>
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