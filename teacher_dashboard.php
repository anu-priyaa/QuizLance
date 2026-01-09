<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];

$res = mysqli_query(
    $conn,
    "SELECT name, profile_pic FROM Teachers WHERE id = $teacher_id"
);
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];

/* CACHE BUSTER */
$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuizLance - Teacher Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { display:flex; background:#f0f2f5; min-height:100vh; }

/* ===== SIDEBAR ===== */
.sidebar {
    width:260px;
    background:#5A0E24;
    color:white;
    display:flex;
    flex-direction:column;
    position:fixed;
    height:100vh;
}

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
    text-align:center;
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
    flex:1;
    padding:40px;
}

/* WELCOME CARD */
.welcome-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    margin-bottom:30px;
    border-left:5px solid #5d9415;
}

.welcome-card h1 { color:#5A0E24; }

/* DASHBOARD GRID */
.dashboard-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px,1fr));
    gap:20px;
}

.menu-link { text-decoration:none; color:inherit; }

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

/* ===== PROFILE POPUP ===== */
.profile-popup {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    z-index:999;
    justify-content:center;
    align-items:center;
}

.profile-popup-content {
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    width:300px;
    position:relative;
}

.profile-popup-content img {
    width:200px;
    height:200px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid #5d9415;
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

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>

    <a href="teacher_dashboard.php" class="active">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="my_classes.php">
        <i class="fas fa-users"></i> My Classes
    </a>
    <a href="view_attendance_teacher.php">
        <i class="fas fa-clipboard-list"></i> Attendance
    </a>
    <a href="profile_teacher.php">
        <i class="fas fa-user-edit"></i> Profile
    </a>


    <div class="logout">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="welcome-card">
        <h1>Welcome to QuizLance, <?= htmlspecialchars($teacher_name) ?>!</h1>
        <p>Empowering educators with smart assessments and real-time insights.</p>
    </div>

    <div class="dashboard-grid">

        <a href="create_quiz.php" class="menu-link">
            <div class="menu-card"><i class="fas fa-plus-square"></i><h3>Create Quiz</h3></div>
        </a>

        <a href="scheduled_quizzes.php" class="menu-link">
            <div class="menu-card"><i class="fas fa-calendar-alt"></i><h3>Scheduled Quizzes</h3></div>
        </a>

        <a href="live_quiz.php" class="menu-link">
            <div class="menu-card"><i class="fas fa-broadcast-tower"></i><h3>Live Quiz</h3></div>
        </a>

        <a href="view_result_teacher.php" class="menu-link">
            <div class="menu-card"><i class="fas fa-chart-line"></i><h3>Results & Analytics</h3></div>
        </a>

        <a href="leaderboard.php" class="menu-link">
            <div class="menu-card"><i class="fas fa-trophy"></i><h3>Leaderboard</h3></div>
        </a>

        <a href="certificates.php" class="menu-link">
            <div class="menu-card"><i class="fas fa-award"></i><h3>Certificates</h3></div>
        </a>

        <a href="doubts.php" class="menu-link">
            <div class="menu-card"><i class="fas fa-question-circle"></i><h3>Student Doubts</h3></div>
        </a>

    </div>
</div>

<!-- PROFILE POPUP -->
<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $profile_pic ? htmlspecialchars($profile_pic) . '?t=' . time() : 'https://via.placeholder.com/200' ?>">
        <h2><?= htmlspecialchars($teacher_name) ?></h2>
    </div>
</div>

<script>
function openProfilePopup() {
    document.getElementById('profilePopup').style.display = 'flex';
}
function closeProfilePopup() {
    document.getElementById('profilePopup').style.display = 'none';
}
</script>

</body>
</html>
