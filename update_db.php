<?php
require_once '../includes/config.php';

try {
    // 1. Add role column to admins table if it doesn't exist
    $pdo->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'admin' AFTER password");
    echo "Column 'role' added successfully (or already exists).<br>";

    // 2. Promote existing admin to superadmin
    $pdo->exec("UPDATE admins SET role = 'superadmin' WHERE username = 'admin'");
    echo "Default admin promoted to superadmin.<br>";

    // 3. Ensure results table has necessary columns (just in case schema.sql was outdated)
    $columns = [
        'mid_marks' => 'INT DEFAULT 0',
        'final_marks' => 'INT DEFAULT 0',
        'mid_paper_marks' => 'INT DEFAULT 0',
        'quiz_marks' => 'INT DEFAULT 0',
        'assignment_marks' => 'INT DEFAULT 0',
        'presentation_marks' => 'INT DEFAULT 0',
        'attendance_marks' => 'INT DEFAULT 0'
    ];

    foreach ($columns as $col => $type) {
        $pdo->exec("ALTER TABLE results ADD COLUMN IF NOT EXISTS $col $type");
    }
    echo "Results table columns verified/added.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
