<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

// Controller de consulta: compoe filtros e delega leitura para a Facade.
$ticketFacade = \ReportaBlu\Application\AppFactory::ticketFacade(db(), __DIR__);

$dashboardData = $ticketFacade->dashboardData($_GET, currentUserId(), isAdmin());
$categories = $dashboardData['categories'] ?? [];
$stats = $dashboardData['stats'] ?? [];
$tickets = $dashboardData['tickets'] ?? [];
$validStatuses = $dashboardData['valid_statuses'] ?? [];
$activeFilters = $dashboardData['active_filters'] ?? [];
$charts = $dashboardData['charts'] ?? [];
$recentUpdates = $dashboardData['recent_updates'] ?? [];

$search = (string) ($activeFilters['search'] ?? '');
$statusFilter = (string) ($activeFilters['status'] ?? '');
$categoryFilter = (int) ($activeFilters['category_id'] ?? 0);
$districtFilter = (string) ($activeFilters['district'] ?? '');

$statusChart = $charts['status'] ?? [];
$categoryChart = $charts['category'] ?? [];
$responsibleChart = $charts['responsible'] ?? [];
$chartMax = max(
    1,
    ...array_map(
        static fn (array $chartItem): int => (int) ($chartItem['value'] ?? 0),
        array_merge($statusChart, $categoryChart, $responsibleChart)
    )
);

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

        <input
            type="text"
            name="bairro"
            placeholder="Filtrar por bairro"
            value="<?= h($districtFilter) ?>"
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

    <div class="chart-grid">
        <article class="chart-card">
            <h3>Status</h3>
            <div id="chart-status" class="chart-canvas"></div>
        </article>

        <article class="chart-card">
            <h3>Categoria</h3>
            <div id="chart-category" class="chart-canvas"></div>
        </article>

        <article class="chart-card">
            <h3>Responsavel</h3>
            <div id="chart-responsible" class="chart-canvas"></div>
        </article>
    </div>

    <?php if (!isAdmin()): ?>
        <section class="panel update-panel">
            <h2>Atualizacoes recentes dos meus chamados</h2>
            <?php if (count($recentUpdates) === 0): ?>
                <p class="empty-state">Sem atualizacoes recentes.</p>
            <?php endif; ?>
            <?php if (count($recentUpdates) > 0): ?>
                <ul class="update-list">
                    <?php foreach ($recentUpdates as $update): ?>
                        <li>
                            <div class="card-top">
                                <strong>Chamado #<?= (int) ($update['ticket_id'] ?? 0) ?> - <?= h((string) ($update['titulo'] ?? '')) ?></strong>
                                <small><?= formatDateTime((string) ($update['created_at'] ?? '')) ?></small>
                            </div>
                            <span class="status-badge <?= h(statusClass((string) ($update['status'] ?? 'aberto'))) ?>">
                                <?= h(statusLabel((string) ($update['status'] ?? 'aberto'))) ?>
                            </span>
                            <p><?= h((string) ($update['message'] ?? 'Atualizacao registrada.')) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="table-wrapper">
        <table class="ticket-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Titulo</th>
                    <th>Categoria</th>
                    <th>Localizacao</th>
                    <th>Responsavel</th>
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
                        <td><?= h((string) $ticket['titulo']) ?></td>
                        <td><?= h((string) $ticket['categoria']) ?></td>
                        <td><?= h((string) $ticket['localizacao']) ?></td>
                        <td>
                            <?php $responsavel = trim((string) ($ticket['responsavel'] ?? '')); ?>
                            <?= h($responsavel === '' ? 'Nao atribuido' : departmentLabel($responsavel)) ?>
                        </td>
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

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    // Dados vindos do servidor (array de {label, value})
    const STATUS_CHART = <?php echo json_encode($statusChart, JSON_UNESCAPED_UNICODE); ?> || [];
    const CATEGORY_CHART = <?php echo json_encode($categoryChart, JSON_UNESCAPED_UNICODE); ?> || [];
    const RESPONSIBLE_CHART = <?php echo json_encode($responsibleChart, JSON_UNESCAPED_UNICODE); ?> || [];

    function toDataTable(rows, labelKey = 'label') {
        const data = new google.visualization.DataTable();
        data.addColumn('string', 'Label');
        data.addColumn('number', 'Valor');
        const formatted = rows.map(r => [String(r[labelKey] ?? '-'), Number(r.value ?? 0)]);
        data.addRows(formatted);
        return data;
    }

    function drawCharts() {
        // Status: pie chart
        const statusData = toDataTable(STATUS_CHART);
        const statusOptions = {height: 200, legend: {position: 'right'}, pieHole: 0.32, chartArea: {width: '70%'}};
        const statusChart = new google.visualization.PieChart(document.getElementById('chart-status'));
        statusChart.draw(statusData, statusOptions);

        // Category: bar chart
        const categoryData = toDataTable(CATEGORY_CHART);
        const categoryOptions = {height: 200, legend: {position: 'none'}, chartArea: {width: '70%'}};
        const categoryChart = new google.visualization.BarChart(document.getElementById('chart-category'));
        categoryChart.draw(categoryData, categoryOptions);

        // Responsible: bar chart
        const responsibleData = toDataTable(RESPONSIBLE_CHART);
        const responsibleOptions = {height: 200, legend: {position: 'none'}, chartArea: {width: '70%'}};
        const responsibleChart = new google.visualization.BarChart(document.getElementById('chart-responsible'));
        responsibleChart.draw(responsibleData, responsibleOptions);
    }

    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(function() {
        drawCharts();
    });

    // Redesenha os charts ao redimensionar (debounced)
    let resizeTimer = null;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (typeof google !== 'undefined' && google.visualization) {
                drawCharts();
            }
        }, 200);
    });
</script>