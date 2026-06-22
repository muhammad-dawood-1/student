<?php
require_once 'includes/config.php';

function dumpTable($pdo, $table) {
    echo "--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    echo "\n";
}

dumpTable($pdo, 'students');
dumpTable($pdo, 'departments');
dumpTable($pdo, 'semesters');
dumpTable($pdo, 'subjects');
dumpTable($pdo, 'teacher_subjects');
?>
