<?php
session_start();
require_once 'db.php';

const DEBUG_MODE = true;
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

$error = '';

// Dummy credentials
$validEmail = 'nasirsura58@gmail.com';
$validPass  = '@tjtech2025';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === $validEmail && $password === $validPass) {
        // Login successful
        $_SESSION['user_id'] = 1;
        $_SESSION['email']   = $email;
        header('Location: dashboard.php');
        // Send notification email
       
        exit;
    } else {
        $error = 'Invalid email or password!';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login | Nasar & Co</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; display:flex; align-items:center; justify-content:center; height:100vh; }
        .login-box { background:#fff; padding:30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,.1); width:350px; }
    </style>
</head>
<body>
<div class="login-box">
    <h3 class="text-center mb-4">Login</h3>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off" novalidate>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Login</button>
    </form>
</div>
</body>
</html>
