<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

redirectIfLogged();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password FROM teachers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'teacher';
            $_SESSION['name'] = $user['name'];
            header("Location: teacher/dashboard.php");
            exit;
        } else {
            $error = "Invalid teacher credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Login - SRMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1580582932707-520aed937b7b') no-repeat center center/cover;
            display: flex; justify-content: center; align-items: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            padding: 40px; border-radius: 20px; width: 380px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        h2 { color: white; margin-bottom: 25px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: rgba(255,255,255,0.8); margin-bottom: 8px; font-size: 14px; }
        .form-control {
            width: 100%; padding: 12px; border-radius: 10px; border: none;
            background: rgba(255, 255, 255, 0.9); font-size: 15px; outline: none;
        }
        .btn {
            width: 100%; padding: 12px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a1a; font-weight: 600; font-size: 16px; cursor: pointer;
            transition: 0.3s; margin-top: 10px;
        }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .error { color: #ff6b6b; background: rgba(255, 107, 107, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: white; text-decoration: none; font-size: 14px; opacity: 0.7; }
        .back-link:hover { opacity: 1; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Teacher Login</h2>
        <?php if($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
            </div>
            <div class="form-group" style="display: flex; align-items: center; margin-bottom: 20px;">
                <input type="checkbox" id="showPassword" onclick="togglePassword()" style="margin-right: 8px; cursor: pointer;">
                <label for="showPassword" style="margin-bottom: 0; color: rgba(255,255,255,0.8); font-size: 14px; cursor: pointer; display: inline;">Show Password</label>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <a href="index.php" class="back-link">← Back to Portal</a>
    </div>
    <script>
        function togglePassword() {
            var pwd = document.getElementById("password");
            if (pwd.type === "password") {
                pwd.type = "text";
            } else {
                pwd.type = "password";
            }
        }
    </script>
</body>
</html>
