<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');

$msg = '';
$edit_st = null;

// Handle Edit Mode Fetch
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_st = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_student']) || isset($_POST['update_student'])) {
        $roll_no = trim($_POST['roll_no']);
        $name = trim($_POST['name']);
        $father_name = trim($_POST['father_name']);
        $dept = trim($_POST['department']);
        $batch = trim($_POST['batch']);
        $semester_id = $_POST['semester_id'];
        
        $params = [$roll_no, $name, $father_name, $dept, $batch, $semester_id];
        $password_sql = "";
        
        if (!empty($_POST['password'])) {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_sql = ", password=?";
        } else {
            $password_hash = password_hash('123456', PASSWORD_DEFAULT);
        }

        if (isset($_POST['add_student'])) {
            $params[] = $password_hash;
            $stmt = $pdo->prepare("INSERT INTO students (roll_no, name, father_name, department, batch, semester_id, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            try {
                if($stmt->execute($params)) {
                    $msg = "<div class='alert alert-success'>Student added successfully.</div>";
                }
            } catch(PDOException $e) {
                if (strpos($e->getMessage(), 'father_name') !== false) {
                    try {
                        $pdo->exec("ALTER TABLE students ADD COLUMN father_name VARCHAR(255) AFTER name");
                        if($stmt->execute($params)) {
                            $msg = "<div class='alert alert-success'>Database updated and Student added successfully!</div>";
                        }
                    } catch(PDOException $e2) { $msg = "<div class='alert alert-danger'>Error: " . $e2->getMessage() . "</div>"; }
                } else { $msg = "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>"; }
            }
        } else {
            // Update Logic
            $id = $_POST['student_id'];
            $sql = "UPDATE students SET roll_no=?, name=?, father_name=?, department=?, batch=?, semester_id=? $password_sql WHERE id=?";
            if (!empty($password_sql)) {
                $params[] = $password_hash;
            }
            $params[] = $id;
            $upd = $pdo->prepare($sql);
            if($upd->execute($params)) {
                $msg = "<div class='alert alert-success'>Student updated successfully.</div>";
                header("Refresh:1; url=manage_students.php");
            }
        }
    } elseif (isset($_POST['delete_student'])) {
        $id = $_POST['student_id'];
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        if($stmt->execute([$id])) {
            $msg = "<div class='alert alert-success'>Student deleted.</div>";
        }
    }
}

$students = $pdo->query("
    SELECT st.*, sem.semester_name 
    FROM students st 
    LEFT JOIN semesters sem ON st.semester_id = sem.id 
    ORDER BY st.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Manage Students</h2>
            <?= $msg ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white pb-0 border-0 d-flex justify-content-between">
                            <h5 class="mb-0"><?= $edit_st ? 'Edit Student' : 'Add Student' ?></h5>
                            <?php if($edit_st): ?><a href="manage_students.php" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="<?= $edit_st ? 'update_student' : 'add_student' ?>" value="1">
                                <?php if($edit_st): ?><input type="hidden" name="student_id" value="<?= $edit_st['id'] ?>"><?php endif; ?>
                                <div class="mb-3"><label>Roll Number</label><input type="text" name="roll_no" class="form-control" value="<?= $edit_st['roll_no'] ?? '' ?>" required></div>
                                <div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?= $edit_st['name'] ?? '' ?>" required></div>
                                <div class="mb-3"><label>Father's Name</label><input type="text" name="father_name" class="form-control" value="<?= $edit_st['father_name'] ?? '' ?>" required></div>
<?php 
$depts = $pdo->query("SELECT dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();
$semesters = $pdo->query("SELECT id, semester_name FROM semesters ORDER BY id ASC")->fetchAll();
?>
                                <div class="mb-3">
                                    <label>Department</label>
                                    <select name="department" class="form-select" required>
                                        <option value="">Select Department</option>
                                        <?php foreach($depts as $d): ?>
                                            <option value="<?= htmlspecialchars($d['dept_name']) ?>" <?= (isset($edit_st) && $edit_st['department'] == $d['dept_name']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($d['dept_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Current Semester</label>
                                    <select name="semester_id" class="form-select" required>
                                        <option value="">Select Semester</option>
                                        <?php foreach($semesters as $s): ?>
                                            <option value="<?= $s['id'] ?>" <?= (isset($edit_st) && $edit_st['semester_id'] == $s['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['semester_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3"><label>Batch</label><input type="text" name="batch" class="form-control" value="<?= $edit_st['batch'] ?? '' ?>" required></div>
                                <div class="mb-3"><label>Password <?= $edit_st ? '(Leave empty to keep current)' : '' ?></label><input type="password" name="password" class="form-control" <?= $edit_st ? '' : 'required' ?>></div>
                                <button type="submit" class="btn btn-<?= $edit_st ? 'success' : 'primary' ?> w-100"><?= $edit_st ? 'Update Student' : 'Register Student' ?></button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0 table-custom p-3">
                        <h5 class="mb-3">Student Roster</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>Roll No</th><th>Name</th><th>Father Name</th><th>Dept</th><th>Semester</th><th>Batch</th><th class="text-end">Action</th></tr></thead>
                                <tbody>
                                    <?php foreach($students as $s): ?>
                                    <tr>
                                        <td><span class="badge bg-primary rounded-pill"><?= htmlspecialchars($s['roll_no']) ?></span></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= htmlspecialchars($s['father_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($s['department']) ?></td>
                                        <td><?= htmlspecialchars($s['semester_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($s['batch']) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="?edit_id=<?= $s['id'] ?>" class="btn btn-sm btn-info text-white">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete student? All their results will be deleted too!');">
                                                    <input type="hidden" name="delete_student" value="1">
                                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>
