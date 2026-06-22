<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('admin');

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_teacher'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO teachers (name, email, password) VALUES (?, ?, ?)");
        try {
            if($stmt->execute([$name, $email, $password])) {
                $msg = "<div class='alert alert-success'>Teacher added successfully.</div>";
            }
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-danger'>Error: Email might already exist.</div>";
        }
    } elseif (isset($_POST['edit_teacher'])) {
        $id = $_POST['teacher_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        
        if ($password) {
            $stmt = $pdo->prepare("UPDATE teachers SET name=?, email=?, password=? WHERE id=?");
            $params = [$name, $email, $password, $id];
        } else {
            $stmt = $pdo->prepare("UPDATE teachers SET name=?, email=? WHERE id=?");
            $params = [$name, $email, $id];
        }
        
        try {
            if($stmt->execute($params)) {
                $msg = "<div class='alert alert-success'>Teacher updated successfully.</div>";
            }
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-danger'>Error: Email might already exist.</div>";
        }
    } elseif (isset($_POST['delete_teacher'])) {
        $id = $_POST['teacher_id'];
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
        if($stmt->execute([$id])) {
            $msg = "<div class='alert alert-success'>Teacher deleted.</div>";
        }
    } elseif (isset($_POST['assign_subject'])) {
        $tid = $_POST['teacher_id'];
        $sid = $_POST['subject_id'];
        $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
        try {
            if($stmt->execute([$tid, $sid])) {
                $msg = "<div class='alert alert-success'>Subject assigned.</div>";
            }
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-warning'>Subject already assigned to this teacher or database error.</div>";
        }
    }
}

$teachers = $pdo->query("SELECT * FROM teachers ORDER BY id DESC")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Teachers - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <h2 class="fw-bold mb-4">Manage Teachers</h2>
            <?= $msg ?>
            
            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white pb-0 border-0"><h5 class="mb-0">Add Teacher</h5></div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="add_teacher" value="1">
                                <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                                <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                                <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                                <button type="submit" class="btn btn-primary">Add Teacher</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white pb-0 border-0"><h5 class="mb-0">Assign Subject</h5></div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="assign_subject" value="1">
                                <div class="mb-3">
                                    <label>Select Teacher</label>
                                    <select name="teacher_id" class="form-select" required>
                                        <option value="">Choose...</option>
                                        <?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Select Subject</label>
                                    <select name="subject_id" class="form-select" required>
                                        <option value="">Choose...</option>
                                        <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">Assign Subject</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card shadow-sm border-0 table-custom p-3">
                        <h5 class="mb-3">Teacher List</h5>
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Name</th><th>Email</th><th>Assigned Subjects</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                <?php foreach($teachers as $t): 
                                    $t_id = $t['id'];
                                    $stmt = $pdo->query("
                                        SELECT s.subject_name, d.dept_name, sem.semester_name 
                                        FROM teacher_subjects ts 
                                        JOIN subjects s ON ts.subject_id=s.id 
                                        LEFT JOIN departments d ON s.department_id=d.id 
                                        LEFT JOIN semesters sem ON s.semester_id=sem.id 
                                        WHERE ts.teacher_id=$t_id
                                    ");
                                    $t_subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    $subs_list = [];
                                    foreach($t_subs as $sub) {
                                        $dept = $sub['dept_name'] ?? 'N/A';
                                        $sem = $sub['semester_name'] ?? 'N/A';
                                        $subs_list[] = htmlspecialchars($sub['subject_name']) . " <small class='text-muted'>(" . htmlspecialchars($dept) . " - " . htmlspecialchars($sem) . ")</small>";
                                    }
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($t['name']) ?></td>
                                    <td><?= htmlspecialchars($t['email']) ?></td>
                                    <td><?= $subs_list ? join('<br>', $subs_list) : '<span class="text-muted">None</span>' ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editTeacherModal<?= $t['id'] ?>">Edit</button>
                                            <form method="post" onsubmit="return confirm('Delete teacher?');" class="m-0">
                                                <input type="hidden" name="delete_teacher" value="1">
                                                <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>

                                        <!-- Edit Modal -->
                                        <div class="modal fade text-start" id="editTeacherModal<?= $t['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Teacher</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="edit_teacher" value="1">
                                                            <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                                            <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($t['name']) ?>" required></div>
                                                            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($t['email']) ?>" required></div>
                                                            <div class="mb-3"><label>Password (Leave blank to keep current)</label><input type="password" name="password" class="form-control"></div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
