<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

$pdo = db();
$ticketId = (int) ($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    setFlash('error', 'Chamado invalido.');
    redirect('dashboard.php');
}

$validStatuses = ['aberto', 'em_andamento', 'solucionado', 'fechado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $newStatus = trim((string) ($_POST['status'] ?? ''));
    $note = trim((string) ($_POST['nota'] ?? ''));

    if (!in_array($newStatus, $validStatuses, true)) {
        setFlash('error', 'Status invalido.');
        redirect('ticket_detail.php?id=' . $ticketId);
    }

    $currentStmt = $pdo->prepare('SELECT status, resolved_at FROM tickets WHERE id = :id LIMIT 1');
    $currentStmt->execute(['id' => $ticketId]);
    $currentTicket = $currentStmt->fetch();

    if ($currentTicket) {
        $resolvedAt = $currentTicket['resolved_at'];

        if ($newStatus === 'solucionado' && ($resolvedAt === null || $resolvedAt === '')) {
            $resolvedAt = date('Y-m-d H:i:s');
        }

        if ($newStatus === 'aberto' || $newStatus === 'em_andamento') {
            $resolvedAt = null;
        }

        $updateStmt = $pdo->prepare(
            'UPDATE tickets
             SET status = :status, resolved_at = :resolved_at, updated_at = NOW()
             WHERE id = :id'
        );

        $updateStmt->execute([
            'status' => $newStatus,
            'resolved_at' => $resolvedAt,
            'id' => $ticketId,
        ]);

        $historyStmt = $pdo->prepare(
            'INSERT INTO ticket_status_history (ticket_id, status, note, created_at)
             VALUES (:ticket_id, :status, :note, NOW())'
        );

        $historyStmt->execute([
            'ticket_id' => $ticketId,
            'status' => $newStatus,
            'note' => $note !== '' ? $note : 'Atualizacao de status pelo administrador.',
        ]);

        setFlash('success', 'Status do chamado atualizado.');
        redirect('ticket_detail.php?id=' . $ticketId);
    }
}

$params = ['id' => $ticketId];
$ticketSql = "SELECT
                t.id,
                t.user_id,
                t.titulo,
                t.descricao,
                t.localizacao,
                t.status,
                t.created_at,
                t.updated_at,
                t.resolved_at,
                c.nome AS categoria,
                u.nome AS solicitante,
                u.email AS solicitante_email
            FROM tickets t
            INNER JOIN categories c ON c.id = t.category_id
            INNER JOIN users u ON u.id = t.user_id
            WHERE t.id = :id";

if (!isAdmin()) {
    $ticketSql .= ' AND t.user_id = :user_id';
    $params['user_id'] = currentUserId();
}

$ticketStmt = $pdo->prepare($ticketSql);
$ticketStmt->execute($params);
$ticket = $ticketStmt->fetch();

if (!$ticket) {
    setFlash('error', 'Chamado nao encontrado ou sem permissao para visualizar.');
    redirect('dashboard.php');
}

$filesStmt = $pdo->prepare(
    'SELECT id, original_name, file_path, mime_type, file_size, uploaded_at
     FROM ticket_files
     WHERE ticket_id = :ticket_id
     ORDER BY uploaded_at DESC'
);
$filesStmt->execute(['ticket_id' => $ticketId]);
$files = $filesStmt->fetchAll();

$historyStmt = $pdo->prepare(
    'SELECT status, note, created_at
     FROM ticket_status_history
     WHERE ticket_id = :ticket_id
     ORDER BY created_at DESC'
);
$historyStmt->execute(['ticket_id' => $ticketId]);
$historyItems = $historyStmt->fetchAll();

renderHeader('Detalhes do chamado');
?>

<section class="panel">
    <div class="section-head">
        <h1>Chamado #<?= (int) $ticket['id'] ?> - <?= h((string) $ticket['titulo']) ?></h1>
        <p>Visualize descricao, status, anexos e historico de atualizacoes.</p>
    </div>

    <div class="ticket-card detail-card">
        <div class="card-top">
            <span class="status-badge <?= h(statusClass((string) $ticket['status'])) ?>">
                <?= h(statusLabel((string) $ticket['status'])) ?>
            </span>
            <small>Criado em <?= formatDateTime((string) $ticket['created_at']) ?></small>
        </div>

        <p><?= nl2br(h((string) $ticket['descricao'])) ?></p>

        <ul class="meta-list">
            <li><strong>Categoria:</strong> <?= h((string) $ticket['categoria']) ?></li>
            <li><strong>Localizacao:</strong> <?= h((string) $ticket['localizacao']) ?></li>
            <li><strong>Solicitante:</strong> <?= h((string) $ticket['solicitante']) ?> (<?= h((string) $ticket['solicitante_email']) ?>)</li>
            <li><strong>Ultima atualizacao:</strong> <?= formatDateTime((string) $ticket['updated_at']) ?></li>
            <li><strong>Data de solucao:</strong> <?= formatDateTime((string) $ticket['resolved_at']) ?></li>
        </ul>
    </div>

    <?php if (isAdmin()): ?>
        <form class="form-grid status-form" method="post" action="ticket_detail.php?id=<?= (int) $ticket['id'] ?>" data-status-form>
            <h2>Atualizar status</h2>

            <label for="status">Novo status</label>
            <select id="status" name="status" required>
                <?php foreach ($validStatuses as $statusOption): ?>
                    <option value="<?= h($statusOption) ?>" <?= $statusOption === $ticket['status'] ? 'selected' : '' ?>>
                        <?= h(statusLabel($statusOption)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="nota">Observacao da atualizacao</label>
            <textarea id="nota" name="nota" rows="3" placeholder="Opcional"></textarea>

            <button class="btn btn-primary" type="submit">Salvar status</button>
        </form>
    <?php endif; ?>

    <section class="panel">
        <h2>Anexos</h2>
        <?php if (count($files) === 0): ?>
            <p class="empty-state">Este chamado nao possui anexos.</p>
        <?php endif; ?>

        <?php if (count($files) > 0): ?>
            <ul class="attachments-list">
                <?php foreach ($files as $file): ?>
                    <li>
                        <a class="card-link" href="<?= h((string) $file['file_path']) ?>" target="_blank" rel="noopener">
                            <?= h((string) $file['original_name']) ?>
                        </a>
                        <small><?= h((string) $file['mime_type']) ?> - <?= h(formatBytes((int) $file['file_size'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Historico do chamado</h2>
        <?php if (count($historyItems) === 0): ?>
            <p class="empty-state">Ainda nao existem eventos registrados.</p>
        <?php endif; ?>

        <?php if (count($historyItems) > 0): ?>
            <ul class="history-list">
                <?php foreach ($historyItems as $historyItem): ?>
                    <li>
                        <span class="status-badge <?= h(statusClass((string) $historyItem['status'])) ?>">
                            <?= h(statusLabel((string) $historyItem['status'])) ?>
                        </span>
                        <strong><?= formatDateTime((string) $historyItem['created_at']) ?></strong>
                        <p><?= h((string) $historyItem['note']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</section>

<?php renderFooter(); ?>