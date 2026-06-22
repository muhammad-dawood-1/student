<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT s.id, s.subject_name, s.credit_hours, sem.semester_name, d.dept_name 
    FROM teacher_subjects ts 
    JOIN subjects s ON ts.subject_id = s.id 
    JOIN semesters sem ON s.semester_id = sem.id 
    JOIN departments d ON s.department_id = d.id
    WHERE ts.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$assigned_subjects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Assigned Subjects - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Assigned Subjects</h2>
            
            <div class="card shadow-sm border-0 table-custom p-3 mt-4">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Credit Hours</th>
                            <th>Semester</th>
                            <th>Department</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assigned_subjects as $sub): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($sub['subject_name']) ?></td>
                            <td><?= $sub['credit_hours'] ?></td>
                            
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($sub['semester_name']) ?></span></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($sub['dept_name']) ?></span></td>
                            <td class="text-end">
                                <a href="enter_marks.php?subject_id=<?= $sub['id'] ?>" class="btn btn-sm btn-primary">Enter Marks</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($assigned_subjects)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No subjects assigned yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
