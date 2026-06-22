<?php
// Function to determine Grade and GPA point from marks
function calculateGradeAndGPA($marks) {
    if ($marks >= 85) return ['grade' => 'A+', 'gpa' => 4.0];
    if ($marks >= 80) return ['grade' => 'A', 'gpa' => 3.7];
    if ($marks >= 75) return ['grade' => 'B+', 'gpa' => 3.3];
    if ($marks >= 70) return ['grade' => 'B', 'gpa' => 3.0];
    if ($marks >= 65) return ['grade' => 'B', 'gpa' => 2.7];
    if ($marks >= 60) return ['grade' => 'C+', 'gpa' => 2.3];
    if ($marks >= 55) return ['grade' => 'C', 'gpa' => 2.0];
    if ($marks >= 50) return ['grade' => 'C', 'gpa' => 1.7];
    if ($marks >= 45) return ['grade' => 'D', 'gpa' => 1.3];
    return ['grade' => 'F', 'gpa' => 0.0];
}

// Function to calculate SGPA (Semester GPA)
function calculateSGPA($results) {
    $totalPoints = 0;
    $totalCredits = 0;
    
    foreach($results as $res) {
        $credits = $res['credit_hours'];
        $gpa = $res['gpa'];
        $totalPoints += ($gpa * $credits);
        $totalCredits += $credits;
    }
    
    if ($totalCredits == 0) return 0;
    return round($totalPoints / $totalCredits, 2);
}

// Security: redirect if not logged in as specific role
function requireRole($role) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../index.php");
        exit;
    }
    
    // Check for superadmin specifically if that's the required role
    if ($role === 'superadmin') {
        if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
            header("Location: ../index.php");
            exit;
        }
        return;
    }

    // For standard admin role, allow both admin and superadmin
    if ($role === 'admin') {
        if ($_SESSION['role'] !== 'admin') {
            header("Location: ../index.php");
            exit;
        }
        return;
    }

    // For other roles (teacher, student)
    if ($_SESSION['role'] !== $role) {
        header("Location: ../index.php");
        exit;
    }
}

// Check if user is superadmin
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';
}

// Redirect authenticated user to their respective dashboard
function redirectIfLogged() {
    if(isset($_SESSION['role'])) {
        if($_SESSION['role'] == 'admin') {
            header("Location: admin/dashboard.php");
            exit;
        } elseif($_SESSION['role'] == 'teacher') {
            header("Location: teacher/dashboard.php");
            exit;
        } elseif($_SESSION['role'] == 'student') {
            header("Location: student/dashboard.php");
            exit;
        }
    }
}
?>
