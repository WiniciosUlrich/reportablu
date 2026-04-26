<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

$ticketFacade = \ReportaBlu\Application\AppFactory::ticketFacade(db(), __DIR__);
$ticketId = (int) ($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    setFlash('error', 'Chamado invalido.');
    redirect('dashboard.php');
}

$validStatuses = $ticketFacade->validStatuses();
$departments = $ticketFacade->departments();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $action = trim((string) ($_POST['action'] ?? 'update_status'));

    try {
        if ($action === 'update_status') {
            $ticketFacade->updateStatus(
                $ticketId,
                (string) ($_POST['status'] ?? ''),
                (string) ($_POST['nota'] ?? ''),
                isAdmin()
            );

            setFlash('success', 'Status do chamado atualizado.');
        } elseif ($action === 'assign_department') {
            $ticketFacade->assignDepartment(
                $ticketId,
                (string) ($_POST['setor'] ?? ''),
                (string) ($_POST['nota_setor'] ?? ''),
                (int) currentUserId(),
                isAdmin()
            );

            setFlash('success', 'Chamado encaminhado para o setor responsavel.');
        } elseif ($action === 'add_response') {
            $ticketFacade->addResponse(
                $ticketId,
                (int) currentUserId(),
                currentUserName(),
                (string) ($_POST['resposta'] ?? ''),
                isAdmin()
            );

            setFlash('success', 'Resposta adicionada ao chamado.');
        } else {
            throw new \ReportaBlu\Application\Exceptions\ValidationException(['Acao invalida.']);
        }
    } catch (\ReportaBlu\Application\Exceptions\ValidationException $exception) {
        setFlash('error', $exception->errors()[0] ?? 'Dados invalidos.');
    } catch (\ReportaBlu\Application\Exceptions\AuthorizationException | \ReportaBlu\Application\Exceptions\NotFoundException $exception) {
        setFlash('error', $exception->getMessage());
    } catch (Throwable $exception) {
        setFlash('error', 'Nao foi possivel concluir a operacao.');
    }

    redirect('ticket_detail.php?id=' . $ticketId);
}

$detailData = $ticketFacade->ticketDetail($ticketId, currentUserId(), isAdmin());
if ($detailData === null) {
    setFlash('error', 'Chamado nao encontrado ou sem permissao para visualizar.');
    redirect('dashboard.php');
}

$ticket = $detailData['ticket'];
$files = $detailData['files'];
$historyItems = $detailData['history'];
$assignment = $detailData['assignment'];
$responses = $detailData['responses'];

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
            <li><strong>Protocolo:</strong> <?= h((string) ($ticket['protocol_code'] ?? '-')) ?></li>
            <li><strong>Categoria:</strong> <?= h((string) $ticket['categoria']) ?></li>
            <li><strong>Localizacao:</strong> <?= h((string) $ticket['localizacao']) ?></li>
            <li><strong>Setor responsavel:</strong> <?= $assignment ? h(departmentLabel((string) $assignment['department'])) : 'Nao encaminhado' ?></li>
            <li><strong>Solicitante:</strong> <?= h((string) $ticket['solicitante']) ?> (<?= h((string) $ticket['solicitante_email']) ?>)</li>
            <li><strong>Ultima atualizacao:</strong> <?= formatDateTime((string) $ticket['updated_at']) ?></li>
            <li><strong>Data de solucao:</strong> <?= formatDateTime((string) $ticket['resolved_at']) ?></li>
            <?php if ($assignment !== null): ?>
                <li><strong>Encaminhado em:</strong> <?= formatDateTime((string) $assignment['assigned_at']) ?> por <?= h((string) $assignment['assigned_by_name']) ?></li>
                <?php if (trim((string) ($assignment['note'] ?? '')) !== ''): ?>
                    <li><strong>Observacao do encaminhamento:</strong> <?= h((string) $assignment['note']) ?></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <?php if (isAdmin()): ?>
        <form class="form-grid status-form" method="post" action="ticket_detail.php?id=<?= (int) $ticket['id'] ?>" data-status-form>
            <h2>Atualizar status</h2>
            <input type="hidden" name="action" value="update_status">

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

        <form class="form-grid status-form" method="post" action="ticket_detail.php?id=<?= (int) $ticket['id'] ?>">
            <h2>Encaminhar para setor</h2>
            <input type="hidden" name="action" value="assign_department">

            <label for="setor">Setor responsavel</label>
            <select id="setor" name="setor" required>
                <option value="">Selecione</option>
                <?php foreach ($departments as $departmentCode => $departmentLabel): ?>
                    <option value="<?= h((string) $departmentCode) ?>" <?= $assignment !== null && (string) $assignment['department'] === (string) $departmentCode ? 'selected' : '' ?>>
                        <?= h((string) $departmentLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="nota_setor">Observacao do encaminhamento</label>
            <textarea id="nota_setor" name="nota_setor" rows="3" placeholder="Opcional"></textarea>

            <button class="btn btn-primary" type="submit">Encaminhar chamado</button>
        </form>

        <form class="form-grid status-form" method="post" action="ticket_detail.php?id=<?= (int) $ticket['id'] ?>">
            <h2>Responder chamado</h2>
            <input type="hidden" name="action" value="add_response">

            <label for="resposta">Mensagem para o cidadao</label>
            <textarea id="resposta" name="resposta" rows="4" minlength="5" required></textarea>

            <button class="btn btn-primary" type="submit">Enviar resposta</button>
        </form>
    <?php endif; ?>

    <section class="panel">
        <h2>Respostas do atendente</h2>
        <?php if (count($responses) === 0): ?>
            <p class="empty-state">Ainda nao existem respostas registradas.</p>
        <?php endif; ?>

        <?php if (count($responses) > 0): ?>
            <ul class="response-list">
                <?php foreach ($responses as $response): ?>
                    <li class="response-card">
                        <div class="card-top">
                            <strong><?= h((string) $response['author_name']) ?></strong>
                            <small><?= formatDateTime((string) $response['created_at']) ?></small>
                        </div>
                        <p><?= nl2br(h((string) $response['message'])) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

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