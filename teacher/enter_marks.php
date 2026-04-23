<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('teacher');

$teacher_id = $_SESSION['user_id'];
$form_error = '';
$form_success = '';

// ── SAVE MARKS ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $marks      = (float)($_POST['marks']    ?? -1);
    $exam_type  = sanitize($_POST['exam_type'] ?? '');

    // Validate
    $valid_exams = ['Mid','Final','Assignment','Quiz'];
    if (!$student_id || !$subject_id || !in_array($exam_type, $valid_exams)) {
        $form_error = 'Please fill all fields correctly.';
    } elseif ($marks < 0) {
        $form_error = 'Marks cannot be negative.';
    } else {
        // Check total marks for this subject
        $sub_stmt = $conn->prepare("SELECT total_marks FROM subjects WHERE id=?");
        $sub_stmt->bind_param("i", $subject_id); $sub_stmt->execute();
        $sub_row = $sub_stmt->get_result()->fetch_assoc();
        if (!$sub_row) {
            $form_error = 'Subject not found.';
        } elseif ($marks > $sub_row['total_marks']) {
            $form_error = "Marks cannot exceed total marks ({$sub_row['total_marks']}).";
        } else {
            // Upsert — update if exists, insert if not
            $upsert = $conn->prepare("
                INSERT INTO results (student_id, subject_id, marks, exam_type, entered_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE marks=VALUES(marks), entered_by=VALUES(entered_by)
            ");
            $upsert->bind_param("iidsi", $student_id, $subject_id, $marks, $exam_type, $teacher_id);
            $upsert->execute();
            flashMessage('success', 'Marks saved successfully.');
            redirect('/student-result-system/teacher/enter_marks.php');
        }
    }
}

// ── AJAX: get subjects by class ──────────────────────
if (isset($_GET['get_subjects'])) {
    $class_id = (int)$_GET['get_subjects'];
    $res = $conn->prepare("SELECT id, subject_name, total_marks FROM subjects WHERE class_id=? ORDER BY subject_name");
    $res->bind_param("i", $class_id); $res->execute();
    $rows = $res->get_result()->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit();
}

// ── AJAX: get students by class ──────────────────────
if (isset($_GET['get_students'])) {
    $class_id = (int)$_GET['get_students'];
    $res = $conn->prepare("
        SELECT s.id, u.full_name, s.roll_number
        FROM students s JOIN users u ON s.user_id=u.id
        WHERE s.class_id=? ORDER BY s.roll_number
    ");
    $res->bind_param("i", $class_id); $res->execute();
    $rows = $res->get_result()->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit();
}

$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Enter Marks</h1>
    <p class="page-subtitle">Select class, student, and subject to enter marks</p>
  </div>
</div>

<?php if ($form_error): ?>
  <div class="flash-msg" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b"><?= $form_error ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-title">Enter / Update Marks</div>
  <form method="POST" id="marksForm">
    <div class="form-grid">

      <!-- Step 1: Class -->
      <div class="form-group">
        <label>1. Select Class</label>
        <select id="classSelect" name="class_id" required>
          <option value="">— Select Class —</option>
          <?php while ($c = $classes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?> (<?= $c['section'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Step 2: Student -->
      <div class="form-group">
        <label>2. Select Student</label>
        <select id="studentSelect" name="student_id" required disabled>
          <option value="">— Select Class First —</option>
        </select>
      </div>

      <!-- Step 3: Subject -->
      <div class="form-group">
        <label>3. Select Subject</label>
        <select id="subjectSelect" name="subject_id" required disabled>
          <option value="">— Select Class First —</option>
        </select>
      </div>

      <!-- Step 4: Exam Type -->
      <div class="form-group">
        <label>4. Exam Type</label>
        <select name="exam_type" required>
          <option value="">— Select Type —</option>
          <option value="Mid">Mid Term</option>
          <option value="Final">Final Term</option>
          <option value="Assignment">Assignment</option>
          <option value="Quiz">Quiz</option>
        </select>
      </div>

      <!-- Step 5: Marks -->
      <div class="form-group">
        <label>5. Marks Obtained <span id="totalMarksLabel" class="text-muted text-sm"></span></label>
        <input type="number" name="marks" id="marksInput" step="0.5" min="0" placeholder="e.g. 78" required>
      </div>

    </div>

    <div class="mt-4">
      <button type="submit" class="btn btn-primary">Save Marks</button>
      <span class="text-muted text-sm" style="margin-left:12px">Note: If marks for this student/subject/exam already exist, they will be updated.</span>
    </div>
  </form>
</div>

<script>
const classSelect   = document.getElementById('classSelect');
const studentSelect = document.getElementById('studentSelect');
const subjectSelect = document.getElementById('subjectSelect');
const marksInput    = document.getElementById('marksInput');
const totalLbl      = document.getElementById('totalMarksLabel');

classSelect.addEventListener('change', async () => {
  const classId = classSelect.value;
  studentSelect.innerHTML = '<option value="">Loading…</option>';
  subjectSelect.innerHTML = '<option value="">Loading…</option>';
  studentSelect.disabled = true;
  subjectSelect.disabled = true;

  if (!classId) return;

  // Fetch students
  const sRes  = await fetch(`?get_students=${classId}`);
  const students = await sRes.json();
  studentSelect.innerHTML = '<option value="">— Select Student —</option>';
  students.forEach(s => {
    studentSelect.innerHTML += `<option value="${s.id}">${s.full_name} (${s.roll_number})</option>`;
  });
  studentSelect.disabled = false;

  // Fetch subjects
  const subRes  = await fetch(`?get_subjects=${classId}`);
  const subjects = await subRes.json();
  subjectSelect.innerHTML = '<option value="">— Select Subject —</option>';
  subjects.forEach(sub => {
    subjectSelect.innerHTML += `<option value="${sub.id}" data-total="${sub.total_marks}">${sub.subject_name} (/${sub.total_marks})</option>`;
  });
  subjectSelect.disabled = false;
});

subjectSelect.addEventListener('change', () => {
  const opt = subjectSelect.selectedOptions[0];
  if (opt && opt.dataset.total) {
    const total = opt.dataset.total;
    totalLbl.textContent = `(out of ${total})`;
    marksInput.max = total;
  } else {
    totalLbl.textContent = '';
    marksInput.removeAttribute('max');
  }
});
</script>

<?php include '../includes/footer.php'; ?>