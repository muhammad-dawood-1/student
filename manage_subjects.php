<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_subject'])) {
        $name = trim($_POST['subject_name']);
        $credits = $_POST['credit_hours'];
        $practical_credits = $_POST['practical_credit_hours'];
        $semester_id = $_POST['semester_id'];
        $department_id = $_POST['department_id'];
        
        // Using isset for credits to allow '0' as a valid input
        if ($name && isset($credits) && isset($practical_credits) && $semester_id && $department_id) {
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, credit_hours, practical_credit_hours, semester_id, department_id) VALUES (?, ?, ?, ?, ?)");
            if($stmt->execute([$name, $credits, $practical_credits, $semester_id, $department_id])) {
                $msg = "<div class='alert alert-success'>Subject added successfully.</div>";
            }
        }
    } elseif (isset($_POST['delete_subject'])) {
        $id = $_POST['subject_id'];
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        if($stmt->execute([$id])) {
            $msg = "<div class='alert alert-success'>Subject deleted successfully.</div>";
        }
    }
}

$semesters = $pdo->query("SELECT * FROM semesters ORDER BY id DESC")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC")->fetchAll();
$subjects = $pdo->query("
    SELECT s.id, s.subject_name, s.credit_hours, s.practical_credit_hours, sem.semester_name, d.dept_name 
    FROM subjects s 
    JOIN semesters sem ON s.semester_id = sem.id 
    LEFT JOIN departments d ON s.department_id = d.id
    ORDER BY s.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Manage Subjects</h2>
            <?= $msg ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white pb-0 border-0">
                            <h5 class="mb-0">Add New Subject</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="add_subject" value="1">
                                <div class="mb-3">
                                    <label class="form-label">Subject Name</label>
                                    <input type="text" name="subject_name" class="form-control" placeholder="e.g. Calculus" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Theory Credit Hours</label>
                                    <input type="number" name="credit_hours" class="form-control" min="0" max="4" value="3" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Practical Credit Hours</label>
                                    <input type="number" name="practical_credit_hours" class="form-control" min="0" max="3" value="0" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Assign to Semester</label>
                                    <select name="semester_id" class="form-select" required>
                                        <option value="">Select Semester...</option>
                                        <?php foreach($semesters as $sem): ?>
                                            <option value="<?= $sem['id'] ?>"><?= htmlspecialchars($sem['semester_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Assign to Department</label>
                                    <select name="department_id" class="form-select" required>
                                        <option value="">Select Department...</option>
                                        <?php foreach($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['dept_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Subject</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 table-custom p-3">
                        <h5 class="mb-3">Subject List</h5>
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Th. Credit</th>
                                    <th>Pr. Credit</th>
                                    <th>Semester</th>
                                    <th>Department</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($subjects as $sub): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                    <td><span class="badge bg-primary rounded-pill"><?= htmlspecialchars($sub['credit_hours']) ?></span></td>
                                    <td><span class="badge bg-success rounded-pill"><?= htmlspecialchars($sub['practical_credit_hours'] ?? '0') ?></span></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($sub['semester_name']) ?></span></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($sub['dept_name'] ?? 'N/A') ?></span></td>
                                    <td class="text-end">
                                        <a href="edit_subject.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this subject? Associated results will be removed!');">
                                            <input type="hidden" name="delete_subject" value="1">
                                            <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($subjects)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No subjects found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>s