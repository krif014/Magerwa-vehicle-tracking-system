<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirect_if_authenticated();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $names = clean_string($_POST['names'] ?? '');
    $email = strtolower(clean_string($_POST['email'] ?? ''));
    $phone = clean_string($_POST['phone'] ?? '');
    $nationalId = clean_string($_POST['national_id'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($names === '') $errors[] = 'Names are required.';
    if (!valid_email_address($email)) $errors[] = 'A valid email address is required.';
    if (!valid_phone_number($phone)) $errors[] = 'Phone number must contain 7 to 15 digits, may start with +, and may use spaces or hyphens between digits.';
    if (!valid_national_id($nationalId)) $errors[] = 'National ID must be 8 to 30 digits.';
    if (!valid_password($password)) $errors[] = 'Password must be at least 8 characters and include uppercase, lowercase, and a number.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO admins (names, email, phone, national_id, password_hash) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$names, $email, $phone, $nationalId, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['admin_id'] = (int) db()->lastInsertId();
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            $errors[] = 'Email or national ID is already registered.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Sign Up - Magerwa</title>
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
            <p class="mb-3">Vehicle Tracking Management System</p>
            <div class="d-flex gap-3">
                <span class="auth-mini-icon"><i class="bi bi-shield-check"></i></span>
                <p class="mb-0 text-white-50">Create an administrator account to manage vehicles, clients, and warehouse operations.</p>
            </div>
        </div>
        <span class="auth-location"><i class="bi bi-geo-alt-fill"></i>Kigali, Rwanda</span>
    </section>
    <section class="auth-main">
        <div class="auth-panel">
            <span class="auth-icon"><i class="bi bi-person-plus"></i></span>
            <h1 class="h3 mb-2">Create Admin Account</h1>
            <p class="text-center mb-4">Fill in the details below to get started.</p>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endforeach; ?>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="col-12">
                    <label class="form-label">Full Names</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="names" class="form-control" placeholder="Enter your full names" value="<?= e($_POST['names'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address" value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="tel" name="phone" class="form-control" placeholder="0788000000" pattern="\+?[0-9][0-9 -]{5,18}[0-9]" value="<?= e($_POST['phone'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">National ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                        <input type="text" name="national_id" class="form-control" placeholder="Enter national ID number" minlength="8" maxlength="30" pattern="[0-9]{8,30}" inputmode="numeric" value="<?= e($_POST['national_id'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Create password" required minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}">
                    </div>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-primary btn-lg"><i class="bi bi-person-plus me-1"></i>Create Admin Account</button>
                </div>
            </form>
            <div class="auth-note mt-4">
                <span><i class="bi bi-shield-check"></i> protected platform</span>
                <p class="mb-0">Only authenticated admins can access MAGERWA APIs and warehouse data.</p>
            </div>
            <p class="mt-4 mb-0 text-center text-secondary">Already have an account? <a href="login.php">Sign in as an administrator</a></p>
        </div>
    </section>
</main>
</body>
</html>
