<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('admin');

// Stats
$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$total_teachers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='teacher'")->fetch_assoc()['c'];
$total_subjects = $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'];
$total_classes  = $conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'];

// Recent results
$recent_results = $conn->query("
    SELECT u.full_name, s.roll_number, sub.subject_name, r.marks, sub.total_marks, r.exam_type, r.created_at
    FROM results r
    JOIN students s   ON r.student_id = s.id
    JOIN users u      ON s.user_id    = u.id
    JOIN subjects sub ON r.subject_id = sub.id
    ORDER BY r.created_at DESC
    LIMIT 8
");

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
  </div>
  <a href="/student-result-system/admin/manage_students.php" class="btn btn-primary">+ Add Student</a>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Students</div>
    <div class="stat-value"><?= $total_students ?></div>
    <div class="stat-sub">Enrolled</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Teachers</div>
    <div class="stat-value"><?= $total_teachers ?></div>
    <div class="stat-sub">Active</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Subjects</div>
    <div class="stat-value"><?= $total_subjects ?></div>
    <div class="stat-sub">Across all classes</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Classes</div>
    <div class="stat-value"><?= $total_classes ?></div>
    <div class="stat-sub">Active</div>
  </div>
</div>

<!-- Recent Results -->
<div class="card">
  <div class="card-title">Recent Results Entered</div>
  <div class="table-wrapper">
    <?php if ($recent_results->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Roll No.</th>
          <th>Subject</th>
          <th>Exam</th>
          <th>Marks</th>
          <th>%</th>
          <th>Grade</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $recent_results->fetch_assoc()):
          $pct   = round(($row['marks'] / $row['total_marks']) * 100, 1);
          $grade = getGrade($pct);
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
          <td><span class="mono"><?= htmlspecialchars($row['roll_number']) ?></span></td>
          <td><?= htmlspecialchars($row['subject_name']) ?></td>
          <td><span class="badge badge-blue"><?= $row['exam_type'] ?></span></td>
          <td class="mono"><?= $row['marks'] ?>/<?= $row['total_marks'] ?></td>
          <td class="mono"><?= $pct ?>%</td>
          <td>
            <span class="badge" style="background:<?= $grade['color'] ?>18;color:<?= $grade['color'] ?>;border:1px solid <?= $grade['color'] ?>30">
              <?= $grade['grade'] ?>
            </span>
          </td>
          <td class="text-muted text-sm"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-data">No results entered yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>