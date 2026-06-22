<?php
require_once dirname(__DIR__) . '/includes/config.php';

try {
    $pdo->exec("ALTER TABLE students ADD COLUMN father_name VARCHAR(255) AFTER name");
    echo "Column 'father_name' added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
