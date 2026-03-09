<?php

declare(strict_types=1);

function app_config(): array
{
    static $config;
    if (!$config) {
        $config = require __DIR__ . '/../config/config.php';
        date_default_timezone_set($config['timezone'] ?? 'UTC');
    }
    return $config;
}

function base_url(string $path = ''): string
{
    $root = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    return $root . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function csrf_validate(?string $token): bool
{
    return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string) $token);
}

function rupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function request(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function create_ai_task(PDO $pdo, string $eventType, array $payload): void
{
    $config = app_config();
    if (!($config['ai']['enabled'] ?? false)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO ai_tasks (event_type, payload_json, status, created_at) VALUES (:event_type, :payload, :status, NOW())'
    );

    $stmt->execute([
        'event_type' => $eventType,
        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'status' => 'pending',
    ]);
}
