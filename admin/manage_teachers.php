<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('admin');

// ── DELETE ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $del = $conn->prepare("DELETE FROM users WHERE id=? AND role='teacher'");
    $del->bind_param("i", $id); $del->execute();
    flashMessage('success', 'Teacher removed.');
    redirect('/student-result-system/admin/manage_teachers.php');
}

// ── ADD ──────────────────────────────────────────────
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = sanitize($_POST['email']     ?? '');
    $password  = $_POST['password']           ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        $form_error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $form_error = 'Password must be at least 6 characters.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param("s", $email); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $form_error = 'Email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins    = $conn->prepare("INSERT INTO users (full_name,email,password,role) VALUES (?,?,?,'teacher')");
            $ins->bind_param("sss", $full_name, $email, $hashed);
            $ins->execute();
            flashMessage('success', "Teacher '$full_name' added.");
            redirect('/student-result-system/admin/manage_teachers.php');
        }
    }
}

$teachers = $conn->query("SELECT * FROM users WHERE role='teacher' ORDER BY full_name");

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manage Teachers</h1>
    <p class="page-subtitle">Add and manage teacher accounts</p>
  </div>
</div>

<div class="card">
  <div class="card-title">Add New Teacher</div>
  <?php if ($form_error): ?>
    <div class="flash-msg" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:16px"><?= $form_error ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Ms. Sara Khan" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="sara@school.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 characters" required>
      </div>
    </div>
    <div class="mt-4">
      <button type="submit" class="btn btn-primary">Add Teacher</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-title">All Teachers</div>
  <div class="table-wrapper">
    <?php if ($teachers->num_rows > 0): ?>
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Added</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while ($row = $teachers->fetch_assoc()): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($row['email']) ?></td>
          <td class="text-muted text-sm"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
          <td>
            <a href="?delete=<?= $row['id'] ?>"
               class="btn btn-sm btn-danger"
               data-confirm="Remove <?= htmlspecialchars($row['full_name']) ?>?">Remove</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-data">No teachers yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>