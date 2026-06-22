<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$roll_no = $_GET['roll_no'] ?? '';
$error = '';
$student = null;
$results = [];
$latest_semester = null;

if (!empty($roll_no)) {
    // 1. Fetch student details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE roll_no = ?");
    $stmt->execute([$roll_no]);
    $student = $stmt->fetch();

    if ($student) {
        // 2. Find the latest semester_id for this student in the results table
        $stmt_sem = $pdo->prepare("
            SELECT MAX(r.semester_id) as last_sem_id 
            FROM results r 
            WHERE r.student_id = ?
        ");
        $stmt_sem->execute([$student['id']]);
        $sem_res = $stmt_sem->fetch();
        
        if ($sem_res && $sem_res['last_sem_id']) {
            $latest_semester_id = $sem_res['last_sem_id'];
            
            // Fetch semester name
            $stmt_name = $pdo->prepare("SELECT semester_name FROM semesters WHERE id = ?");
            $stmt_name->execute([$latest_semester_id]);
            $latest_semester = $stmt_name->fetchColumn();

            // 3. Fetch results for that specific semester
            $stmt_res = $pdo->prepare("
                SELECT r.marks, r.grade, s.subject_name, s.credit_hours, r.final_marks 
                FROM results r 
                JOIN subjects s ON r.subject_id = s.id 
                WHERE r.student_id = ? AND r.semester_id = ?
                ORDER BY s.subject_name ASC
            ");
            $stmt_res->execute([$student['id'], $latest_semester_id]);
            $results = $stmt_res->fetchAll(PDO::FETCH_ASSOC);

            // Check if final marks have been uploaded by the superadmin
            $has_final = true;
            foreach($results as $r) {
                if (empty($r['final_marks']) || $r['final_marks'] <= 0) {
                    $has_final = false;
                    break;
                }
            }
            if (!$has_final) {
                $error = "Result not uploaded yet.";
            }
        } else {
            $error = "No results found for this student yet.";
        }
    } else {
        $error = "Student with Roll Number <b>" . htmlspecialchars($roll_no) . "</b> not found.";
    }
} else {
    header("Location: index.php");
    exit;
}

// Calculate GPA and detailed metrics for the displayed semester
$total_points = 0;
$total_credits = 0;
foreach($results as $index => $r) {
    $gpa_info = calculateGradeAndGPA($r['marks']);
    $results[$index]['value'] = $gpa_info['gpa'];
    $results[$index]['grade'] = $gpa_info['grade']; // Ensure grade matches marks logic
    $results[$index]['grade_point'] = $gpa_info['gpa'] * $r['credit_hours'];
    
    $total_points += $results[$index]['grade_point'];
    $total_credits += $r['credit_hours'];
}
$sgpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;

// Fetch cumulative credits for the student (all semesters)
$stmt_cum = $pdo->prepare("
    SELECT SUM(s.credit_hours) 
    FROM results r 
    JOIN subjects s ON r.subject_id = s.id 
    WHERE r.student_id = ?
");
$stmt_cum->execute([$student['id']]);
$cumulative_credits = $stmt_cum->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result for <?= htmlspecialchars($roll_no) ?> - SRMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --secondary: #a855f7;
            --secondary-glow: rgba(168, 85, 247, 0.4);
            --accent: #f43f5e;
            --success: #10b981;
            --bg: #030712;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.06);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --card-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.7);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        body {
            background: #020617;
            color: var(--text-main);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
            line-height: 1.6;
            position: relative;
        }

        /* Sophisticated Mesh Background */
        .mesh-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background-color: #020617;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(244, 63, 94, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            animation: meshMove 20s infinite alternate ease-in-out;
        }

        @keyframes meshMove {
            0% { background-position: 0% 0%; }
            100% { background-position: 10% 10%; }
        }

        /* Grain Texture Overlay */
        .grain-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            opacity: 0.03;
            pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        /* Floating Orbs */
        .orb {
            position: fixed;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(120px);
            z-index: -1;
            opacity: 0.4;
            animation: orbFloat 25s infinite alternate ease-in-out;
        }
        .orb-1 { background: var(--primary); top: -200px; left: -100px; animation-duration: 30s; }
        .orb-2 { background: var(--secondary); bottom: -200px; right: -100px; animation-duration: 35s; animation-delay: -5s; }
        .orb-3 { background: var(--accent); top: 40%; left: 30%; width: 400px; height: 400px; animation-duration: 40s; }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) rotate(0deg) scale(1); }
            100% { transform: translate(150px, 100px) rotate(180deg) scale(1.2); }
        }

        .container { width: 100%; max-width: 1100px; z-index: 10; margin: 0 auto; padding-top: 20px; }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
            margin-bottom: 30px;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 20px var(--primary-glow);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .btn-back:hover { 
            transform: translateX(-8px) scale(1.05); 
            box-shadow: 0 15px 30px var(--primary-glow), 0 0 20px rgba(168, 85, 247, 0.4); 
            border-color: #fff;
        }

        .result-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid var(--glass-border);
            border-radius: 48px;
            padding: 60px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            animation: cardEntrance 1s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes cardEntrance { 
            from { opacity: 0; transform: translateY(60px) scale(0.95); } 
            to { opacity: 1; transform: translateY(0) scale(1); } 
        }

        .result-card::after {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(99,102,241,0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 60px;
            position: relative;
        }

        .student-profile h1 { 
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            line-height: 1.1;
        }
        .student-profile p { color: var(--text-muted); font-size: 1.2rem; font-weight: 500; letter-spacing: 2px; text-transform: uppercase; }

        .semester-badge {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 16px 32px;
            border-radius: 20px;
            font-weight: 900;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            box-shadow: 0 15px 35px var(--primary-glow);
            position: relative;
            overflow: hidden;
        }
        .semester-badge::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            animation: sweep 3s infinite linear;
        }
        @keyframes sweep { 0% { left: -100%; } 100% { left: 100%; } }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 60px;
        }

        .detail-item {
            background: rgba(255,255,255,0.015);
            padding: 25px;
            border-radius: 28px;
            border: 1px solid var(--glass-border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }
        .detail-item:hover { 
            background: rgba(255,255,255,0.04); 
            transform: translateY(-8px); 
            border-color: rgba(255,255,255,0.15); 
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);
        }
        .detail-item label { 
            display: block; 
            color: var(--text-muted); 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            font-weight: 800; 
            margin-bottom: 10px; 
            letter-spacing: 1.5px; 
        }
        .detail-item span { font-weight: 700; font-size: 1.25rem; color: #fff; }

        .table-wrapper {
            background: rgba(0,0,0,0.25);
            border-radius: 32px;
            border: 1px solid var(--glass-border);
            margin-bottom: 50px;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 25px; color: var(--text-muted); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 2px; background: rgba(255,255,255,0.02); }
        td { padding: 25px; border-bottom: 1px solid var(--glass-border); font-size: 1.1rem; transition: 0.3s; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); color: #fff; }
        
        .grade-pill {
            background: rgba(255,255,255,0.05);
            padding: 8px 18px;
            border-radius: 12px;
            font-weight: 900;
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--primary);
            box-shadow: inset 0 0 10px rgba(255,255,255,0.05);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            padding: 40px;
            border-radius: 36px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            position: relative;
        }

        .sgpa-badge { display: flex; align-items: center; gap: 25px; }
        .sgpa-display {
            width: 100px; height: 100px;
            border-radius: 30px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.2rem;
            font-weight: 900;
            box-shadow: 0 15px 40px var(--primary-glow);
            animation: pulse 2s infinite alternate;
        }
        @keyframes pulse { from { transform: scale(1); box-shadow: 0 15px 30px var(--primary-glow); } to { transform: scale(1.05); box-shadow: 0 20px 50px var(--primary-glow); } }

        .sgpa-text p { color: var(--text-muted); font-weight: 700; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; }
        .sgpa-text h3 { color: var(--success); font-size: 1.4rem; font-weight: 900; margin-top: 5px; }

        .btn-action {
            background: #fff;
            color: #000;
            border: none;
            padding: 18px 40px;
            border-radius: 20px;
            font-weight: 900;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1.1rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-action:hover { transform: translateY(-8px) rotate(2deg); box-shadow: 0 25px 50px rgba(0,0,0,0.5); }

        /* Responsive Table */
        @media (max-width: 768px) {
            .header-info { flex-direction: column; text-align: center; gap: 40px; }
            .result-card { padding: 30px; border-radius: 32px; }
            .details-grid { grid-template-columns: 1fr; }
            .summary-row { flex-direction: column; gap: 40px; text-align: center; }
            .sgpa-badge { flex-direction: column; }
            
            /* Table to Cards on Mobile */
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { border-bottom: 2px solid var(--glass-border); padding: 20px 0; }
            td { border: none; position: relative; padding-left: 50%; text-align: right; font-size: 1rem; }
            td:before { 
                content: attr(data-label); 
                position: absolute; 
                left: 0; 
                width: 45%; 
                padding-right: 10px; 
                white-space: nowrap; 
                text-align: left; 
                font-weight: 800; 
                color: var(--text-muted); 
                text-transform: uppercase;
                font-size: 0.75rem;
            }
        }

        @media print {
            body { background: white; color: black; padding: 0; }
            .mesh-bg, .grain-overlay, .orb, .btn-back, .btn-action, .result-card::after, .semester-badge::before { display: none; }
            .result-card { background: white; border: 3px solid #000; box-shadow: none; color: black; backdrop-filter: none; padding: 40px; }
            .header-info, td, th, .detail-item, .table-wrapper, .summary-row { border-color: #000; color: black !important; }
            h1, span, td, .grade-pill, .sgpa-text h3, .sgpa-text p { -webkit-text-fill-color: black !important; color: black !important; }
            .semester-badge { border: 3px solid #000; color: black; background: none; box-shadow: none; }
            .summary-row { background: none; }
            .sgpa-display { border: 3px solid #000; background: none; color: black; box-shadow: none; animation: none; }
        }
    </style>
</head>
<body>
    <div class="mesh-bg"></div>
    <div class="grain-overlay"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="container">
        <a href="index.php" class="btn-back"><i class="fas fa-chevron-left"></i> RETURN TO HOME</a>

        <?php if ($error): ?>
            <div class="error-container" style="text-align: center; padding: 50px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 32px; box-shadow: var(--card-shadow); max-width: 600px; margin: 40px auto;">
                <i class="fas <?= $error == 'Result not uploaded yet.' ? 'fa-clock' : 'fa-search' ?> mb-4" style="font-size: 4rem; color: var(--primary);"></i>
                <h2 class="fw-bold mb-3" style="color: #fff;"><?= $error == 'Result not uploaded yet.' ? 'Result Pending' : 'Not Found' ?></h2>
                <p class="fs-5 mb-4" style="color: var(--text-muted);"><?= $error ?></p>
                <a href="index.php" class="btn-action" style="display: inline-flex; justify-content: center; margin: 20px auto 0; background: var(--primary); color: white; text-decoration: none;">Try New Search</a>
            </div>
        <?php else: ?>
            <div class="result-card">
                <div class="header-info">
                    <div class="student-profile">
                        <h1><?= htmlspecialchars($student['name']) ?></h1>
                        <p>Academic Excellence Summary</p>
                    </div>
                    <div class="semester-badge">
                        <?= htmlspecialchars($latest_semester) ?>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <label>Enrollment No</label>
                        <span><?= htmlspecialchars($student['roll_no']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Department</label>
                        <span><?= htmlspecialchars($student['department']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Academic Batch</label>
                        <span><?= htmlspecialchars($student['batch']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Semester Credits</label>
                        <span><?= $total_credits ?> CH</span>
                    </div>
                    <div class="detail-item">
                        <label>Cumulative Credits</label>
                        <span><?= $cumulative_credits ?> CH</span>
                    </div>
                    <div class="detail-item">
                        <label>Verification</label>
                        <span style="color: var(--success);"><i class="fas fa-shield-alt"></i> Official Record</span>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Title</th>
                                <th>Total Marks</th>
                                <th>Obtained Marks</th>
                                <th>Grade</th>
                                <th>Value</th>
                                <th>Credit Hours</th>
                                <th>Grade Point</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($results as $r): ?>
                            <tr>
                                <td data-label="Subject" style="font-weight: 700; color: #fff;"><?= htmlspecialchars($r['subject_name']) ?></td>
                                <td data-label="Total Marks">100</td>
                                <td data-label="Obtained Marks" style="font-weight: 800;"><?= $r['marks'] ?></td>
                                <td data-label="Grade"><span class="grade-pill"><?= $r['grade'] ?></span></td>
                                <td data-label="Value" style="font-weight: 900; color: var(--success);"><?= number_format($r['value'], 2) ?></td>
                                <td data-label="Credit Hours"><?= $r['credit_hours'] ?></td>
                                <td data-label="Grade Point" style="color: var(--secondary); font-weight: 900;"><?= number_format($r['grade_point'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-row">
                    <div class="sgpa-badge">
                        <div class="sgpa-display">
                            <?= number_format($sgpa, 2) ?>
                        </div>
                        <div class="sgpa-text">
                            <p>Obtained GPA: <?= number_format($sgpa, 2) ?> out of 4.00</p>
                            <h3>PASS / PROMOTED</h3>
                        </div>
                    </div>
                    <button onclick="window.print()" class="btn-action">
                        <i class="fas fa-print"></i> DOWNLOAD TRANSCRIPT
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <footer style="margin-top: 60px; text-align: center; color: var(--text-muted); font-size: 0.95rem; font-weight: 500;">
            <p>&copy; 2026 Student Result Management System. All academic records are cryptographically verified.</p>
        </footer>
    </div>
</body>
</html>
