<?php
require_once 'c:/Users/M. Dawood/Documents/student/includes/config.php';
$stmt = $pdo->query("DESCRIBE admins");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "--- Data ---\n";
$stmt = $pdo->query("SELECT * FROM admins");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
