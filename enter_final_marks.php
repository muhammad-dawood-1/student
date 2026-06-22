<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');
$msg = '';
$selected_dept_id = $_GET['dept_id'] ?? null;
$selected_sem_id = $_GET['semester_id'] ?? null;
$selected_subject_id = $_GET['subject_id'] ?? null;
// Fetch all Departments
$stmt_depts = $pdo->query("SELECT id, dept_name FROM departments ORDER BY dept_name");
$departments = $stmt_depts->fetchAll();
// Fetch all Semesters
$stmt_sems = $pdo->query("SELECT id, semester_name FROM semesters ORDER BY id");
$semesters = $stmt_sems->fetchAll();
// Fetch Subjects for chosen dept and semester
$subjects = [];
if ($selected_dept_id && $selected_sem_id) {
    $stmt_subs = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE department_id = ? AND semester_id = ? ORDER BY subject_name");
    $stmt_subs->execute([$selected_dept_id, $selected_sem_id]);
    $subjects = $stmt_subs->fetchAll();
}
// Default distributions
$max_mid_paper = 30;
$max_practical = 0;
$is_practical = false;
$selected_subject_name = '';
$students = [];
// Fetch subject configuration and dynamic marks rules first (needed for both POST and GET)
if ($selected_subject_id && $selected_dept_id && $selected_sem_id) {
    $stmt_sub_info = $pdo->prepare("SELECT subject_name, credit_hours, practical_credit_hours FROM subjects WHERE id = ?");
    $stmt_sub_info->execute([$selected_subject_id]);
    $subject_info = $stmt_sub_info->fetch();
    if ($subject_info) {
        $selected_subject_name = $subject_info['subject_name'];
        $th_credits = (int)$subject_info['credit_hours'];
        $pr_credits = (int)$subject_info['practical_credit_hours'];
        // Apply your rules: 3-1 credit hours gets 20 Mid & 10 Practical. Others (3-0, 2-0) get 30 Mid & 0 Practical.
        if ($th_credits === 3 && $pr_credits === 1) {
            $max_mid_paper = 20;
            $max_practical = 10;
            $is_practical = true;
        } else {
            $max_mid_paper = 30;
            $max_practical = 0;
            $is_practical = false;
        }
    }
    $stmt_dept_name = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
    $stmt_dept_name->execute([$selected_dept_id]);
    $dept_name = $stmt_dept_name->fetchColumn();
    if ($selected_subject_name) {
        $stmt = $pdo->prepare("
            SELECT st.id, st.roll_no, st.name, r.marks, r.mid_marks, r.final_marks, r.practical_marks,
                   r.mid_paper_marks, r.quiz_marks, r.assignment_marks, r.presentation_marks, r.attendance_marks
            FROM students st 
            LEFT JOIN results r ON st.id = r.student_id AND r.subject_id = ?
            WHERE st.department = ?
            ORDER BY st.roll_no ASC
        ");
        $stmt->execute([$selected_subject_id, $dept_name]);
        $students = $stmt->fetchAll();
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_marks'])) {
    if (!isSuperAdmin()) {
        $msg = "<div class='alert alert-danger'>Access Denied: Standard admins are not permitted to enter or modify marks.</div>";
    } else {
        $sub_id = $_POST['subject_id'];
        $sem_id = $_POST['semester_id'];
        
        if(isset($_POST['mid_paper']) && is_array($_POST['mid_paper'])) {
            foreach($_POST['mid_paper'] as $st_id => $paper_val) {
                $quiz = min(5, max(0, (float)($_POST['quiz'][$st_id] ?? 0)));
                $assgn = min(5, max(0, (float)($_POST['assignment'][$st_id] ?? 0)));
                $pres = min(5, max(0, (float)($_POST['presentation'][$st_id] ?? 0)));
                $attnd = min(5, max(0, (float)($_POST['attendance'][$st_id] ?? 0)));
                $paper = min($max_mid_paper, max(0, (float)$paper_val));
                
                // Superadmin inputs
                $final = min(50, max(0, (float)($_POST['final_marks'][$st_id] ?? 0)));
                $practical = min($max_practical, max(0, (float)($_POST['practical_marks'][$st_id] ?? 0)));
                $mid_total = $paper + $quiz + $assgn + $pres + $attnd;
                
                $stmt = $pdo->prepare("SELECT id FROM results WHERE student_id = ? AND subject_id = ?");
                $stmt->execute([$st_id, $sub_id]);
                $existing_res = $stmt->fetch();
                
                $grand_total = round($mid_total + $final + $practical);
                $gpa_info = calculateGradeAndGPA($grand_total);
                $grade = $gpa_info['grade'];
                
                if($existing_res) {
                    $upd = $pdo->prepare("UPDATE results SET marks=?, mid_marks=?, final_marks=?, practical_marks=?, mid_paper_marks=?, quiz_marks=?, assignment_marks=?, presentation_marks=?, attendance_marks=?, grade=?, semester_id=? WHERE student_id=? AND subject_id=?");
                    $upd->execute([$grand_total, $mid_total, $final, $practical, $paper, $quiz, $assgn, $pres, $attnd, $grade, $sem_id, $st_id, $sub_id]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO results (student_id, subject_id, marks, mid_marks, final_marks, practical_marks, mid_paper_marks, quiz_marks, assignment_marks, presentation_marks, attendance_marks, grade, semester_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$st_id, $sub_id, $grand_total, $mid_total, $final, $practical, $paper, $quiz, $assgn, $pres, $attnd, $grade, $sem_id]);
                }
            }
            $stmt = $pdo->prepare("
                SELECT st.id, st.roll_no, st.name, r.marks, r.mid_marks, r.final_marks, r.practical_marks,
                       r.mid_paper_marks, r.quiz_marks, r.assignment_marks, r.presentation_marks, r.attendance_marks
                FROM students st 
                LEFT JOIN results r ON st.id = r.student_id AND r.subject_id = ?
                WHERE st.department = ?
                ORDER BY st.roll_no ASC
            ");
            $stmt->execute([$selected_subject_id, $dept_name]);
            $students = $stmt->fetchAll();
            
            $msg = "<div class='alert alert-success'>Marks saved successfully!</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Marks - Admin SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Manage Student Marks</h2>
            <?= $msg ?>
            
            <?php if (isSuperAdmin()): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-shield-lock me-2"></i> You are logged in as <strong>Super Admin</strong>. You are responsible for entering <strong>University Final & Practical Marks</strong>. Mid-term fields are locked for you.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> You are logged in as <strong>Standard Admin</strong>. You can only view <strong>Mid-term, Practical, and University Final marks</strong>. Entering/editing marks is disabled.
                </div>
            <?php endif; ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">1. Select Department</label>
                            <select name="dept_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Choose Department...</option>
                                <?php foreach($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $selected_dept_id == $d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['dept_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">2. Select Semester</label>
                            <select name="semester_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Choose Semester...</option>
                                <?php foreach($semesters as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $selected_sem_id == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['semester_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">3. Select Subject</label>
                            <select name="subject_id" class="form-select" onchange="this.form.submit()" <?= empty($subjects) ? 'disabled' : '' ?>>
                                <option value="">Choose Subject...</option>
                                <?php foreach($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $selected_subject_id == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            <?php if($selected_subject_id && $selected_subject_name): ?>
            <div class="card shadow-sm border-0 table-custom p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Student List: <span class="text-primary"><?= htmlspecialchars($selected_subject_name) ?></span></h5>
                </div>
                
                <form method="post">
                    <input type="hidden" name="save_marks" value="1">
                    <input type="hidden" name="subject_id" value="<?= $selected_subject_id ?>">
                    <input type="hidden" name="semester_id" value="<?= $selected_sem_id ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Mid Paper (<?= $max_mid_paper ?>)</th>
                                    <th>Quiz (5)</th>
                                    <th>Assgn (5)</th>
                                    <th>Pres (5)</th>
                                    <th>Attnd (5)</th>
                                    <th>Mid Total (50)</th>
                                    
                                    <?php if($is_practical): ?>
                                        <th>Practical (<?= $max_practical ?>)</th>
                                    <?php endif; ?>
                                    
                                    <th>Uni Final (50)</th>
                                    <th>Total (100)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $st): ?>
                                <tr>
                                    <td><span class="badge bg-primary rounded-pill"><?= htmlspecialchars($st['roll_no']) ?></span></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($st['name']) ?></td>
                                    <td>
                                        <input type="number" name="mid_paper[<?= $st['id'] ?>]" class="form-control form-control-sm bg-light" 
                                               value="<?= htmlspecialchars((string)($st['mid_paper_marks'] ?? '')) ?>" 
                                               min="0" max="<?= $max_mid_paper ?>" step="any" oninput="updateTotal(<?= $st['id'] ?>)"
                                               readonly tabindex="-1">
                                    </td>
                                    <td>
                                        <input type="number" name="quiz[<?= $st['id'] ?>]" class="form-control form-control-sm bg-light" 
                                               value="<?= htmlspecialchars((string)($st['quiz_marks'] ?? '')) ?>" 
                                               min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"
                                               readonly tabindex="-1">
                                    </td>
                                    <td>
                                        <input type="number" name="assignment[<?= $st['id'] ?>]" class="form-control form-control-sm bg-light" 
                                               value="<?= htmlspecialchars((string)($st['assignment_marks'] ?? '')) ?>" 
                                               min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"
                                               readonly tabindex="-1">
                                    </td>
                                    <td>
                                        <input type="number" name="presentation[<?= $st['id'] ?>]" class="form-control form-control-sm bg-light" 
                                               value="<?= htmlspecialchars((string)($st['presentation_marks'] ?? '')) ?>" 
                                               min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"
                                               readonly tabindex="-1">
                                    </td>
                                    <td>
                                        <input type="number" name="attendance[<?= $st['id'] ?>]" class="form-control form-control-sm bg-light" 
                                               value="<?= htmlspecialchars((string)($st['attendance_marks'] ?? '')) ?>" 
                                               min="0" max="5" step="any" oninput="updateTotal(<?= $st['id'] ?>)"
                                               readonly tabindex="-1">
                                    </td>
                                    <td>
                                        <input type="text" id="mid_total_<?= $st['id'] ?>" class="form-control form-control-sm bg-light fw-bold" 
                                               value="<?= htmlspecialchars((string)($st['mid_marks'] ?? '')) ?>" readonly tabindex="-1">
                                    </td>
                                    
                                    <?php if($is_practical): ?>
                                    <td>
                                        <input type="number" name="practical_marks[<?= $st['id'] ?>]" class="form-control form-control-sm <?= !isSuperAdmin() ? 'bg-light' : 'mark-input' ?>" 
                                               value="<?= htmlspecialchars((string)($st['practical_marks'] ?? '')) ?>" 
                                               min="0" max="<?= $max_practical ?>" step="any" oninput="updateTotal(<?= $st['id'] ?>)"
                                               <?= !isSuperAdmin() ? 'readonly tabindex="-1"' : '' ?>>
                                    </td>
                                    <?php endif; ?>
                                    
                                    <td>
                                        <input type="number" name="final_marks[<?= $st['id'] ?>]" class="form-control form-control-sm <?= !isSuperAdmin() ? 'bg-light' : 'mark-input' ?>" 
                                               value="<?= htmlspecialchars((string)($st['final_marks'] ?? '')) ?>" 
                                               min="0" max="50" step="any" oninput="updateTotal(<?= $st['id'] ?>)"
                                               <?= !isSuperAdmin() ? 'readonly tabindex="-1"' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="text" id="total_<?= $st['id'] ?>" class="form-control form-control-sm bg-light fw-bold" 
                                               value="<?= htmlspecialchars((string)($st['marks'] ?? '')) ?>" readonly tabindex="-1">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($students)): ?>
                                    <tr><td colspan="<?= $is_practical ? '11' : '10' ?>" class="text-center text-muted">No students found in this department.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(!empty($students) && isSuperAdmin()): ?>
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold mt-3">Upload Result</button>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
            <script>
        function updateTotal(stId) {
            const paperInput = document.querySelector(input[name="mid_paper[${stId}]"]);
            const quizInput = document.querySelector(input[name="quiz[${stId}]"]);
            const assgnInput = document.querySelector(input[name="assignment[${stId}]"]);
            const presInput = document.querySelector(input[name="presentation[${stId}]"]);
            const attndInput = document.querySelector(input[name="attendance[${stId}]"]);
            const finalInput = document.querySelector(input[name="final_marks[${stId}]"]);
            const practInput = document.querySelector(input[name="practical_marks[${stId}]"]);
            
            const maxPaper = parseFloat(paperInput.max) || 30;
            const paper = Math.min(Math.max(parseFloat(paperInput.value) || 0, 0), maxPaper);
            const quiz = Math.min(Math.max(parseFloat(quizInput.value) || 0, 0), 5);
            const assgn = Math.min(Math.max(parseFloat(assgnInput.value) || 0, 0), 5);
            const pres = Math.min(Math.max(parseFloat(presInput.value) || 0, 0), 5);
            const attnd = Math.min(Math.max(parseFloat(attndInput.value) || 0, 0), 5);
            const final = finalInput ? Math.min(Math.max(parseFloat(finalInput.value) || 0, 0), 50) : 0;
            
            let pract = 0;
            if (practInput) {
                const maxPract = parseFloat(practInput.max) || 10;
                pract = Math.min(Math.max(parseFloat(practInput.value) || 0, 0), maxPract);
            }
            
            const midTotal = paper + quiz + assgn + pres + attnd;
            document.getElementById(mid_total_${stId}).value = midTotal;
            
            const total = Math.round(midTotal + final + pract);
            document.getElementById(    otal_${stId}).value = total;
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeEl = document.activeElement;
                if (activeEl && activeEl.classList.contains('mark-input')) {
                    e.preventDefault();

                    let val = parseFloat(activeEl.value);
                    let max = parseFloat(activeEl.getAttribute('max'));
                    if (isNaN(max)) max = 100;

                    let isInvalid = false;
                    if (val > max) {
                        activeEl.value = max;
                        isInvalid = true;
                    }
                    if (val < 0) {
                        activeEl.value = 0;
                        isInvalid = true;
                    }

                    if (isInvalid) {
                        const match = activeEl.name.match(/\[(\d+)\]/);
                        if (match) updateTotal(match[1]);
                        // NO RETURN - allow it to proceed to the next cell!
                    }

                    const inputs = Array.from(document.querySelectorAll('.mark-input:not([readonly])'));
                    const index = inputs.indexOf(activeEl);
                    if (index > -1 && index + 1 < inputs.length) {
                        inputs[index + 1].focus();
                        inputs[index + 1].select();
                    }
                }
            }
        });

        document.addEventListener('blur', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('mark-input')) {
                let val = parseFloat(e.target.value);
                let max = parseFloat(e.target.getAttribute('max'));
                if (isNaN(max)) max = 100;

                let isInvalid = false;
                if (val > max) {
                    e.target.value = max;
                    isInvalid = true;
                }
                if (val < 0) {
                    e.target.value = 0;
                    isInvalid = true;
                }
                if (isInvalid) {
                    const match = e.target.name.match(/\[(\d+)\]/);
                    if (match) updateTotal(match[1]);
                }
            }
        }, true);
    </script>
</body>
</html>

