<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireRole('superadmin'); // Only superadmins can manage other admins

$msg = '';
$edit_admin = null;

// Handle Edit Mode Fetch
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_admin = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_admin']) || isset($_POST['update_admin'])) {
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        
        if (isset($_POST['add_admin'])) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
            try {
                if($stmt->execute([$username, $password_hash, $role])) {
                    $msg = "<div class='alert alert-success'>Admin added successfully.</div>";
                }
            } catch(PDOException $e) {
                $msg = "<div class='alert alert-danger'>Error: Username already exists or database error.</div>";
            }
        } else {
            // Update Logic
            $id = $_POST['admin_id'];
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE admins SET username=?, password=?, role=? WHERE id=?");
                $success = $upd->execute([$username, $password_hash, $role, $id]);
            } else {
                $upd = $pdo->prepare("UPDATE admins SET username=?, role=? WHERE id=?");
                $success = $upd->execute([$username, $role, $id]);
            }
            if($success) {
                $msg = "<div class='alert alert-success'>Admin updated successfully.</div>";
                header("Refresh:1; url=manage_admins.php");
            }
        }
    } elseif (isset($_POST['delete_admin'])) {
        $id = $_POST['admin_id'];
        // Prevent deleting self
        if ($id == $_SESSION['user_id']) {
            $msg = "<div class='alert alert-danger'>You cannot delete your own account.</div>";
        } else {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            if($stmt->execute([$id])) {
                $msg = "<div class='alert alert-success'>Admin account deleted.</div>";
            }
        }
    }
}

$all_admins = $pdo->query("SELECT * FROM admins ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins - SRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex w-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-grow-1 p-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Manage Administrators</h2>
                <span class="badge bg-danger p-2">Super Admin Only</span>
            </div>
            
            <?= $msg ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white pb-0 border-0 d-flex justify-content-between">
                            <h5 class="mb-0"><?= $edit_admin ? 'Edit Admin' : 'Add New Admin' ?></h5>
                            <?php if($edit_admin): ?><a href="manage_admins.php" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="<?= $edit_admin ? 'update_admin' : 'add_admin' ?>" value="1">
                                <?php if($edit_admin): ?><input type="hidden" name="admin_id" value="<?= $edit_admin['id'] ?>"><?php endif; ?>
                                
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= $edit_admin['username'] ?? '' ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select" required>
                                        <option value="admin" <?= (isset($edit_admin) && $edit_admin['role'] == 'admin') ? 'selected' : '' ?>>Admin (Standard)</option>
                                        <option value="superadmin" <?= (isset($edit_admin) && $edit_admin['role'] == 'superadmin') ? 'selected' : '' ?>>Super Admin (Full Access)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Password <?= $edit_admin ? '(Leave empty to keep current)' : '' ?></label>
                                    <input type="password" name="password" class="form-control" <?= $edit_admin ? '' : 'required' ?>>
                                </div>
                                
                                <button type="submit" class="btn btn-<?= $edit_admin ? 'success' : 'primary' ?> w-100 fw-bold">
                                    <?= $edit_admin ? 'Update Admin Account' : 'Create Admin Account' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0 table-custom p-3">
                        <h5 class="mb-3">Admin Accounts</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_admins as $adm): ?>
                                    <tr>
                                        <td><?= $adm['id'] ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($adm['username']) ?></td>
                                        <td>
                                            <?php if($adm['role'] == 'superadmin'): ?>
                                                <span class="badge bg-danger">Super Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="?edit_id=<?= $adm['id'] ?>" class="btn btn-sm btn-outline-info">Edit</a>
                                                <?php if($adm['id'] != $_SESSION['user_id']): ?>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                    <input type="hidden" name="delete_admin" value="1">
                                                    <input type="hidden" name="admin_id" value="<?= $adm['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                                <?php endif; ?>
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
