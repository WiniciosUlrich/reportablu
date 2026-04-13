<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$pdo = db();

$search = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = (int) ($_GET['categoria'] ?? 0);

$categoryStmt = $pdo->query('SELECT id, nome FROM categories ORDER BY nome');
$categories = $categoryStmt->fetchAll();

$statsStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'aberto' THEN 1 ELSE 0 END) AS abertos,
        SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) AS em_andamento,
        SUM(CASE WHEN status = 'solucionado' THEN 1 ELSE 0 END) AS solucionados
     FROM tickets"
);
$stats = $statsStmt->fetch() ?: [];

$sql = "SELECT
            t.id,
            t.titulo,
            t.descricao,
            t.localizacao,
            t.resolved_at,
            c.nome AS categoria
        FROM tickets t
        INNER JOIN categories c ON c.id = t.category_id
        WHERE t.status = 'solucionado'";

$params = [];

if ($search !== '') {
    $sql .= ' AND (t.titulo LIKE :search_title OR t.descricao LIKE :search_description OR t.localizacao LIKE :search_location)';
    $searchValue = '%' . $search . '%';
    $params['search_title'] = $searchValue;
    $params['search_description'] = $searchValue;
    $params['search_location'] = $searchValue;
}

if ($categoryFilter > 0) {
    $sql .= ' AND t.category_id = :category';
    $params['category'] = $categoryFilter;
}

$sql .= ' ORDER BY t.resolved_at DESC, t.updated_at DESC LIMIT 12';

$solvedStmt = $pdo->prepare($sql);
$solvedStmt->execute($params);
$solvedTickets = $solvedStmt->fetchAll();

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
                    <li><strong>Categoria:</strong> <?= h((string) $ticket['categoria']) ?></li>
                    <li><strong>Local:</strong> <?= h((string) $ticket['localizacao']) ?></li>
                </ul>

                <a class="card-link" href="ticket_detail.php?id=<?= (int) $ticket['id'] ?>">Ver detalhes</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php renderFooter(); ?>