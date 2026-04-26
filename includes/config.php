<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload simples para manter organizacao em modulos sem framework externo.
// A camada de UI importa apenas config/layout e as classes sao carregadas sob demanda.
spl_autoload_register(static function (string $className): void {
    $prefix = 'ReportaBlu\\';

    if (strncmp($className, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $filePath = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($filePath)) {
        require_once $filePath;
    }
});

function loadEnv(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if (preg_match('/^([\'\"])(.*)\\1$/', $value, $matches) === 1) {
            $value = $matches[2];
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

loadEnv(__DIR__ . '/../.env');

$timezone = env('APP_TIMEZONE', 'America/Sao_Paulo');
if ($timezone !== null) {
    date_default_timezone_set($timezone);
}

function db(): PDO
{
    // Instancia unica de PDO por request para consistencia e menor overhead.
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $dbName = env('DB_NAME', 'reportablu');
    $charset = env('DB_CHARSET', 'utf8mb4');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');

    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbName . ';charset=' . $charset;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, (string) $user, (string) $pass, $options);
    } catch (PDOException $exception) {
        http_response_code(500);
        exit('Erro ao conectar ao banco de dados. Verifique o arquivo .env e o SQL. Detalhe: ' . $exception->getMessage());
    }

    return $pdo;
}