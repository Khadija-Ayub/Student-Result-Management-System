<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Already logged in → redirect to their dashboard
if (isLoggedIn()) {
    redirect('/student-result-system/' . $_SESSION['role'] . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            redirect('/student-result-system/' . $user['role'] . '/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}

// URL error param (from requireLogin redirect)
if (isset($_GET['error'])) {
    $error = sanitize($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ResultMS — Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/student-result-system/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">

    <div class="login-logo">
      <div class="logo-icon">R</div>
      <span class="logo-text">ResultMS</span>
    </div>

    <h1 class="login-title">Welcome back</h1>
    <p class="login-sub">Sign in to access your dashboard</p>

    <?php if ($error): ?>
      <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group mb-4">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@school.com" required>
      </div>

      <div class="form-group" style="margin-bottom:20px">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:11px">
        Sign In →
      </button>
    </form>

    <div class="login-footer">
      <p style="margin-bottom:8px;color:var(--text-2);font-weight:500">Demo Accounts</p>
      <p>admin@school.com &nbsp;|&nbsp; teacher@school.com</p>
      <p>ahmed@school.com (student)</p>
      <p style="margin-top:4px">Password for all: <strong>password</strong></p>
    </div>

  </div>
</div>
</body>
</html>