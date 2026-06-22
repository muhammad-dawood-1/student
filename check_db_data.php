<?php
require_once 'includes/config.php';

echo "--- DEPARTMENTS ---\n";
$stmt = $pdo->query("SELECT * FROM departments");
print_r($stmt->fetchAll());

echo "\n--- SUBJECTS ---\n";
$stmt = $pdo->query("SELECT * FROM subjects");
print_r($stmt->fetchAll());

echo "\n--- TEACHER_SUBJECTS ---\n";
$stmt = $pdo->query("SELECT * FROM teacher_subjects");
print_r($stmt->fetchAll());

echo "\n--- USERS (Teachers) ---\n";
$stmt = $pdo->query("SELECT id, name, username, role FROM users WHERE role = 'teacher'");
print_r($stmt->fetchAll());
?>
