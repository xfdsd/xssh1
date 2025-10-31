<?php
if (!file_exists(__DIR__ . '/../config/config.php')) {
    header('Location: /install/index.php');
    exit;
}

$config = include __DIR__ . '/../config/config.php';

if (!is_array($config)) {
    die('Invalid configuration file. Please re-run the installer.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db()
{
    static $connection;
    global $config;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $connection = @new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name'],
        (int)($config['db_port'] ?? 3306)
    );

    if ($connection->connect_errno) {
        die('Database connection failed: ' . htmlspecialchars($connection->connect_error));
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function is_service_logged_in(): bool
{
    return isset($_SESSION['service_logged_in']) && $_SESSION['service_logged_in'] === true;
}

function require_service(): void
{
    if (!is_service_logged_in()) {
        header('Location: /service/login.php');
        exit;
    }
}

function h($value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

