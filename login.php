<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirect_if_authenticated();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(clean_string($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Magerwa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260620-auth" rel="stylesheet">
</head>
<body class="auth-bg">
<main class="auth-shell">
    <section class="auth-showcase">
        <div class="brand-line">
            <span class="brand-mark"><i class="bi bi-house-lock"></i></span>
            <span>
                <span class="brand-name">MAGERWA</span>
                <span class="brand-subtitle">Rwanda's Public Bonded Warehouse</span>
            </span>
        </div>
        <div class="auth-showcase-copy">
            <p class="mb-2">Vehicle Tracking Management System</p>
            <p class="mb-0 text-white-50">Secure access for authorized administrators only.</p>
        </div>
        <span class="auth-location"><i class="bi bi-geo-alt-fill"></i>Kigali, Rwanda</span>
    </section>
    <section class="auth-main">
        <div class="auth-panel">
            <span class="auth-icon"><i class="bi bi-person"></i></span>
            <h1 class="h3 mb-2">Admin Login</h1>
            <p class="text-center mb-4">Access protected cargo vehicle records.</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="admin@magerwa.rw" required autofocus>
                    </div>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <label class="form-check-label d-flex align-items-center gap-2 text-secondary">
                        <input class="form-check-input mt-0" type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                    <a href="login.php" class="text-decoration-none">Forgot password?</a>
                </div>
                <button class="btn btn-primary btn-lg mt-2"><i class="bi bi-shield-lock me-1"></i>Login</button>
            </form>
            <div class="auth-note mt-4">
                <span><i class="bi bi-shield-check"></i> secured access</span>
                <p class="mb-0">This system and its APIs are restricted to <strong>authorized administrators only.</strong></p>
            </div>
            <p class="mt-4 mb-0 text-center text-secondary">No admin account? <a href="signup.php">Create one</a></p>
        </div>
    </section>
</main>
</body>
</html>
