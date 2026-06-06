<?php
if (Auth::check()) { Helper::redirect('index.php?m=dashboard'); }
$email = $_GET['email'] ?? $_POST['email'] ?? '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email && $token) {
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';
    if (strlen($pass1) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        if (Auth::resetPassword($email, $token, $pass1)) {
            $success = true;
        } else {
            $error = 'Invalid or expired reset token.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= PANEL_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-logo"><?= PANEL_NAME ?></h1>
        <p class="auth-sub">Set a new password</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">Password has been reset. <a href="index.php?m=login">Sign in now</a></div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Reset Password</button>
        </form>
        <?php endif; ?>
        <div class="auth-footer"><a href="index.php?m=login">Back to login</a></div>
    </div>
</div>
<script>const s=localStorage.getItem('sv-theme');if(s==='dark')document.documentElement.setAttribute('data-theme','dark');</script>
</body>
</html>
