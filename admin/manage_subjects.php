<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('admin');

if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $del = $conn->prepare("DELETE FROM subjects WHERE id=?");
    $del->bind_param("i", $id); $del->execute();
    flashMessage('success', 'Subject deleted.');
    redirect('/student-result-system/admin/manage_subjects.php');
}

$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = sanitize($_POST['subject_name'] ?? '');
    $class_id     = (int)($_POST['class_id']        ?? 0);
    $total_marks  = (int)($_POST['total_marks']      ?? 100);

    if (empty($subject_name) || !$class_id) {
        $form_error = 'Subject name and class are required.';
    } else {
        $ins = $conn->prepare("INSERT INTO subjects (subject_name,class_id,total_marks) VALUES (?,?,?)");
        $ins->bind_param("sii", $subject_name, $class_id, $total_marks);
        $ins->execute();
        flashMessage('success', "Subject '$subject_name' added.");
        redirect('/student-result-system/admin/manage_subjects.php');
    }
}

$classes  = $conn->query("SELECT * FROM classes ORDER BY class_name");
$subjects = $conn->query("
    SELECT sub.*, c.class_name, c.section
    FROM subjects sub JOIN classes c ON sub.class_id = c.id
    ORDER BY c.class_name, sub.subject_name
");

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manage Subjects</h1>
    <p class="page-subtitle">Add subjects per class</p>
  </div>
</div>

<div class="card">
  <div class="card-title">Add New Subject</div>
  <?php if ($form_error): ?>
    <div class="flash-msg" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:16px"><?= $form_error ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-grid">
      <div class="form-group">
        <label>Subject Name</label>
        <input type="text" name="subject_name" placeholder="Data Structures & Algorithms" required>
      </div>
      <div class="form-group">
        <label>Class</label>
        <select name="class_id" required>
          <option value="">— Select Class —</option>
          <?php while ($c = $classes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?> (<?= $c['section'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Total Marks</label>
        <input type="number" name="total_marks" value="100" min="1" max="1000" required>
      </div>
    </div>
    <div class="mt-4">
      <button type="submit" class="btn btn-primary">Add Subject</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-title">All Subjects</div>
  <div class="table-wrapper">
    <?php if ($subjects->num_rows > 0): ?>
    <table>
      <thead>
        <tr><th>#</th><th>Subject</th><th>Class</th><th>Total Marks</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while ($row = $subjects->fetch_assoc()): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($row['subject_name']) ?></strong></td>
          <td><?= htmlspecialchars($row['class_name']) ?> <span class="text-muted">(<?= $row['section'] ?>)</span></td>
          <td class="mono"><?= $row['total_marks'] ?></td>
          <td>
            <a href="?delete=<?= $row['id'] ?>"
               class="btn btn-sm btn-danger"
               data-confirm="Delete subject '<?= htmlspecialchars($row['subject_name']) ?>'? Its results will also be deleted.">Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-data">No subjects yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>