<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin('teacher');

$filter_class   = (int)($_GET['class_id']   ?? 0);
$filter_student = (int)($_GET['student_id'] ?? 0);

$where = ["1=1"]; $params = []; $types = "";
if ($filter_class)   { $where[] = "s.class_id=?";    $params[] = $filter_class;   $types .= "i"; }
if ($filter_student) { $where[] = "r.student_id=?";  $params[] = $filter_student; $types .= "i"; }
$where_sql = implode(" AND ", $where);

$sql = "
    SELECT u.full_name, s.roll_number, sub.subject_name, sub.total_marks,
           r.marks, r.exam_type, c.class_name, c.section
    FROM results r
    JOIN students s   ON r.student_id = s.id
    JOIN users u      ON s.user_id    = u.id
    JOIN subjects sub ON r.subject_id = sub.id
    JOIN classes c    ON s.class_id   = c.id
    WHERE $where_sql
    ORDER BY u.full_name, sub.subject_name
";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$results = $stmt->get_result();

$classes  = $conn->query("SELECT * FROM classes ORDER BY class_name");
$students = $conn->query("SELECT s.id, u.full_name, s.roll_number FROM students s JOIN users u ON s.user_id=u.id ORDER BY u.full_name");

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">View Results</h1>
    <p class="page-subtitle">Browse all student results</p>
  </div>
</div>

<div class="card" style="padding:16px 24px">
  <form method="GET" class="flex gap-3" style="flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="min-width:200px">
      <label>Class</label>
      <select name="class_id">
        <option value="">All Classes</option>
        <?php while ($c = $classes->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>" <?= $filter_class==$c['id']?'selected':'' ?>>
            <?= htmlspecialchars($c['class_name']) ?> (<?= $c['section'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="form-group" style="min-width:200px">
      <label>Student</label>
      <select name="student_id">
        <option value="">All Students</option>
        <?php while ($st = $students->fetch_assoc()): ?>
          <option value="<?= $st['id'] ?>" <?= $filter_student==$st['id']?'selected':'' ?>>
            <?= htmlspecialchars($st['full_name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div style="display:flex;gap:8px">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="/student-result-system/teacher/view_results.php" class="btn btn-secondary">Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="flex items-center" style="justify-content:space-between;margin-bottom:16px">
    <div class="card-title" style="margin:0">Results <span class="text-muted text-sm">(<?= $results->num_rows ?> records)</span></div>
    <input type="text" id="tableSearch" placeholder="Search…" style="padding:7px 12px;border:1px solid var(--border);border-radius:7px;font-size:.83rem;width:200px">
  </div>
  <div class="table-wrapper">
    <?php if ($results->num_rows > 0): ?>
    <table>
      <thead>
        <tr><th>Student</th><th>Roll No.</th><th>Class</th><th>Subject</th><th>Exam</th><th>Marks</th><th>%</th><th>Grade</th></tr>
      </thead>
      <tbody>
        <?php while ($row = $results->fetch_assoc()):
          $pct = round(($row['marks'] / $row['total_marks']) * 100, 1);
          $g   = getGrade($pct);
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
          <td class="mono"><?= $row['roll_number'] ?></td>
          <td><?= htmlspecialchars($row['class_name']) ?> (<?= $row['section'] ?>)</td>
          <td><?= htmlspecialchars($row['subject_name']) ?></td>
          <td><span class="badge badge-blue"><?= $row['exam_type'] ?></span></td>
          <td class="mono"><?= $row['marks'] ?>/<?= $row['total_marks'] ?></td>
          <td>
            <div class="mono"><?= $pct ?>%</div>
            <div class="progress-bar" style="width:70px">
              <div class="progress-fill" data-width="<?= $pct ?>" style="background:<?= $g['color'] ?>"></div>
            </div>
          </td>
          <td>
            <span class="badge" style="background:<?= $g['color'] ?>18;color:<?= $g['color'] ?>;border:1px solid <?= $g['color'] ?>30">
              <?= $g['grade'] ?> — <?= $g['label'] ?>
            </span>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-data">No results found.</div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>