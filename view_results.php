<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('student');
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'];
// Get all results for this student
$stmt = $pdo->prepare("
    SELECT r.marks, r.grade, s.subject_name, s.credit_hours, sem.semester_name, r.semester_id, r.final_marks 
    FROM results r 
    JOIN subjects s ON r.subject_id = s.id 
    JOIN semesters sem ON r.semester_id = sem.id 
    WHERE r.student_id = ?
    ORDER BY r.semester_id ASC, s.subject_name ASC
");
$stmt->execute([$student_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch Student details
$stmt_st = $pdo->prepare("SELECT roll_no, father_name, department, batch FROM students WHERE id = ?");
$stmt_st->execute([$student_id]);
$st_details = $stmt_st->fetch();
$semesters_data = [];
$total_gpa_points = 0;
$total_cgpa_credits = 0;
foreach($results as $r) {
    if (!isset($semesters_data[$r['semester_name']])) {
        $semesters_data[$r['semester_name']] = [
            'subjects' => [],
            'total_points' => 0,
            'total_credits' => 0,
            'has_final_marks' => true // Starts as true, set to false if any subject lacks final marks
        ];
    }
    
    // If superadmin has not uploaded uni final marks yet, mark the semester as pending
    if (empty($r['final_marks']) || $r['final_marks'] <= 0) {
        $semesters_data[$r['semester_name']]['has_final_marks'] = false;
    }
    
    $gpa_info = calculateGradeAndGPA($r['marks']);
    $gpa = $gpa_info['gpa'];
    $r['gpa'] = $gpa;
    
    $semesters_data[$r['semester_name']]['subjects'][] = $r;
    $semesters_data[$r['semester_name']]['total_points'] += ($gpa * $r['credit_hours']);
    $semesters_data[$r['semester_name']]['total_credits'] += $r['credit_hours'];
}
// Calculate CGPA only using semesters that have been fully uploaded
foreach($semesters_data as $sem_name => $sem_data) {
    if ($sem_data['has_final_marks']) {
        $total_gpa_points += $sem_data['total_points'];
        $total_cgpa_credits += $sem_data['total_credits'];
    }
}
$cgpa = $total_cgpa_credits > 0 ? round($total_gpa_points / $total_cgpa_credits, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Results - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        @media print {
            .sidebar, .btn-print { display: none !important; }
            .print-area { width: 100% !important; padding: 0 !important; }
            body { background: white; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5 print-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0"> Govt. College Of Commerce And Management Sciences CHD<br><br><center> DMC</center></h2>
                <button onclick="window.print()" class="btn btn-outline-primary fw-bold btn-print">🖨️ Print Result</button>
            </div>
            
            <div class="card p-4 shadow-sm border-0 mb-4 bg-white">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Student Name</p>
                        <h5 class="fw-bold"><?= htmlspecialchars($student_name) ?></h5>
                        <p class="mb-1 text-muted mt-3">Father's Name</p>
                        <h5 class="fw-bold"><?= htmlspecialchars($st_details['father_name'] ?? 'N/A') ?></h5>
                        <p class="mb-1 text-muted mt-3">Roll Number</p>
                        <h5 class="fw-bold"><?= htmlspecialchars($st_details['roll_no'] ?? 'N/A') ?></h5>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1 text-muted">Department</p>
                        <h5 class="fw-bold"><?= htmlspecialchars($st_details['department'] ?? 'N/A') ?></h5>
                        <p class="mb-1 text-muted mt-3">Batch</p>
                        <h5 class="fw-bold"><?= htmlspecialchars($st_details['batch'] ?? 'N/A') ?></h5>
                    </div>
                </div>
            </div>
            <?php if(empty($semesters_data)): ?>
                <div class="alert alert-warning">Result Not Present.</div>
            <?php else: ?>
                <?php foreach($semesters_data as $sem_name => $sem_data): ?>
                    <?php if (!$sem_data['has_final_marks']): ?>
                        <div class="card shadow-sm border-0 mb-4 table-custom">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($sem_name) ?></h5>
                            </div>
                            <div class="card-body py-4">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-clock me-2"></i> Result not uploaded yet.
                                </div>
                            </div>
                        </div>
                    <?php else: 
                        $sgpa = $sem_data['total_credits'] > 0 ? round($sem_data['total_points'] / $sem_data['total_credits'], 2) : 0;
                    ?>
                    <div class="card shadow-sm border-0 mb-4 table-custom">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($sem_name) ?></h5>
                            <span class="badge bg-light text-primary fs-6 py-2 px-3">Semester GPA: <?= number_format($sgpa, 2) ?></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject Name</th>
                                        <th>Total Marks</th>
                                        <th>Credit Hours</th>
                                        <th>Obtained Marks</th>
                                        <th>Grade</th>
                                        <th>GPA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($sem_data['subjects'] as $sub): ?>
                                    <tr>
                                        <td class="fw-semibold px-3"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                        <td>100</td>
                                        <td><span class="badge bg-secondary rounded-pill"><?= $sub['credit_hours'] ?></span></td>
                                        <td class="fw-bold text-dark"><?= (int)$sub['marks'] ?></td>
                                        <td><span class="badge bg-success rounded-pill px-3 py-2"><?= $sub['grade'] ?></span></td>
                                        <td class="fw-bold text-primary"><?= number_format($sub['gpa'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($total_cgpa_credits > 0): ?>
                    <div class="alert alert-info border-info border-2 bg-white d-flex justify-content-between align-items-center mb-5 p-4 shadow-sm">
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-info-emphasis fw-bold">Cumulative Result</h4>
                            <span class="text-muted">Total Credits: <?= $total_cgpa_credits ?></span>
                        </div>
                        <h1 class="mb-0 fw-bold text-primary display-4">CGPA: <?= number_format($cgpa, 2) ?></h1>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>