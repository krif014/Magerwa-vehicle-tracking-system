<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, names, email, phone, national_id, created_at FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    return $admin ?: null;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }

    return $admin;
}

function redirect_if_authenticated(): void
{
    if (current_admin()) {
        header('Location: index.php');
        exit;
    }
}

