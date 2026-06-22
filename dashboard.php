<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('teacher');

// Fetch subjects assigned to teacher
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
    <meta charset="UTF-8">
    <title>Teacher Dashboard - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <!-- Sidebar -->
        <div class="sidebar py-4" style="width: 260px;">
            <h4 class="mb-4 text-center fw-bold text-white">Teacher Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                <li class="nav-item"><a href="assigned_subjects.php" class="nav-link">My Subjects</a></li>
                <li class="nav-item"><a href="enter_marks.php" class="nav-link">Enter Marks</a></li>
                <li class="nav-item mt-auto pt-5 px-3">
                    <a href="../logout.php" class="btn btn-danger w-100 fw-bold shadow-sm py-2" style="border-radius: 8px;">Logout</a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Welcome, Teacher <?= htmlspecialchars($_SESSION['name']) ?></h2>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card metric-card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title text-white-50">Assigned Subjects</h6>
                            <h2 class="display-5 fw-bold mb-0"><?= count($assigned_subjects) ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-5 table-custom p-3 border-0">
                <h5 class="mb-3">Your Subjects Overview</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Credit Hours</th>
                                <th>Department</th>
                                <th>Semester</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assigned_subjects as $sub): ?>
                            <tr>
                                <td><?= htmlspecialchars($sub['subject_name']) ?></td>
                                <td><?= $sub['credit_hours'] ?></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($sub['dept_name']) ?></span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($sub['semester_name']) ?></span></td>
                                <td class="text-end">
                                    <a href="enter_marks.php?subject_id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-primary fw-bold">Enter Marks</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($assigned_subjects)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No subjects assigned yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
