<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');

$selected_dept_id = $_GET['dept_id'] ?? null;
$selected_sem_id = $_GET['semester_id'] ?? null;

// Fetch all Departments
$stmt_depts = $pdo->query("SELECT id, dept_name FROM departments ORDER BY dept_name");
$departments = $stmt_depts->fetchAll();

// Fetch all Semesters
$stmt_sems = $pdo->query("SELECT id, semester_name FROM semesters ORDER BY id");
$semesters = $stmt_sems->fetchAll();

$subjects = [];
$students = [];
$results_map = [];
$dept_name = '';
$semester_name = '';
$avg_sgpa = 0;
$total_credits_sem = 0;

if ($selected_dept_id && $selected_sem_id) {
    // Fetch department details
    $stmt_dept = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
    $stmt_dept->execute([$selected_dept_id]);
    $dept_name = $stmt_dept->fetchColumn();

    // Fetch semester details
    $stmt_sem = $pdo->prepare("SELECT semester_name FROM semesters WHERE id = ?");
    $stmt_sem->execute([$selected_sem_id]);
    $semester_name = $stmt_sem->fetchColumn();

    // Fetch subjects for this department and semester
    $stmt_subs = $pdo->prepare("SELECT id, subject_name, credit_hours FROM subjects WHERE department_id = ? AND semester_id = ? ORDER BY id ASC");
    $stmt_subs->execute([$selected_dept_id, $selected_sem_id]);
    $subjects = $stmt_subs->fetchAll();
    
    foreach($subjects as $sub) {
        $total_credits_sem += $sub['credit_hours'];
    }

    if ($dept_name) {
        // Fetch students in this department
        $stmt_studs = $pdo->prepare("SELECT id, roll_no, name, batch FROM students WHERE department = ? ORDER BY roll_no ASC");
        $stmt_studs->execute([$dept_name]);
        $students = $stmt_studs->fetchAll();

        $student_ids = array_column($students, 'id');
        if (!empty($student_ids)) {
            // Fetch all results for these students in this semester
            $in_clause = implode(',', array_fill(0, count($student_ids), '?'));
            $stmt_res = $pdo->prepare("
                SELECT r.student_id, r.subject_id, r.marks, r.grade 
                FROM results r
                WHERE r.semester_id = ? AND r.student_id IN ($in_clause)
            ");
            $stmt_res->execute(array_merge([$selected_sem_id], $student_ids));
            $db_results = $stmt_res->fetchAll();
            
            foreach($db_results as $dr) {
                $results_map[$dr['student_id']][$dr['subject_id']] = [
                    'marks' => $dr['marks'],
                    'grade' => $dr['grade']
                ];
            }

            $st_sgpa_list = [];
            foreach($students as $index => $st) {
                $total_points = 0;
                $total_credits = 0;
                $has_any_result = false;

                foreach($subjects as $sub) {
                    if (isset($results_map[$st['id']][$sub['id']])) {
                        $marks = $results_map[$st['id']][$sub['id']]['marks'];
                        $gpa_info = calculateGradeAndGPA($marks);
                        $gpa = $gpa_info['gpa'];
                        $total_points += ($gpa * $sub['credit_hours']);
                        $total_credits += $sub['credit_hours'];
                        $has_any_result = true;
                    }
                }

                $sgpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
                $students[$index]['sgpa'] = $sgpa;
                $students[$index]['has_any_result'] = $has_any_result;
                if ($has_any_result) {
                    $st_sgpa_list[] = $sgpa;
                }

                // Calculate CGPA up to the selected semester
                $cgpaStmt = $pdo->prepare("
                    SELECT r.marks, s.credit_hours 
                    FROM results r
                    JOIN subjects s ON r.subject_id = s.id
                    WHERE r.student_id = ? AND r.semester_id <= ?
                ");
                $cgpaStmt->execute([$st['id'], $selected_sem_id]);
                $cgpaResults = $cgpaStmt->fetchAll(PDO::FETCH_ASSOC);

                $cgpaPoints = 0;
                $cgpaCredits = 0;
                foreach($cgpaResults as $cRes) {
                    $gpa_info = calculateGradeAndGPA($cRes['marks']);
                    $cgpaPoints += ($gpa_info['gpa'] * $cRes['credit_hours']);
                    $cgpaCredits += $cRes['credit_hours'];
                }
                $students[$index]['cgpa'] = $cgpaCredits > 0 ? round($cgpaPoints / $cgpaCredits, 2) : 0;
            }
            $avg_sgpa = !empty($st_sgpa_list) ? round(array_sum($st_sgpa_list) / count($st_sgpa_list), 2) : 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Results Worksheet - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .result-cell {
            min-width: 100px;
        }
        .grade-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .stats-badge {
            font-size: 1.1rem;
            padding: 8px 16px;
            font-weight: 700;
        }
        @media print {
            .sidebar, .filter-card, .btn-action-container, .btn-print, .btn-back-link {
                display: none !important;
            }
            .print-area {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            body {
                background: white;
                color: black;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .table {
                border: 1px solid #dee2e6 !important;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5 print-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold m-0">Department Results Worksheet</h2>
                    <p class="text-muted m-0 btn-back-link">Search and generate consolidated results for departments by semester.</p>
                </div>
                <?php if ($selected_dept_id && $selected_sem_id && !empty($students)): ?>
                    <button onclick="window.print()" class="btn btn-primary btn-print fw-bold">
                        <i class="fas fa-print me-2"></i> Print Worksheet
                    </button>
                <?php endif; ?>
            </div>

            <!-- Filter Controls -->
            <div class="card shadow-sm border-0 mb-4 filter-card">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Select Department</label>
                            <select name="dept_id" class="form-select" onchange="this.form.submit()" required>
                                <option value="">Choose Department...</option>
                                <?php foreach($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $selected_dept_id == $d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['dept_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Select Semester</label>
                            <select name="semester_id" class="form-select" onchange="this.form.submit()" required>
                                <option value="">Choose Semester...</option>
                                <?php foreach($semesters as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $selected_sem_id == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['semester_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <a href="view_department_results.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($selected_dept_id && $selected_sem_id): ?>
                <?php if (empty($dept_name) || empty($semester_name)): ?>
                    <div class="alert alert-danger">Invalid Department or Semester selection.</div>
                <?php else: ?>
                    <div class="card p-4 shadow-sm border-0 mb-4 bg-white">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <span class="badge bg-secondary mb-2">Results Worksheet</span>
                                <h3 class="fw-bold mb-1 text-primary"><?= htmlspecialchars($dept_name) ?> Department</h3>
                                <h5 class="text-muted m-0"><?= htmlspecialchars($semester_name) ?></h5>
                            </div>
                            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                <div class="d-inline-flex gap-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <div class="text-muted small">Total Students</div>
                                        <div class="fs-4 fw-bold text-dark"><?= count($students) ?></div>
                                    </div>
                                    <div class="bg-light p-3 rounded text-center">
                                        <div class="text-muted small">Total Credit Hours</div>
                                        <div class="fs-4 fw-bold text-dark"><?= $total_credits_sem ?> CH</div>
                                    </div>
                                    <div class="bg-light p-3 rounded text-center">
                                        <div class="text-muted small">Average SGPA</div>
                                        <div class="fs-4 fw-bold text-success"><?= number_format($avg_sgpa, 2) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($subjects)): ?>
                        <div class="alert alert-warning p-4 shadow-sm border-0">
                            <i class="fas fa-exclamation-triangle me-2"></i> No subjects are currently mapped to the **<?= htmlspecialchars($dept_name) ?>** department for **<?= htmlspecialchars($semester_name) ?>**. Add subjects under the "Subjects" module first.
                        </div>
                    <?php elseif (empty($students)): ?>
                        <div class="alert alert-warning p-4 shadow-sm border-0">
                            <i class="fas fa-users-slash me-2"></i> No students are registered in the **<?= htmlspecialchars($dept_name) ?>** department.
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm border-0 table-custom p-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Batch</th>
                                            <?php foreach($subjects as $sub): ?>
                                                <th class="text-center result-cell" title="<?= htmlspecialchars($sub['subject_name']) ?>">
                                                    <div class="fw-bold small text-truncate" style="max-width: 140px;"><?= htmlspecialchars($sub['subject_name']) ?></div>
                                                    <span class="badge bg-secondary rounded-pill small"><?= $sub['credit_hours'] ?> CH</span>
                                                </th>
                                            <?php endforeach; ?>
                                            <th class="text-center bg-light text-success fw-bold">SGPA</th>
                                            <th class="text-center bg-light text-primary fw-bold">CGPA</th>
                                            <th class="text-center btn-action-container">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($students as $st): ?>
                                            <tr>
                                                <td><span class="badge bg-primary px-3 py-2 fs-6 rounded-pill"><?= htmlspecialchars($st['roll_no']) ?></span></td>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($st['name']) ?></td>
                                                <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($st['batch']) ?></span></td>
                                                
                                                <?php foreach($subjects as $sub): ?>
                                                    <td class="text-center result-cell">
                                                        <?php if (isset($results_map[$st['id']][$sub['id']])): ?>
                                                            <?php 
                                                                $marks = $results_map[$st['id']][$sub['id']]['marks'];
                                                                $grade = $results_map[$st['id']][$sub['id']]['grade'];
                                                                
                                                                $badge_class = 'bg-success';
                                                                if ($grade == 'F') $badge_class = 'bg-danger';
                                                                elseif (strpos($grade, 'D') !== false) $badge_class = 'bg-warning text-dark';
                                                            ?>
                                                            <div class="fw-bold text-dark fs-5 mb-0"><?= (int)$marks ?></div>
                                                            <span class="badge <?= $badge_class ?> grade-badge"><?= htmlspecialchars($grade) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted small"><em>N/A</em></span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                
                                                <td class="text-center fw-extrabold text-success fs-5 bg-light-subtle">
                                                    <?= $st['has_any_result'] ? number_format($st['sgpa'], 2) : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td class="text-center fw-extrabold text-primary fs-5 bg-light-subtle">
                                                    <?= $st['has_any_result'] ? number_format($st['cgpa'], 2) : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td class="text-center btn-action-container">
                                                    <a href="../view_public_result.php?roll_no=<?= urlencode($st['roll_no']) ?>" target="_blank" class="btn btn-sm btn-outline-info px-3 fw-bold">
                                                        <i class="fas fa-eye me-1"></i> Transcript
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="card p-5 text-center shadow-sm border-0 bg-white">
                    <div class="my-4">
                        <i class="fas fa-chart-bar text-primary-emphasis display-1 mb-4 opacity-50"></i>
                        <h4 class="fw-bold">Generate Department Results Worksheet</h4>
                        <p class="text-muted mx-auto" style="max-width: 500px;">Please choose a department and semester from the filter card above to load student records, obtained grades, and GPA calculations.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
