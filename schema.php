<?php
require 'C:/xampp/htdocs/student/includes/config.php';
$stmt = $pdo->query('DESCRIBE results');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $pdo->query('DESCRIBE subjects');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
