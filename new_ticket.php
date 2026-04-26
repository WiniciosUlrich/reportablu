<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

$ticketFacade = \ReportaBlu\Application\AppFactory::ticketFacade(db(), __DIR__);

$errors = [];
$title = '';
$description = '';
$location = '';
$categoryId = 0;

$categories = $ticketFacade->categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['titulo'] ?? ''));
    $description = trim((string) ($_POST['descricao'] ?? ''));
    $location = trim((string) ($_POST['localizacao'] ?? ''));
    $categoryId = (int) ($_POST['categoria'] ?? 0);

    try {
        $result = $ticketFacade->createTicket($_POST, (int) currentUserId(), $_FILES['arquivos'] ?? null);

        setFlash(
            'success',
            'Chamado aberto com sucesso. Protocolo: ' . (string) $result['protocol_code'] . '.'
        );
        redirect('ticket_detail.php?id=' . (int) $result['ticket_id']);
    } catch (\ReportaBlu\Application\Exceptions\ValidationException $exception) {
        $errors = $exception->errors();
    } catch (\RuntimeException $exception) {
        $runtimeMessage = trim($exception->getMessage());
        $errors[] = $runtimeMessage !== ''
            ? $runtimeMessage
            : 'Nao foi possivel registrar o chamado agora. Tente novamente.';
    } catch (Throwable $exception) {
        $errors[] = 'Nao foi possivel registrar o chamado agora. Tente novamente.';
    }
}

renderHeader('Novo chamado');
?>

<section class="panel">
    <div class="section-head">
        <h1>Abrir chamado</h1>
        <p>Descreva o problema, informe localizacao e adicione anexos para agilizar o atendimento.</p>
    </div>

    <?php if (count($errors) > 0): ?>
        <div class="alert alert-error">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="new_ticket.php" class="form-grid" enctype="multipart/form-data">
        <label for="titulo">Titulo</label>
        <input id="titulo" name="titulo" type="text" value="<?= h($title) ?>" required>

        <label for="categoria">Categoria</label>
        <select id="categoria" name="categoria" required>
            <option value="0">Selecione</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                    <?= h((string) $category['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="localizacao">Localizacao</label>
        <input id="localizacao" name="localizacao" type="text" value="<?= h($location) ?>" required>

        <label for="descricao">Descricao</label>
        <textarea id="descricao" name="descricao" rows="6" required><?= h($description) ?></textarea>

        <label for="arquivos">Anexos (opcional)</label>
        <input id="arquivos" name="arquivos[]" type="file" multiple>
        <p class="muted" id="arquivos-preview">Nenhum arquivo selecionado.</p>
        <p class="muted">Formatos: jpg, jpeg, png, pdf, doc, docx. Limite: 5MB por arquivo.</p>

        <button class="btn btn-primary" type="submit">Enviar chamado</button>
    </form>
</section>

<?php renderFooter(); ?>