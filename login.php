<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

requireGuest();

$authService = \ReportaBlu\Application\AppFactory::authService(db());
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['senha'] ?? '');

    try {
        $_SESSION['user'] = $authService->authenticate($email, $password);
        setFlash('success', 'Bem-vindo, ' . (string) $_SESSION['user']['name'] . '.');
        redirect('dashboard.php');
    } catch (\ReportaBlu\Application\Exceptions\ValidationException $exception) {
        $errors = $exception->errors();
    } catch (Throwable $exception) {
        $errors[] = 'Nao foi possivel realizar o login agora. Tente novamente.';
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