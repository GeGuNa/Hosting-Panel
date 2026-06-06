<?php
if (Auth::check()) { Helper::redirect('index.php?m=dashboard'); }
$message = '';
$type = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $token = Auth::generateResetToken($email);
    if ($token) {
        $resetLink = PANEL_URL . '/index.php?m=reset&email=' . urlencode($email) . '&token=' . $token;
        $message = 'A password reset link has been generated. In production, this would be emailed. For now, use: ' . $resetLink;
        $type = 'success';
    } else {
        $message = 'If an account with that email exists, a reset link has been sent.';
        $type = 'info';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password - <?= PANEL_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-logo"><?= PANEL_NAME ?></h1>
        <p class="auth-sub">Recover your password</p>
        <?php if ($message): ?>
            <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Send Reset Link</button>
        </form>
        <div class="auth-footer"><a href="index.php?m=login">Back to login</a></div>
    </div>
</div>
<script>const s=localStorage.getItem('sv-theme');if(s==='dark')document.documentElement.setAttribute('data-theme','dark');</script>
</body>
</html>
