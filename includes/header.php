<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$role      = $_SESSION['role']      ?? '';
$full_name = $_SESSION['full_name'] ?? '';
$page      = basename($_SERVER['PHP_SELF'], '.php');

$nav = [];
if ($role === 'admin') {
    $nav = [
        ['href' => '/student-result-system/admin/dashboard.php',        'label' => 'Dashboard',  'icon' => '▦'],
        ['href' => '/student-result-system/admin/manage_students.php',  'label' => 'Students',   'icon' => '👤'],
        ['href' => '/student-result-system/admin/manage_teachers.php',  'label' => 'Teachers',   'icon' => '🎓'],
        ['href' => '/student-result-system/admin/manage_classes.php',   'label' => 'Classes',    'icon' => '🏫'],
        ['href' => '/student-result-system/admin/manage_subjects.php',  'label' => 'Subjects',   'icon' => '📚'],
        ['href' => '/student-result-system/admin/all_results.php',      'label' => 'Results',    'icon' => '📊'],
    ];
} elseif ($role === 'teacher') {
    $nav = [
        ['href' => '/student-result-system/teacher/dashboard.php',      'label' => 'Dashboard',  'icon' => '▦'],
        ['href' => '/student-result-system/teacher/enter_marks.php',    'label' => 'Enter Marks','icon' => '✏️'],
        ['href' => '/student-result-system/teacher/view_results.php',   'label' => 'View Results','icon' => '📊'],
    ];
} elseif ($role === 'student') {
    $nav = [
        ['href' => '/student-result-system/student/dashboard.php',      'label' => 'My Results', 'icon' => '📊'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> — <?= ucfirst($role) ?> Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/student-result-system/assets/css/style.css">
</head>
<body>

<div class="layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <span class="logo-icon">R</span>
      <span class="logo-text"><?= APP_NAME ?></span>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar"><?= strtoupper(substr($full_name, 0, 1)) ?></div>
      <div class="user-info">
        <span class="user-name"><?= htmlspecialchars($full_name) ?></span>
        <span class="user-role"><?= ucfirst($role) ?></span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($nav as $item): ?>
        <a href="<?= $item['href'] ?>"
           class="nav-link <?= (strpos($_SERVER['PHP_SELF'], basename($item['href'])) !== false) ? 'active' : '' ?>">
          <span class="nav-icon"><?= $item['icon'] ?></span>
          <?= $item['label'] ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <a href="/student-result-system/logout.php" class="sidebar-logout">
      ↩ Logout
    </a>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <?php showFlash(); ?>