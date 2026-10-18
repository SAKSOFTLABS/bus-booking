<?php
/**
 * Administrator Login
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (AuthService::isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $loginRes = AuthService::adminLogin($username, $password);
    if ($loginRes['success']) {
        setFlash('success', 'Welcome back, Administrator!');
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    } else {
        $error = $loginRes['message'];
    }
}

$settings = getSystemSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="brand-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h4 class="fw-bold text-navy mb-1"><?= APP_NAME ?></h4>
        <p class="text-muted small">Administrator Control Portal</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (hasFlash('error')): ?>
        <div class="alert alert-danger py-2 small">
            <?= htmlspecialchars(getFlash('error')) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Enter username" value="admin" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold text-dark">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Enter password" value="admin123" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Dashboard
        </button>
    </form>

    <div class="alert alert-info py-2 small mt-4 mb-0 text-center">
        Default Demo Login: <strong>admin</strong> / <strong>admin123</strong>
    </div>

    <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/index.php" class="small text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Customer Website
        </a>
    </div>
</div>

</body>
</html>
