<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');

$msg = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_subjects.php");
    exit;
}

$subject_id = $_GET['id'];

// Fetch the existing subject data
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    header("Location: manage_subjects.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_subject'])) {
    $name = trim($_POST['subject_name']);
    $credits = $_POST['credit_hours'];
    $practical_credits = $_POST['practical_credit_hours']; // NEW: Fetch practical credits
    $semester_id = $_POST['semester_id'];
    $department_id = $_POST['department_id'];
    
    // Check if all fields are filled (using !== '' to safely allow '0' as an input)
    if ($name && $credits !== '' && $practical_credits !== '' && $semester_id && $department_id) {
        // NEW: Added practical_credit_hours to the UPDATE query
        $update_stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, credit_hours = ?, practical_credit_hours = ?, semester_id = ?, department_id = ? WHERE id = ?");
        
        if($update_stmt->execute([$name, $credits, $practical_credits, $semester_id, $department_id, $subject_id])) {
            $msg = "<div class='alert alert-success'>Subject updated successfully.</div>";
            // Refresh subject data after update
            $stmt->execute([$subject_id]);
            $subject = $stmt->fetch();
        } else {
            $msg = "<div class='alert alert-danger'>Error updating subject.</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning'>Please fill in all fields.</div>";
    }
}

$semesters = $pdo->query("SELECT * FROM semesters ORDER BY id DESC")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Subject - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Edit Subject</h2>
                
            </div>
            <?= $msg ?>
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pb-0 border-0">
                    <h5 class="mb-0">Subject Details</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="update_subject" value="1">
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" value="<?= htmlspecialchars($subject['subject_name']) ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Theory Credit Hours</label>
                                <input type="number" name="credit_hours" class="form-control" min="0" max="4" value="<?= htmlspecialchars($subject['credit_hours']) ?>" required>
                                <small class="text-muted">E.g., 3</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Practical Credit Hours</label>
                                <input type="number" name="practical_credit_hours" class="form-control" min="0" max="3" value="<?= htmlspecialchars($subject['practical_credit_hours'] ?? '0') ?>" required>
                                <small class="text-muted">Enter 0 for 3-0 subjects, or 1 for 3-1 subjects.</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assign to Semester</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">Select Semester...</option>
                                <?php foreach($semesters as $sem): ?>
                                    <option value="<?= $sem['id'] ?>" <?= ($subject['semester_id'] == $sem['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sem['semester_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign to Department</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department...</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= ($subject['department_id'] == $dept['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['dept_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning">Update Subject</button> 

                        <a href="manage_subjects.php" class="btn btn-secondary">Back to Subjects</a>  
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>