<?php
session_start();

/* ROLE PROTECTION */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* DATABASE */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

/* DASHBOARD DATA */
$teacher_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM Teachers")
)['total'];

$student_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM Students")
)['total'];

$admin_name = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { background:#f0f2f5; }

/* ===== TOP BAR ===== */
.topbar {
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:60px;
    background:#5A0E24;
    color:white;
    display:flex;
    align-items:center;
    padding:0 20px;
    z-index:1001;
}

.topbar i {
    font-size:24px;
    cursor:pointer;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width:260px;
    background:#5A0E24;
    color:white;
    display:flex;
    flex-direction:column;
    position:fixed;
    top:60px;
    left:0;
    height:calc(100vh - 60px);
    transition:0.3s ease;
    z-index:1000;
}

.sidebar.collapsed {
    transform:translateX(-100%);
}

.sidebar.no-transition {
    transition:none !important;
}

.sidebar-profile {
    text-align:center;
    padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
}

.sidebar-profile h3 {
    font-size:18px;
    font-weight:bold;
}

.sidebar a {
    padding:15px 25px;
    text-decoration:none;
    color:#d1d1d1;
    display:flex;
    align-items:center;
}

.sidebar a i {
    margin-right:15px;
    width:20px;
}

.sidebar a:hover,
.sidebar a.active {
    background:#861434;
    color:white;
}

.logout {
    margin-top:auto;
    border-top:1px solid rgba(255,255,255,0.15);
}

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left:260px;
    padding:90px 40px 40px;
    transition:0.3s ease;
}

.main-content.full {
    margin-left:0;
}

/* ===== WELCOME CARD ===== */
.welcome-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    margin-bottom:30px;
    border-left:5px solid #5d9415;
}

.welcome-card h1 {
    color:#5A0E24;
}

/* ===== STATS GRID ===== */
.dashboard-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px,1fr));
    gap:20px;
}

.menu-card {
    background:white;
    padding:30px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,0.03);
    transition:0.3s;
}

.menu-card:hover {
    transform:translateY(-5px);
    box-shadow:0 10px 20px rgba(0,0,0,0.1);
}

.menu-card i {
    font-size:40px;
    color:#5A0E24;
    margin-bottom:15px;
}

.menu-card h3 {
    margin-bottom:8px;
    color:#333;
}

.menu-card p {
    font-size:28px;
    font-weight:bold;
    color:#5d9415;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>
</div>

<!-- SIDEBAR -->
<div class="sidebar collapsed no-transition" id="sidebar">

    <div class="sidebar-profile">
        <h3>Admin Panel</h3>
    </div>

    <a href="admin_dashboard.php" class="active">
        <i class="fas fa-chart-pie"></i> Overview
    </a>
    <a href="manage_teachers.php">
        <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
    </a>
    <a href="manage_students.php">
        <i class="fas fa-user-graduate"></i> Manage Students
    </a>

    <div class="logout">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content full" id="mainContent">

    <div class="welcome-card">
        <h1>Welcome, <?= htmlspecialchars($admin_name) ?>!</h1>
        <p>Manage teachers, students, and system activity.</p>
    </div>

    <div class="dashboard-grid">

        <div class="menu-card">
            <i class="fas fa-chalkboard-teacher"></i>
            <h3>Total Teachers</h3>
            <p><?= $teacher_count ?></p>
        </div>

        <div class="menu-card">
            <i class="fas fa-user-graduate"></i>
            <h3>Total Students</h3>
            <p><?= $student_count ?></p>
        </div>

    </div>
</div>

<script>
const menuToggle  = document.getElementById('menuToggle');
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

/* restore sidebar state */
window.addEventListener('DOMContentLoaded', () => {
    const state = sessionStorage.getItem('sidebar');

    if (state === 'open') {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }

    setTimeout(() => sidebar.classList.remove('no-transition'), 50);
});

/* toggle only on click */
menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');

    sessionStorage.setItem(
        'sidebar',
        sidebar.classList.contains('collapsed') ? 'closed' : 'open'
    );
};
</script>

</body>
</html>
