<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireGuest();

$pdo = db();
$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['nome'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['senha'] ?? '');
    $confirmPassword = (string) ($_POST['confirmacao_senha'] ?? '');

    if ($name === '') {
        $errors[] = 'Informe seu nome.';
    }

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Informe um email valido.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'A senha deve ter ao menos 6 caracteres.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'A confirmacao de senha nao confere.';
    }

    if (count($errors) === 0) {
        $existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existsStmt->execute(['email' => $email]);

        if ($existsStmt->fetch()) {
            $errors[] = 'Este email ja esta cadastrado.';
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO users (nome, email, password_hash, role) VALUES (:nome, :email, :password_hash, :role)'
            );

            $insertStmt->execute([
                'nome' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'morador',
            ]);

            setFlash('success', 'Conta criada com sucesso. Entre para abrir seu primeiro chamado.');
            redirect('login.php');
        }
    }
}

renderHeader('Criar conta');
?>

<section class="auth-shell">
    <article class="auth-card">
        <h1>Criar conta</h1>
        <p>Cadastre-se para registrar chamados e acompanhar os status em um unico lugar.</p>

        <?php if (count($errors) > 0): ?>
            <div class="alert alert-error">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" class="form-grid">
            <label for="nome">Nome completo</label>
            <input id="nome" name="nome" type="text" value="<?= h($name) ?>" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= h($email) ?>" required>

            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" required>

            <label for="confirmacao_senha">Confirmar senha</label>
            <input id="confirmacao_senha" name="confirmacao_senha" type="password" required>

            <button class="btn btn-primary" type="submit">Criar conta</button>
        </form>

        <p class="muted">
            Ja possui conta?
            <a href="login.php">Entrar</a>
        </p>
    </article>
</section>

<?php renderFooter(); ?>