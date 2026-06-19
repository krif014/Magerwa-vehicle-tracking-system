<?php
declare(strict_types=1);

$dbConfig = [
    'host' => '127.0.0.1',
    'name' => 'magerwa_vehicle_tracking',
    'user' => 'root',
    'pass' => '',
];

$localConfigFile = __DIR__ . '/config.local.php';
if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;

    if (is_array($localConfig)) {
        $dbConfig = array_replace($dbConfig, array_intersect_key($localConfig, $dbConfig));
    }
}

define('DB_HOST', (string) $dbConfig['host']);
define('DB_NAME', (string) $dbConfig['name']);
define('DB_USER', (string) $dbConfig['user']);
define('DB_PASS', (string) $dbConfig['pass']);

unset($dbConfig, $localConfig, $localConfigFile);

session_start();

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid request token.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function clean_string(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

function valid_email_address(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 160;
}

function valid_phone_number(string $phone): bool
{
    if (!preg_match('/^\+?[0-9](?:[0-9 -]*[0-9])?$/', $phone)) {
        return false;
    }

    $digits = preg_replace('/\D+/', '', $phone);
    return strlen($digits) >= 7 && strlen($digits) <= 15;
}

function valid_national_id(string $nationalId): bool
{
    return (bool) preg_match('/^[0-9]{8,30}$/', $nationalId);
}

function valid_password(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

function valid_chassis_number(string $chassis): bool
{
    return (bool) preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $chassis);
}

function valid_plate_number(string $plate): bool
{
    return (bool) preg_match('/^R[A-Z]{2} [0-9]{3} [A-Z]$/', $plate);
}

function valid_year(int $year): bool
{
    return $year >= 1901 && $year <= (int) date('Y');
}

function valid_price(float $price): bool
{
    return $price > 0 && $price <= 9999999999999.99;
}
