<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireLogin();

$pdo = db();

$errors = [];
$title = '';
$description = '';
$location = '';
$categoryId = 0;

$categoriesStmt = $pdo->query('SELECT id, nome FROM categories ORDER BY nome');
$categories = $categoriesStmt->fetchAll();
$validCategoryIds = array_map('intval', array_column($categories, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['titulo'] ?? ''));
    $description = trim((string) ($_POST['descricao'] ?? ''));
    $location = trim((string) ($_POST['localizacao'] ?? ''));
    $categoryId = (int) ($_POST['categoria'] ?? 0);

    if ($title === '') {
        $errors[] = 'Informe um titulo para o chamado.';
    }

    if (strlen($description) < 15) {
        $errors[] = 'A descricao deve ter ao menos 15 caracteres.';
    }

    if ($location === '') {
        $errors[] = 'Informe a localizacao.';
    }

    if (!in_array($categoryId, $validCategoryIds, true)) {
        $errors[] = 'Selecione uma categoria valida.';
    }

    if (count($errors) === 0) {
        $uploadedAbsolutePaths = [];

        try {
            $pdo->beginTransaction();

            $insertTicketStmt = $pdo->prepare(
                'INSERT INTO tickets (user_id, category_id, titulo, descricao, localizacao, status, created_at, updated_at)
                 VALUES (:user_id, :category_id, :titulo, :descricao, :localizacao, :status, NOW(), NOW())'
            );

            $insertTicketStmt->execute([
                'user_id' => currentUserId(),
                'category_id' => $categoryId,
                'titulo' => $title,
                'descricao' => $description,
                'localizacao' => $location,
                'status' => 'aberto',
            ]);

            $ticketId = (int) $pdo->lastInsertId();

            $insertHistoryStmt = $pdo->prepare(
                'INSERT INTO ticket_status_history (ticket_id, status, note, created_at)
                 VALUES (:ticket_id, :status, :note, NOW())'
            );

            $insertHistoryStmt->execute([
                'ticket_id' => $ticketId,
                'status' => 'aberto',
                'note' => 'Chamado criado pelo morador.',
            ]);

            if (isset($_FILES['arquivos']) && is_array($_FILES['arquivos']['name'])) {
                $uploadDirectory = __DIR__ . '/uploads';
                if (!is_dir($uploadDirectory)) {
                    if (!mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                        throw new RuntimeException('Nao foi possivel criar a pasta de uploads.');
                    }
                }

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                $maxFileSize = 5 * 1024 * 1024;

                $insertFileStmt = $pdo->prepare(
                    'INSERT INTO ticket_files (ticket_id, original_name, file_path, mime_type, file_size, uploaded_at)
                     VALUES (:ticket_id, :original_name, :file_path, :mime_type, :file_size, NOW())'
                );

                $fileCount = count($_FILES['arquivos']['name']);
                for ($index = 0; $index < $fileCount; $index++) {
                    $originalName = (string) ($_FILES['arquivos']['name'][$index] ?? '');
                    $uploadError = (int) ($_FILES['arquivos']['error'][$index] ?? UPLOAD_ERR_NO_FILE);

                    if ($uploadError === UPLOAD_ERR_NO_FILE || $originalName === '') {
                        continue;
                    }

                    if ($uploadError !== UPLOAD_ERR_OK) {
                        $errors[] = 'Falha no upload do arquivo ' . $originalName . '.';
                        continue;
                    }

                    $fileSize = (int) ($_FILES['arquivos']['size'][$index] ?? 0);
                    if ($fileSize > $maxFileSize) {
                        $errors[] = 'O arquivo ' . $originalName . ' excede o limite de 5MB.';
                        continue;
                    }

                    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
                    if (!in_array($extension, $allowedExtensions, true)) {
                        $errors[] = 'Extensao nao permitida para o arquivo ' . $originalName . '.';
                        continue;
                    }

                    $tmpName = (string) ($_FILES['arquivos']['tmp_name'][$index] ?? '');
                    $safeFileName = bin2hex(random_bytes(18)) . '.' . $extension;
                    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $safeFileName;

                    if (!move_uploaded_file($tmpName, $destination)) {
                        $errors[] = 'Nao foi possivel salvar o arquivo ' . $originalName . '.';
                        continue;
                    }

                    $uploadedAbsolutePaths[] = $destination;

                    $mimeType = function_exists('mime_content_type')
                        ? (string) mime_content_type($destination)
                        : 'application/octet-stream';

                    $insertFileStmt->execute([
                        'ticket_id' => $ticketId,
                        'original_name' => $originalName,
                        'file_path' => 'uploads/' . $safeFileName,
                        'mime_type' => $mimeType,
                        'file_size' => $fileSize,
                    ]);
                }

                if (count($errors) > 0) {
                    throw new RuntimeException('Falha na validacao de anexos.');
                }
            }

            $pdo->commit();
            setFlash('success', 'Chamado aberto com sucesso. Voce pode acompanhar pelo painel.');
            redirect('dashboard.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            foreach ($uploadedAbsolutePaths as $uploadedPath) {
                if (is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }
            }

            if (count($errors) === 0) {
                $errors[] = 'Nao foi possivel registrar o chamado agora. Tente novamente.';
            }
        }
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