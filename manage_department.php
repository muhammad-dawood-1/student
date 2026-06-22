<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_department'])) {
        $name = trim($_POST['dept_name']);
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO departments (dept_name) VALUES (?)");
            try {
                if($stmt->execute([$name])) {
                    $msg = "<div class='alert alert-success'>Department added successfully.</div>";
                }
            } catch(PDOException $e) {
                $msg = "<div class='alert alert-danger'>Error: Department already exists.</div>";
            }
        }
    } elseif (isset($_POST['delete_department'])) {
        $id = $_POST['dept_id'];
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        if($stmt->execute([$id])) {
            $msg = "<div class='alert alert-success'>Department deleted successfully.</div>";
        }
    }
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Departments - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Manage Departments</h2>
            <?= $msg ?>
            
            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white pb-0 border-0">
                            <h5 class="mb-0">Add New Department</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="add_department" value="1">
                                <div class="mb-3">
                                    <label class="form-label">Department Name</label>
                                    <input type="text" name="dept_name" class="form-control" placeholder="e.g. BBA" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Department</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 table-custom p-3">
                        <h5 class="mb-3">Existing Departments</h5>
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Department Name</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($departments as $dept): ?>
                                <tr>
                                    <td><?= $dept['id'] ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($dept['dept_name']) ?></span></td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this department?');">
                                            <input type="hidden" name="delete_department" value="1">
                                            <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($departments)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No departments found.</td></tr>
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
