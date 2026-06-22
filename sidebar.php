<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar py-4" style="width: 250px;">
    <h4 class="mb-4 text-center fw-bold text-white">Teacher Panel</h4>
    <ul class="nav flex-column">
        <li class="nav-item"><a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li class="nav-item"><a href="assigned_subjects.php" class="nav-link <?= $current_page == 'assigned_subjects.php' ? 'active' : '' ?>">My Subjects</a></li>
        <li class="nav-item"><a href="enter_marks.php" class="nav-link <?= $current_page == 'enter_marks.php' ? 'active' : '' ?>">Enter Marks</a></li>
        <li class="nav-item mt-auto pt-5 px-3">
            <a href="../logout.php" class="btn btn-danger w-100 fw-bold shadow-sm py-2" style="border-radius: 8px;">Logout</a>
        </li>
    </ul>
</div>
