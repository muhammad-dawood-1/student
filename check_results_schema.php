<?php
require_once 'includes/config.php';

$table = 'results';
echo "--- $table ---\n";
$stmt = $pdo->query("DESCRIBE $table");
while ($row = $stmt->fetch()) {
    echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
}
?>
