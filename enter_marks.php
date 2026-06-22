<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('teacher'); 
$teacher_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'teacher';
$can_edit_practical = ($user_role === 'superadmin'); 
$msg = '';
$selected_dept_id = $_GET['dept_id'] ?? null;
$selected_sem_id = $_GET['semester_id'] ?? null;
$selected_subject_id = $_GET['subject_id'] ?? null;

if ($selected_subject_id && (!$selected_dept_id || !$selected_sem_id)) {
    $stmt_resolve = $pdo->prepare("SELECT department_id, semester_id FROM subjects WHERE id = ?");
    $stmt_resolve->execute([$selected_subject_id]);
    $sub_info = $stmt_resolve->fetch();
    if ($sub_info) {
        $selected_dept_id = $selected_dept_id ?? $sub_info['department_id'];
        $selected_sem_id = $selected_sem_id ?? $sub_info['semester_id'];
    }
}

$stmt_depts = $pdo->prepare("SELECT DISTINCT d.id, d.dept_name FROM departments d JOIN subjects s ON d.id = s.department_id JOIN teacher_subjects ts ON s.id = ts.subject_id WHERE ts.teacher_id = ?");
$stmt_depts->execute([$teacher_id]);
$teacher_depts = $stmt_depts->fetchAll();

$semesters = [];
if ($selected_dept_id) {
    $stmt_sems = $pdo->prepare("SELECT DISTINCT sem.id, sem.semester_name FROM semesters sem JOIN subjects s ON sem.id = s.semester_id JOIN teacher_subjects ts ON s.id = ts.subject_id WHERE s.department_id = ? AND ts.teacher_id = ?");
    $stmt_sems->execute([$selected_dept_id, $teacher_id]);
    $semesters = $stmt_sems->fetchAll();
}

$subjects = [];
if ($selected_dept_id && $selected_sem_id) {
    $stmt_subs = $pdo->prepare("SELECT s.id, s.subject_name, s.credit_hours, s.practical_credit_hours FROM subjects s JOIN teacher_subjects ts ON s.id = ts.subject_id WHERE s.department_id = ? AND s.semester_id = ? AND ts.teacher_id = ?");
    $stmt_subs->execute([$selected_dept_id, $selected_sem_id, $teacher_id]);
    $subjects = $stmt_subs->fetchAll();
}

$selected_subject = null;
$is_practical = false;
$max_mid_paper = 30; 
$max_practical = 0;  

if ($selected_subject_id && $selected_dept_id && $selected_sem_id) {
    foreach($subjects as $s) {
        if ($s['id'] == $selected_subject_id) {
            $selected_subject = $s;
            if (isset($s['practical_credit_hours']) && $s['practical_credit_hours'] > 0) {
                $is_practical = true;
                $max_mid_paper = 20;
                $max_practical = 10;
            }
            break;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_marks'])) {
    $sub_id = $_POST['subject_id'];
    $sem_id = $_POST['semester_id'];
    foreach($_POST['mid_paper'] as $st_id => $paper_val) {
        $quiz = min(5, max(0, (float)($_POST['quiz'][$st_id] ?? 0)));
        $assgn = min(5, max(0, (float)($_POST['assignment'][$st_id] ?? 0)));
        $pres = min(5, max(0, (float)($_POST['presentation'][$st_id] ?? 0)));
        $attnd = min(5, max(0, (float)($_POST['attendance'][$st_id] ?? 0)));
        $final = min(50, max(0, (float)($_POST['final_marks'][$st_id] ?? 0)));
        $paper = min($max_mid_paper, max(0, (float)$paper_val));
        
        if ($can_edit_practical) {
            $prac_val = $_POST['practical'][$st_id] ?? 0;
        } else {
            $stmt_prac = $pdo->prepare("SELECT practical_marks FROM results WHERE student_id = ? AND subject_id = ?");
            $stmt_prac->execute([$st_id, $sub_id]);
            $res = $stmt_prac->fetch();
            $prac_val = $res ? $res['practical_marks'] : 0;
        }
        $practical = min($max_practical, max(0, (float)$prac_val));
        $mid_total = $paper + $practical + $quiz + $assgn + $pres + $attnd;
        $grand_total = round($mid_total + $final);
        $grade = calculateGradeAndGPA($grand_total)['grade'];
        
        $stmt = $pdo->prepare("SELECT id FROM results WHERE student_id = ? AND subject_id = ?");
        $stmt->execute([$st_id, $sub_id]);
        if($stmt->rowCount() > 0) {
            $upd = $pdo->prepare("UPDATE results SET marks=?, mid_marks=?, final_marks=?, mid_paper_marks=?, practical_marks=?, quiz_marks=?, assignment_marks=?, presentation_marks=?, attendance_marks=?, grade=?, semester_id=? WHERE student_id=? AND subject_id=?");
            $upd->execute([$grand_total, $mid_total, $final, $paper, $practical, $quiz, $assgn, $pres, $attnd, $grade, $sem_id, $st_id, $sub_id]);
        } else {
            $ins = $pdo->prepare("INSERT INTO results (student_id, subject_id, marks, mid_marks, final_marks, mid_paper_marks, practical_marks, quiz_marks, assignment_marks, presentation_marks, attendance_marks, grade, semester_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$st_id, $sub_id, $grand_total, $mid_total, $final, $paper, $practical, $quiz, $assgn, $pres, $attnd, $grade, $sem_id]);
        }
    }
    $msg = "<div class='alert alert-success'>Marks saved successfully!</div>";
}

$students = [];
if ($selected_subject) {
    $stmt = $pdo->prepare("SELECT st.id, st.roll_no, st.name, r.marks, r.mid_marks, r.final_marks, r.mid_paper_marks, r.practical_marks, r.quiz_marks, r.assignment_marks, r.presentation_marks, r.attendance_marks FROM students st JOIN departments d ON st.department = d.dept_name LEFT JOIN results r ON st.id = r.student_id AND r.subject_id = ? WHERE d.id = ? ORDER BY st.roll_no ASC");
    $stmt->execute([$selected_subject_id, $selected_dept_id]);
    $students = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Enter Marks - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Enter Marks</h2>
            <?= $msg ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4"><select name="dept_id" class="form-select" onchange="this.form.submit()"><option value="">Department...</option><?php foreach($teacher_depts as $d): ?><option value="<?= $d['id'] ?>" <?= $selected_dept_id == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><select name="semester_id" class="form-select" onchange="this.form.submit()" <?= empty($semesters) ? 'disabled' : '' ?>><option value="">Semester...</option><?php foreach($semesters as $s): ?><option value="<?= $s['id'] ?>" <?= $selected_sem_id == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['semester_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><select name="subject_id" class="form-select" onchange="this.form.submit()" <?= empty($subjects) ? 'disabled' : '' ?>><option value="">Subject...</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $selected_subject_id == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?> (<?= $s['credit_hours'] ?>-<?= $s['practical_credit_hours'] ?>)</option><?php endforeach; ?></select></div>
                    </form>
                </div>
            </div>
            <?php if($selected_subject): ?>
            <div class="card shadow-sm border-0 p-3">
                <form method="post">
                    <input type="hidden" name="save_marks" value="1"><input type="hidden" name="subject_id" value="<?= $selected_subject['id'] ?>"><input type="hidden" name="semester_id" value="<?= $selected_sem_id ?>">
                    <table class="table table-hover">
                        <thead><tr><th>Roll</th><th>Name</th><th>Mid (<?= $max_mid_paper ?>)</th><th>Quiz (5)</th><th>Assgn (5)</th><th>Pres (5)</th><th>Attnd (5)</th><th>Mid Total</th><?php if($is_practical): ?><th>Practical (10)</th><?php endif; ?><th>Uni Final (50)</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach($students as $st): ?>
                            <tr>
                                <td><?= htmlspecialchars($st['roll_no']) ?></td><td><?= htmlspecialchars($st['name']) ?></td>
                                <td><input type="number" name="mid_paper[<?= $st['id'] ?>]" class="form-control form-control-sm mark-input" value="<?= $st['mid_paper_marks'] ?>" min="0" max="<?= $max_mid_paper ?>" step="any" oninput="updateTotal(<?= $st['id'] ?>)"></td>
                                <td><input type="number" name="quiz[<?= $st['id'] ?>]" class="form-control form-control-sm mark-input" value="<?= $st['quiz_marks'] ?>" min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"></td>
                                <td><input type="number" name="assignment[<?= $st['id'] ?>]" class="form-control form-control-sm mark-input" value="<?= $st['assignment_marks'] ?>" min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"></td>
                                <td><input type="number" name="presentation[<?= $st['id'] ?>]" class="form-control form-control-sm mark-input" value="<?= $st['presentation_marks'] ?>" min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"></td>
                                <td><input type="number" name="attendance[<?= $st['id'] ?>]" class="form-control form-control-sm mark-input" value="<?= $st['attendance_marks'] ?>" min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"></td>
                                <td><input type="text" id="mid_total_<?= $st['id'] ?>" class="form-control form-control-sm bg-light" value="<?= $st['mid_marks'] ?>" readonly></td>
                                <?php if($is_practical): ?><td><input type="number" name="practical[<?= $st['id'] ?>]" class="form-control form-control-sm <?= !$can_edit_practical ? 'bg-light' : 'mark-input' ?>" value="<?= $st['practical_marks'] ?>" min="0" max="10" step="any" oninput="updateTotal(<?= $st['id'] ?>)" <?= !$can_edit_practical ? 'readonly' : '' ?>></td><?php endif; ?>
                                <td><input type="number" name="final_marks[<?= $st['id'] ?>]" class="form-control form-control-sm bg-light" value="<?= $st['final_marks'] ?>" min="0" max="50" step="any" readonly></td>
                                <td><input type="text" id="total_<?= $st['id'] ?>" class="form-control form-control-sm bg-light fw-bold" value="<?= $st['marks'] ?>" readonly></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-success">Save All Marks</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function updateTotal(stId) {
            const get = (name, max) => {
                const el = document.querySelector(`input[name="${name}[${stId}]"]`);
                if(!el) return 0;
                let val = parseFloat(el.value) || 0;
                if(val > max) val = max; if(val < 0) val = 0; el.value = val; return val;
            };
            const paper = get('mid_paper', <?= $max_mid_paper ?>), quiz = get('quiz', 5), assgn = get('assignment', 5), pres = get('presentation', 5), attnd = get('attendance', 5), final = get('final_marks', 50), prac = get('practical', 10);
            const mid = paper + quiz + assgn + pres + attnd + prac;
            document.getElementById(`mid_total_${stId}`).value = mid;
            document.getElementById(`total_${stId}`).value = Math.round(mid + final);
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const active = document.activeElement;
                if (active.classList.contains('mark-input')) {
                    e.preventDefault();
                    const inputs = Array.from(document.querySelectorAll('.mark-input:not([readonly])'));
                    const idx = inputs.indexOf(active);
                    if (idx > -1 && idx + 1 < inputs.length) { inputs[idx + 1].focus(); inputs[idx + 1].select(); }
                }
            }
        });
    </script>
</body>
</html>