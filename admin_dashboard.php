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

/* ADMIN INFO */
$admin_name = $_SESSION['user_name'] ?? 'Admin';

/* 🔥 USE UPDATED PROFILE PIC FROM SESSION */
$imgSrc = !empty($_SESSION['admin_profile_pic'])
    ? htmlspecialchars($_SESSION['admin_profile_pic']) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* COUNTS */
$teacher_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM Teachers")
)['total'];

$student_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM Students")
)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', sans-serif;
}
body { background:#f0f2f5; overflow-x: hidden; }

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

/* ===== TOP PROFILE ===== */
.top-profile {
    margin-left:auto;
    display:flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    position:relative;
}

.top-profile img {
    width:36px;
    height:36px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #5d9415;
}

.top-profile span {
    font-size:14px;
    font-weight:500;
}

/* DROPDOWN */
.profile-dropdown {
    display:none;
    position:absolute;
    right:0;
    top:55px;
    background:white;
    border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
    min-width:180px;
    overflow:hidden;
    z-index:3000;
}

.profile-dropdown a {
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px 15px;
    text-decoration:none;
    color:#333;
    font-size:14px;
}

.profile-dropdown a:hover {
    background:#f2f2f2;
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

/* This class hides the sidebar */
.sidebar.collapsed {
    transform:translateX(-260px);
}

/* PROFILE */
.sidebar-profile {
    text-align:center;
    padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    cursor:pointer;
}

.sidebar-profile img {
    width:85px;
    height:85px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #5d9415;
}

.sidebar-profile h3 {
    margin-top:10px;
    font-size:16px;
}

/* MENU */
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

/* ===== MAIN ===== */
.main-content {
    margin-left:260px;
    padding:90px 40px 40px;
    transition:0.3s ease;
}

/* This class expands the content when sidebar is gone */
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

/* ===== GRID ===== */
.dashboard-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
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
    font-size: 18px;
    margin-bottom: 10px;
}

.menu-card p {
    font-size: 28px;
    font-weight: bold;
    color: #5d9415;
}

/* ===== PROFILE POPUP ===== */
.profile-popup {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    z-index:2000;
    justify-content:center;
    align-items:center;
}

.profile-popup-content {
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    position:relative;
}

.profile-popup-content img {
    width:200px;
    height:200px;
    border-radius:50%;
    border:4px solid #5d9415;
    object-fit:cover;
}

.close-btn {
    position:absolute;
    top:10px;
    right:14px;
    font-size:22px;
    cursor:pointer;
}
</style>
</head>

<body>

<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>

    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($admin_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_admin.php">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($admin_name) ?></h3>
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
</div>

<div class="main-content" id="mainContent">
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

<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($admin_name) ?></h2>
        <p style="color: gray; margin-top: 5px;">Administrator</p>
    </div>
</div>

<script>
const menuToggle  = document.getElementById('menuToggle');
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

// Handle sidebar state on load
window.addEventListener('DOMContentLoaded', () => {
    // If the saved state is 'closed', then add the classes
    if(sessionStorage.getItem('sidebar') === 'closed'){
        sidebar.classList.add('collapsed');
        mainContent.classList.add('full');
    }
});

menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
    
    // Check the current state and save it
    let state = sidebar.classList.contains('collapsed') ? 'closed' : 'open';
    sessionStorage.setItem('sidebar', state);
};

function toggleProfileMenu() {
    const menu = document.getElementById('profileDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e) {
    const profile = document.querySelector('.top-profile');
    if (profile && !profile.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});

function openProfilePopup() {
    document.getElementById('profilePopup').style.display = 'flex';
}

function closeProfilePopup() {
    document.getElementById('profilePopup').style.display = 'none';
}
</script>

<?php if(file_exists('includes/auto_logout.php')) include 'includes/auto_logout.php'; ?>

</body>
</html>