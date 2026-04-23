<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('teacher');

$teacher_id = $_SESSION['user_id'];

// Stats for this teacher
$total_entered = $conn->prepare("SELECT COUNT(*) as c FROM results WHERE entered_by=?");
$total_entered->bind_param("i", $teacher_id); $total_entered->execute();
$marks_entered = $total_entered->get_result()->fetch_assoc()['c'];

$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$total_subjects = $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'];

// Recent results by this teacher
$recent = $conn->prepare("
    SELECT u.full_name, s.roll_number, sub.subject_name, r.marks, sub.total_marks, r.exam_type, r.created_at
    FROM results r
    JOIN students s   ON r.student_id = s.id
    JOIN users u      ON s.user_id    = u.id
    JOIN subjects sub ON r.subject_id = sub.id
    WHERE r.entered_by = ?
    ORDER BY r.created_at DESC
    LIMIT 6
");
$recent->bind_param("i", $teacher_id);
$recent->execute();
$recent_results = $recent->get_result();

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Teacher Dashboard</h1>
    <p class="page-subtitle">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
  </div>
  <a href="/student-result-system/teacher/enter_marks.php" class="btn btn-primary">✏️ Enter Marks</a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Marks Entered</div>
    <div class="stat-value"><?= $marks_entered ?></div>
    <div class="stat-sub">By you</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Students</div>
    <div class="stat-value"><?= $total_students ?></div>
    <div class="stat-sub">In system</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Subjects</div>
    <div class="stat-value"><?= $total_subjects ?></div>
    <div class="stat-sub">Available</div>
  </div>
</div>

<div class="card">
  <div class="card-title">Recently Entered Results</div>
  <div class="table-wrapper">
    <?php if ($recent_results->num_rows > 0): ?>
    <table>
      <thead>
        <tr><th>Student</th><th>Subject</th><th>Exam</th><th>Marks</th><th>Grade</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php while ($row = $recent_results->fetch_assoc()):
          $pct = round(($row['marks'] / $row['total_marks']) * 100, 1);
          $g   = getGrade($pct);
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($row['full_name']) ?></strong>
              <br><span class="mono text-sm text-muted"><?= $row['roll_number'] ?></span></td>
          <td><?= htmlspecialchars($row['subject_name']) ?></td>
          <td><span class="badge badge-blue"><?= $row['exam_type'] ?></span></td>
          <td class="mono"><?= $row['marks'] ?>/<?= $row['total_marks'] ?></td>
          <td><span class="badge" style="background:<?= $g['color'] ?>18;color:<?= $g['color'] ?>;border:1px solid <?= $g['color'] ?>30"><?= $g['grade'] ?></span></td>
          <td class="text-muted text-sm"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-data">You have not entered any results yet. <a href="/student-result-system/teacher/enter_marks.php">Enter marks now →</a></div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>