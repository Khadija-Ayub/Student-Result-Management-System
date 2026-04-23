<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('admin');

// ── DELETE ──────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    // Deleting student cascades to results (FK). User record also deleted.
    $stmt = $conn->prepare("SELECT user_id FROM students WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $r    = $stmt->get_result()->fetch_assoc();
    if ($r) {
        $conn->prepare("DELETE FROM users WHERE id=?")->execute() ;
        $del = $conn->prepare("DELETE FROM users WHERE id=?");
        $del->bind_param("i", $r['user_id']); $del->execute();
        flashMessage('success', 'Student deleted successfully.');
    }
    redirect('/student-result-system/admin/manage_students.php');
}

// ── ADD ──────────────────────────────────────────────
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = sanitize($_POST['full_name']   ?? '');
    $email       = sanitize($_POST['email']        ?? '');
    $password    = $_POST['password']              ?? '';
    $roll_number = sanitize($_POST['roll_number']  ?? '');
    $class_id    = (int)($_POST['class_id']        ?? 0);

    if (empty($full_name) || empty($email) || empty($password) || empty($roll_number) || !$class_id) {
        $form_error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $form_error = 'Password must be at least 6 characters.';
    } else {
        // Check duplicate email/roll
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param("s", $email); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $form_error = 'Email already exists.';
        } else {
            $chk2 = $conn->prepare("SELECT id FROM students WHERE roll_number=?");
            $chk2->bind_param("s", $roll_number); $chk2->execute();
            if ($chk2->get_result()->num_rows > 0) {
                $form_error = 'Roll number already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins    = $conn->prepare("INSERT INTO users (full_name,email,password,role) VALUES (?,?,?,'student')");
                $ins->bind_param("sss", $full_name, $email, $hashed);
                $ins->execute();
                $user_id = $conn->insert_id;

                $ins2 = $conn->prepare("INSERT INTO students (user_id,roll_number,class_id) VALUES (?,?,?)");
                $ins2->bind_param("isi", $user_id, $roll_number, $class_id);
                $ins2->execute();

                flashMessage('success', "Student '$full_name' added successfully.");
                redirect('/student-result-system/admin/manage_students.php');
            }
        }
    }
}

// ── FETCH ────────────────────────────────────────────
$classes  = $conn->query("SELECT * FROM classes ORDER BY class_name");
$students = $conn->query("
    SELECT s.id, s.roll_number, u.full_name, u.email, c.class_name, c.section
    FROM students s
    JOIN users u   ON s.user_id  = u.id
    JOIN classes c ON s.class_id = c.id
    ORDER BY s.roll_number
");

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Manage Students</h1>
    <p class="page-subtitle">Add, view, and remove student accounts</p>
  </div>
</div>

<!-- ADD FORM -->
<div class="card">
  <div class="card-title">Add New Student</div>
  <?php if ($form_error): ?>
    <div class="flash-msg" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:16px"><?= $form_error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Ahmed Ali" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="ahmed@school.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 characters" required>
      </div>
      <div class="form-group">
        <label>Roll Number</label>
        <input type="text" name="roll_number" placeholder="BSCS-001" value="<?= htmlspecialchars($_POST['roll_number'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Class</label>
        <select name="class_id" required>
          <option value="">— Select Class —</option>
          <?php
          $classes->data_seek(0);
          while ($c = $classes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?> (<?= $c['section'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div class="mt-4">
      <button type="submit" class="btn btn-primary">Add Student</button>
    </div>
  </form>
</div>

<!-- STUDENT LIST -->
<div class="card">
  <div class="flex items-center" style="justify-content:space-between;margin-bottom:16px">
    <div class="card-title" style="margin:0">All Students</div>
    <input type="text" id="tableSearch" placeholder="Search students…" style="padding:7px 12px;border:1px solid var(--border);border-radius:7px;font-family:inherit;font-size:.83rem;width:220px">
  </div>
  <div class="table-wrapper">
    <?php if ($students->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Roll Number</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Class</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; while ($row = $students->fetch_assoc()): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td><span class="mono"><?= htmlspecialchars($row['roll_number']) ?></span></td>
          <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($row['email']) ?></td>
          <td><?= htmlspecialchars($row['class_name']) ?> <span class="text-muted">(<?= $row['section'] ?>)</span></td>
          <td>
            <a href="/student-result-system/admin/all_results.php?student_id=<?= $row['id'] ?>"
               class="btn btn-sm btn-secondary">View Results</a>
            <a href="?delete=<?= $row['id'] ?>"
               class="btn btn-sm btn-danger"
               data-confirm="Delete <?= htmlspecialchars($row['full_name']) ?>? This also deletes their results.">Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-data">No students found. Add one above.</div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>