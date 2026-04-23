<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('admin');

if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $del = $conn->prepare("DELETE FROM classes WHERE id=?");
    $del->bind_param("i", $id); $del->execute();
    flashMessage('success', 'Class deleted.');
    redirect('/student-result-system/admin/manage_classes.php');
}

$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = sanitize($_POST['class_name'] ?? '');
    $section    = sanitize($_POST['section']    ?? '');
    if (empty($class_name) || empty($section)) {
        $form_error = 'Both fields are required.';
    } else {
        $ins = $conn->prepare("INSERT INTO classes (class_name,section) VALUES (?,?)");
        $ins->bind_param("ss", $class_name, $section);
        $ins->execute();
        flashMessage('success', "Class '$class_name ($section)' added.");
        redirect('/student-result-system/admin/manage_classes.php');
    }
}

$classes = $conn->query("
    SELECT c.*, COUNT(s.id) as student_count
    FROM classes c LEFT JOIN students s ON s.class_id = c.id
    GROUP BY c.id ORDER BY c.class_name
");

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manage Classes</h1>
    <p class="page-subtitle">Add and manage class sections</p>
  </div>
</div>

<div class="card">
  <div class="card-title">Add New Class</div>
  <?php if ($form_error): ?>
    <div class="flash-msg" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:16px"><?= $form_error ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-grid">
      <div class="form-group">
        <label>Class / Program Name</label>
        <input type="text" name="class_name" placeholder="BS Computer Science" required>
      </div>
      <div class="form-group">
        <label>Section</label>
        <input type="text" name="section" placeholder="A" maxlength="10" required>
      </div>
    </div>
    <div class="mt-4">
      <button type="submit" class="btn btn-primary">Add Class</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-title">All Classes</div>
  <div class="table-wrapper">
    <?php if ($classes->num_rows > 0): ?>
    <table>
      <thead>
        <tr><th>#</th><th>Class Name</th><th>Section</th><th>Students</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while ($row = $classes->fetch_assoc()): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($row['class_name']) ?></strong></td>
          <td><span class="badge badge-blue"><?= htmlspecialchars($row['section']) ?></span></td>
          <td class="mono"><?= $row['student_count'] ?></td>
          <td>
            <a href="?delete=<?= $row['id'] ?>"
               class="btn btn-sm btn-danger"
               data-confirm="Delete this class? Students and subjects linked to it will also be deleted.">Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-data">No classes yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>