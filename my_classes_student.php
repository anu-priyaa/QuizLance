<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];

/* FETCH STUDENT INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH JOINED CLASSES */
$classes = mysqli_query(
    $conn,
    "SELECT c.class_name, c.class_code, t.name AS teacher_name
     FROM class_students cs
     JOIN Classes c ON cs.class_id = c.id
     JOIN Teachers t ON c.teacher_id = t.id
     WHERE cs.student_id = $student_id"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Classes | QuizLance</title>
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

/* ===== PAGE TITLE ===== */
.page-title {
    color:#5A0E24;
    margin-bottom:25px;
}

/* ===== CLASS GRID ===== */
.class-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(240px,1fr));
    gap:20px;
}

.class-card {
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
    border-left:5px solid #5d9415;
}

.class-card h3 {
    color:#5A0E24;
    margin-bottom:10px;
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
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="join_class.php"><i class="fas fa-users"></i> Join Class</a>
    <a href="my_classes_student.php" class="active"><i class="fas fa-chalkboard"></i> My Classes</a>
    <a href="view_result_student.php"><i class="fas fa-chart-line"></i> Results</a>
    <a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
    <a href="profile_student.php"><i class="fas fa-user-edit"></i> Profile</a>

    <div class="logout">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content full" id="mainContent">

    <h2 class="page-title">My Classes</h2>

    <div class="class-grid">
        <?php if (mysqli_num_rows($classes) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($classes)): ?>
                <div class="class-card">
                    <h3><?= htmlspecialchars($row['class_name']) ?></h3>
                    <p><b>Class Code:</b> <?= htmlspecialchars($row['class_code']) ?></p>
                    <p><b>Teacher:</b> <?= htmlspecialchars($row['teacher_name']) ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You have not joined any classes yet.</p>
        <?php endif; ?>
    </div>

</div>

<script>
const menuToggle  = document.getElementById('menuToggle');
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

/* restore sidebar state without animation */
window.addEventListener('DOMContentLoaded', () => {
    const state = sessionStorage.getItem('sidebar');

    if (state === 'open') {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }

    setTimeout(() => sidebar.classList.remove('no-transition'), 50);
});

/* toggle sidebar only on click */
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
