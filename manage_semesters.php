<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_semester'])) {
        $name = trim($_POST['semester_name']);
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO semesters (semester_name) VALUES (?)");
            if($stmt->execute([$name])) {
                $msg = "<div class='alert alert-success'>Semester added successfully.</div>";
            }
        }
    } elseif (isset($_POST['delete_semester'])) {
        $id = $_POST['semester_id'];
        $stmt = $pdo->prepare("DELETE FROM semesters WHERE id = ?");
        if($stmt->execute([$id])) {
            $msg = "<div class='alert alert-success'>Semester deleted successfully.</div>";
        }
    }
}

$semesters = $pdo->query("SELECT * FROM semesters ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Semesters - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Manage Semesters</h2>
            <?= $msg ?>
            
            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white pb-0 border-0">
                            <h5 class="mb-0">Add New Semester</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="add_semester" value="1">
                                <div class="mb-3">
                                    <label class="form-label">Semester Name</label>
                                    <input type="text" name="semester_name" class="form-control" placeholder="e.g. Semester 1" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Semester</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 table-custom p-3">
                        <h5 class="mb-3">Existing Semesters</h5>
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Semester Name</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($semesters as $sem): ?>
                                <tr>
                                    <td><?= $sem['id'] ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($sem['semester_name']) ?></span></td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this semester? Active subjects and results will be removed!');">
                                            <input type="hidden" name="delete_semester" value="1">
                                            <input type="hidden" name="semester_id" value="<?= $sem['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($semesters)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No semesters found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>
