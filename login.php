<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireGuest();

$pdo = db();
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['senha'] ?? '');

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Informe um email valido.';
    }

    if ($password === '') {
        $errors[] = 'Informe sua senha.';
    }

    if (count($errors) === 0) {
        $stmt = $pdo->prepare('SELECT id, nome, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $errors[] = 'Email ou senha invalidos.';
        } else {
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'name' => (string) $user['nome'],
                'email' => (string) $user['email'],
                'role' => (string) $user['role'],
            ];

            setFlash('success', 'Bem-vindo, ' . (string) $user['nome'] . '.');
            redirect('dashboard.php');
        }
    }
}

renderHeader('Entrar');
?>

<section class="auth-shell">
    <article class="auth-card">
        <h1>Entrar no portal</h1>
        <p>Acompanhe chamados, anexos e historico de andamento.</p>

        <?php if (count($errors) > 0): ?>
            <div class="alert alert-error">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" class="form-grid">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= h($email) ?>" required>

            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" required>

            <button class="btn btn-primary" type="submit">Entrar</button>
        </form>

        <p class="muted">
            Ainda nao possui conta?
            <a href="register.php">Criar conta</a>
        </p>
    </article>
</section>

<?php renderFooter(); ?>