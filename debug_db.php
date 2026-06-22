<?php
require_once 'includes/config.php';

function check_table($pdo, $table) {
    echo "Checking table: $table\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            echo "  Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Default: {$row['Default']}\n";
        }
    } catch (PDOException $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

check_table($pdo, 'students');
check_table($pdo, 'results');
check_table($pdo, 'subjects');
?>
