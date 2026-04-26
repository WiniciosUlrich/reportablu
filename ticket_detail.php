<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

// Controller de detalhe: regras ficam na camada Application, aqui so ha orquestracao da tela.
$ticketFacade = \ReportaBlu\Application\AppFactory::ticketFacade(db(), __DIR__);
$ticketId = (int) ($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    setFlash('error', 'Chamado invalido.');
    redirect('dashboard.php');
}

$validStatuses = $ticketFacade->validStatuses();
$departments = $ticketFacade->departments();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    try {
        // Salvar geral: um submit unico para manter UX simples e fluxo consistente.
        $currentDetail = $ticketFacade->ticketDetail($ticketId, currentUserId(), isAdmin());
        if ($currentDetail === null) {
            throw new \ReportaBlu\Application\Exceptions\NotFoundException('Chamado nao encontrado.');
        }

        $currentTicket = $currentDetail['ticket'];
        $currentAssignment = $currentDetail['assignment'];

        $newStatus = trim((string) ($_POST['status'] ?? ''));
        $statusNote = (string) ($_POST['nota'] ?? '');
        $newDepartment = trim((string) ($_POST['setor'] ?? ''));
        $departmentNote = (string) ($_POST['nota_setor'] ?? '');
        $responseMessage = trim((string) ($_POST['resposta'] ?? ''));

        $savedOperations = [];

        if ($newStatus !== '' && $newStatus !== (string) $currentTicket['status']) {
            // Atualiza status apenas quando ha mudanca real.
            $ticketFacade->updateStatus(
                $ticketId,
                $newStatus,
                $statusNote,
                isAdmin()
            );

            $savedOperations[] = 'status';
        }

        $currentDepartment = $currentAssignment !== null
            ? (string) ($currentAssignment['department'] ?? '')
            : '';

        $shouldAssignDepartment = $newDepartment !== '' && (
            $newDepartment !== $currentDepartment
            || trim($departmentNote) !== ''
        );

        if ($shouldAssignDepartment) {
            // Encaminhamento opcional no mesmo submit geral.
            $ticketFacade->assignDepartment(
                $ticketId,
                $newDepartment,
                $departmentNote,
                (int) currentUserId(),
                isAdmin()
            );

            $savedOperations[] = 'setor';
        }

        if ($responseMessage !== '') {
            // Resposta opcional no mesmo submit geral.
            $ticketFacade->addResponse(
                $ticketId,
                (int) currentUserId(),
                currentUserName(),
                $responseMessage,
                isAdmin()
            );

            $savedOperations[] = 'resposta';
        }

        if ($savedOperations === []) {
            setFlash('info', 'Nenhuma alteracao para salvar.');
        } elseif (count($savedOperations) === 1) {
            $onlyOperation = $savedOperations[0];

            if ($onlyOperation === 'status') {
                setFlash('success', 'Status do chamado atualizado.');
            } elseif ($onlyOperation === 'setor') {
                setFlash('success', 'Chamado encaminhado para o setor responsavel.');
            } else {
                setFlash('success', 'Resposta adicionada ao chamado.');
            }
        } else {
            setFlash('success', 'Alteracoes salvas com sucesso.');
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
        <form class="form-grid status-form admin-edit-form" method="post" action="ticket_detail.php?id=<?= (int) $ticket['id'] ?>" data-status-form>
            <h2>Editar chamado</h2>
            <label for="status">Novo status</label>
            <select id="status" name="status" required>
                <?php foreach ($validStatuses as $statusOption): ?>
                    <option value="<?= h($statusOption) ?>" <?= $statusOption === $ticket['status'] ? 'selected' : '' ?>>
                        <?= h(statusLabel($statusOption)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="nota">Observacao da atualizacao de status</label>
            <textarea id="nota" name="nota" rows="3" placeholder="Opcional"></textarea>

            <label for="setor">Setor responsavel</label>
            <select id="setor" name="setor">
                <option value="" <?= $assignment === null ? 'selected' : '' ?>>Nao alterar setor</option>
                <?php foreach ($departments as $departmentCode => $departmentLabel): ?>
                    <option value="<?= h((string) $departmentCode) ?>" <?= $assignment !== null && (string) $assignment['department'] === (string) $departmentCode ? 'selected' : '' ?>>
                        <?= h((string) $departmentLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="nota_setor">Observacao do encaminhamento</label>
            <textarea id="nota_setor" name="nota_setor" rows="3" placeholder="Opcional"></textarea>

            <label for="resposta">Mensagem para o cidadao</label>
            <textarea id="resposta" name="resposta" rows="4" minlength="5" placeholder="Opcional"></textarea>

            <button class="btn btn-primary" type="submit">Salvar geral</button>
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
