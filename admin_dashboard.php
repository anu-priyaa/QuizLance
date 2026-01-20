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
<title>Admin Dashboard | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

/* ===== TOP BAR ===== */
.topbar{
    position:fixed;top:0;left:0;width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;
    padding:0 20px;z-index:1001;
}
.topbar i{font-size:24px;cursor:pointer;}

/* ===== TOP PROFILE ===== */
.top-profile{
    margin-left:auto;display:flex;
    align-items:center;gap:8px;
    cursor:pointer;position:relative;
}
.top-profile img{
    width:36px;height:36px;
    border-radius:50%;
    border:2px solid #5d9415;
    object-fit:cover;
}
.top-profile span{font-size:14px;font-weight:500;}

/* DROPDOWN */
.profile-dropdown{
    display:none;position:absolute;
    right:0;top:55px;
    background:white;border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,.15);
    min-width:180px;z-index:3000;
}
.profile-dropdown a{
    display:flex;align-items:center;
    gap:10px;padding:12px 15px;
    text-decoration:none;color:#333;
}
.profile-dropdown a:hover{background:#f2f2f2;}

/* ===== SIDEBAR ===== */
.sidebar{
    width:260px;background:#5A0E24;color:white;
    position:fixed;top:60px;left:0;
    height:calc(100vh - 60px);
    transition:.3s ease;
    display:flex;flex-direction:column;
    z-index:1000;
}
.sidebar.collapsed{transform:translateX(-100%);}
.sidebar.no-transition{transition:none!important;}

.sidebar-profile{
    text-align:center;padding:25px;
    border-bottom:1px solid rgba(255,255,255,.15);
}
.sidebar-profile img{
    width:85px;height:85px;
    border-radius:50%;
    border:3px solid #5d9415;
    object-fit:cover;
}
.sidebar-profile h3{margin-top:10px;font-size:16px;}

.sidebar a{
    padding:15px 25px;
    text-decoration:none;
    color:#d1d1d1;
    display:flex;align-items:center;
}
.sidebar a i{margin-right:15px;width:20px;}
.sidebar a:hover,.sidebar a.active{
    background:#861434;color:white;
}
.logout{margin-top:auto;border-top:1px solid rgba(255,255,255,.15);}

/* ===== MAIN ===== */
.main-content{
    margin-left:260px;
    padding:90px 40px 40px;
    transition:.3s ease;
}
.main-content.full{margin-left:0;}

/* ===== WELCOME CARD ===== */
.welcome-card{
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,.05);
    margin-bottom:30px;
    border-left:5px solid #5d9415;
}
.welcome-card h1{color:#5A0E24;}

/* ===== DASHBOARD GRID ===== */
.dashboard-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}
.menu-card{
    background:white;
    padding:24px;height:170px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    transition:.3s;
}
.menu-card:hover{transform:translateY(-5px);}
.menu-card i{font-size:38px;color:#5A0E24;margin-bottom:10px;}
.menu-card p{font-size:26px;font-weight:bold;color:#5d9415;}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>

    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($admin_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_admin.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile">
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

<!-- MAIN -->
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
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const main=document.getElementById('mainContent');

window.addEventListener('DOMContentLoaded',()=>{
    if(sessionStorage.getItem('sidebar')==='open'){
        sidebar.classList.remove('collapsed');
        main.classList.remove('full');
    }
    setTimeout(()=>sidebar.classList.remove('no-transition'),50);
});

menuToggle.onclick=()=>{
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('full');
    sessionStorage.setItem(
        'sidebar',
        sidebar.classList.contains('collapsed')?'closed':'open'
    );
};

function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p && !p.contains(e.target)){
        document.getElementById('profileDropdown').style.display='none';
    }
});
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
