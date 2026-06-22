<?php
require_once dirname(__DIR__) . '/includes/config.php';

try {
    // Check if columns exist
    $stmt = $pdo->query("DESCRIBE results");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $detailed_cols = ['mid_paper_marks', 'quiz_marks', 'assignment_marks', 'presentation_marks', 'attendance_marks'];
    $to_add = [];

    foreach ($detailed_cols as $col) {
        if (!in_array($col, $columns)) {
            $to_add[] = "ADD COLUMN `$col` INT(11) DEFAULT 0";
        }
    }

    if (!empty($to_add)) {
        $sql = "ALTER TABLE results " . implode(", ", $to_add);
        $pdo->exec($sql);
        echo "Database updated successfully: " . $sql;
    } else {
        echo "Database already updated.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
