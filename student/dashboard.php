<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('student');

$user_id = $_SESSION['user_id'];

// Get student record
$stmt = $conn->prepare("SELECT s.id, s.roll_number, c.class_name, c.section FROM students s JOIN classes c ON s.class_id=c.id WHERE s.user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "<p style='padding:40px;font-family:sans-serif'>Student record not found. Contact admin.</p>";
    exit();
}

$student_id = $student['id'];

// Get all results
$res = $conn->prepare("
    SELECT sub.subject_name, sub.total_marks, r.marks, r.exam_type
    FROM results r
    JOIN subjects sub ON r.subject_id = sub.id
    WHERE r.student_id = ?
    ORDER BY sub.subject_name, r.exam_type
");
$res->bind_param("i", $student_id);
$res->execute();
$results = $res->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate overall stats
$total_obtained = 0; $total_possible = 0; $count = 0;
foreach ($results as $r) {
    if ($r['exam_type'] === 'Final') {   // Use Final exam for GPA calc
        $total_obtained += $r['marks'];
        $total_possible += $r['total_marks'];
        $count++;
    }
}
$overall_pct   = $total_possible > 0 ? round(($total_obtained / $total_possible) * 100, 1) : 0;
$overall_grade = getGrade($overall_pct);

// Group results by subject
$by_subject = [];
foreach ($results as $r) {
    $by_subject[$r['subject_name']][] = $r;
}

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">My Results</h1>
    <p class="page-subtitle"><?= htmlspecialchars($student['class_name']) ?> — Section <?= htmlspecialchars($student['section']) ?></p>
  </div>
  <button onclick="window.print()" class="btn btn-secondary">🖨 Print Results</button>
</div>

<!-- Student Info + Overall -->
<div class="card result-header" style="display:block">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <div class="result-student-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
      <div class="result-meta" style="margin-top:8px">
        <span>Roll No: <?= htmlspecialchars($student['roll_number']) ?></span>
        <span><?= htmlspecialchars($student['class_name']) ?></span>
        <span>Section <?= htmlspecialchars($student['section']) ?></span>
      </div>
    </div>
    <div style="text-align:right">
      <div style="font-size:2.5rem;font-weight:700;font-family:'DM Mono',monospace;color:<?= $overall_grade['color'] ?>;line-height:1">
        <?= $overall_grade['grade'] ?>
      </div>
      <div style="font-size:.8rem;color:var(--text-3);margin-top:4px">Overall Grade</div>
    </div>
  </div>

  <!-- Summary Bar -->
  <div class="result-summary">
    <div class="summary-item">
      <div class="summary-val mono"><?= $overall_pct ?>%</div>
      <div class="summary-lbl">Overall %</div>
    </div>
    <div class="summary-item">
      <div class="summary-val mono"><?= $total_obtained ?>/<?= $total_possible ?></div>
      <div class="summary-lbl">Final Marks</div>
    </div>
    <div class="summary-item">
      <div class="summary-val"><?= $overall_grade['label'] ?></div>
      <div class="summary-lbl">Status</div>
    </div>
    <div class="summary-item">
      <div class="summary-val mono"><?= $count ?></div>
      <div class="summary-lbl">Subjects</div>
    </div>
  </div>

  <!-- Overall Progress -->
  <div style="margin-top:8px">
    <div style="font-size:.75rem;color:var(--text-3);margin-bottom:6px">Overall Performance</div>
    <div class="progress-bar" style="height:10px">
      <div class="progress-fill" data-width="<?= $overall_pct ?>" style="background:<?= $overall_grade['color'] ?>"></div>
    </div>
  </div>
</div>

<!-- Results by Subject -->
<?php if (empty($results)): ?>
  <div class="card">
    <div class="no-data">No results published yet. Check back later.</div>
  </div>
<?php else: ?>

  <?php foreach ($by_subject as $subject_name => $exams): ?>
  <div class="card" style="margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <div class="card-title" style="margin:0"><?= htmlspecialchars($subject_name) ?></div>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Exam Type</th><th>Marks Obtained</th><th>Total Marks</th><th>Percentage</th><th>Grade</th></tr>
        </thead>
        <tbody>
          <?php foreach ($exams as $exam):
            $pct = round(($exam['marks'] / $exam['total_marks']) * 100, 1);
            $g   = getGrade($pct);
          ?>
          <tr>
            <td><span class="badge badge-blue"><?= $exam['exam_type'] ?></span></td>
            <td class="mono"><strong><?= $exam['marks'] ?></strong></td>
            <td class="mono text-muted"><?= $exam['total_marks'] ?></td>
            <td>
              <div class="flex items-center gap-2">
                <span class="mono" style="min-width:42px"><?= $pct ?>%</span>
                <div class="progress-bar" style="width:100px">
                  <div class="progress-fill" data-width="<?= $pct ?>" style="background:<?= $g['color'] ?>"></div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge" style="background:<?= $g['color'] ?>18;color:<?= $g['color'] ?>;border:1px solid <?= $g['color'] ?>30">
                <?= $g['grade'] ?> — <?= $g['label'] ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

<?php endif; ?>

<style>
@media print {
  .sidebar, .sidebar-logout, .btn, .page-header .btn { display: none !important; }
  .main-content { margin-left: 0 !important; padding: 20px !important; }
  .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<?php include '../includes/footer.php'; ?>