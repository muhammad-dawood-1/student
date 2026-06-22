<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect them to their respective dashboard
redirectIfLogged();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SRMS - Smart Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #a855f7;
            --accent: #f43f5e;
            --glass: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            background: #0f172a;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            color: var(--text-main);
        }

        /* Animated Background Elements */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: move 20s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: var(--primary);
            top: -100px;
            left: -100px;
        }

        .shape-2 {
            width: 500px;
            height: 500px;
            background: var(--secondary);
            bottom: -150px;
            right: -150px;
            animation-duration: 25s;
        }

        .shape-3 {
            width: 300px;
            height: 300px;
            background: var(--accent);
            top: 40%;
            left: 60%;
            animation-duration: 15s;
        }

        @keyframes move {
            from { transform: translate(0, 0) rotate(0deg); }
            to { transform: translate(100px, 50px) rotate(30deg); }
        }

        .container {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 10;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInDown 0.8s ease-out;
        }

        .header h1 {
            font-size: clamp(2.5rem, 8vw, 4rem);
            font-weight: 700;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            letter-spacing: -1px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            width: 100%;
            perspective: 1000px;
        }

        .portal-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 40px;
            text-align: center;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out both;
        }

        .portal-card:nth-child(1) { animation-delay: 0.2s; }
        .portal-card:nth-child(2) { animation-delay: 0.4s; }
        .portal-card:nth-child(3) { animation-delay: 0.6s; }

        .portal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.05), transparent);
            transform: translateX(-100%);
            transition: 0.8s;
        }

        .portal-card:hover {
            transform: translateY(-10px) rotateX(5deg);
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .portal-card:hover::before {
            transform: translateX(100%);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            margin: 0 auto 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            transition: 0.5s;
        }

        .student-icon { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .teacher-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .admin-icon { background: linear-gradient(135deg, #ec4899, #db2777); }

        .portal-card:hover .icon-box {
            transform: scale(1.1) rotate(5deg);
        }

        .portal-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .portal-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .enter-btn {
            margin-top: 25px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--primary);
            transition: 0.3s;
        }

        .portal-card:hover .enter-btn {
            gap: 12px;
            color: #fff;
        }

        footer {
            margin-top: 80px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            animation: fadeIn 1.5s ease-in;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .search-section {
            width: 100%;
            max-width: 600px;
            margin-bottom: 60px;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        .search-box {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 10px;
            display: flex;
            gap: 10px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
        }

        .search-box:focus-within {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--primary);
            box-shadow: 0 0 30px -5px rgba(99, 102, 241, 0.4);
            transform: scale(1.02);
        }

        .search-box input {
            flex-grow: 1;
            background: transparent;
            border: none;
            padding: 15px 25px;
            color: #fff;
            font-size: 1.1rem;
            outline: none;
        }

        .search-box input::placeholder {
            color: var(--text-muted);
        }

        .search-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: #fff;
            padding: 0 35px;
            border-radius: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
            filter: brightness(1.1);
        }

        .search-btn:active {
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="container">
        <header class="header">
            <h1> BS Student Result Management System  For GCMS  Charsadda</h1>
            <p>Access your academic performance, manage subjects, and streamline results overview with our intelligent management system.</p>
        </header>

        <section class="search-section">
            <form action="view_public_result.php" method="GET" class="search-box">
                <input type="text" name="roll_no" placeholder="Enter Roll Number (e.g. 2024-CS-01)" required>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Check Result
                </button>
            </form>
        </section>

        <div class="portal-grid">
            <a href="student_login.php" class="portal-card">
                <div class="icon-box student-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Student Login</h3>
                <p>View your official transcripts, track semester GPA, and monitor your cumulative progress through our intuitive dashboard.</p>
                <div class="enter-btn">Access Results <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="teacher_login.php" class="portal-card">
                <div class="icon-box teacher-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3>Teacher Login</h3>
                <p>Submit grades, manage assigned subjects, and review student performance metrics with absolute precision and ease.</p>
                <div class="enter-btn">Manage Marks <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="admin_login.php" class="portal-card">
                <div class="icon-box admin-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>Administrator</h3>
                <p>Maintain system integrity, manage academic records, oversee faculty, and control institutional departments and semesters.</p>
                <div class="enter-btn">Admin Panel <i class="fas fa-arrow-right"></i></div>
            </a>
        </div>

        <footer>
            <p>&copy; 2026 Student Result Management System. Designed for academic excellence.</p>
        </footer>
    </div>
</body>
</html>
