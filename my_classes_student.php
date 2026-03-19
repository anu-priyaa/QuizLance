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

/* FETCH ASSIGNED CLASSES (Including Sub-Teachers via junction table) */
$classes_query = "
    SELECT 
        c.class_name, 
        t1.name AS teacher_name, 
        GROUP_CONCAT(t2.name SEPARATOR ', ') AS sub_teachers
    FROM class_students cs
    JOIN Classes c ON cs.class_id = c.id
    LEFT JOIN Teachers t1 ON c.teacher_id = t1.id
    LEFT JOIN class_subteachers cst ON c.id = cst.class_id
    LEFT JOIN Teachers t2 ON cst.teacher_id = t2.id
    WHERE cs.student_id = $student_id
    GROUP BY c.id
";
$classes = mysqli_query($conn, $classes_query);
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
.topbar i { font-size:24px; cursor:pointer; }

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
.top-profile span { font-size:14px; font-weight:500; }

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
.profile-dropdown a:hover { background:#f2f2f2; }

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
.sidebar.collapsed { transform:translateX(-100%); }
.sidebar.no-transition { transition:none !important; }

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
.sidebar-profile h3 { margin-top:10px; font-size:16px; }

.sidebar a {
    padding:15px 25px;
    text-decoration:none;
    color:#d1d1d1;
    display:flex;
    align-items:center;
}
.sidebar a i { margin-right:15px; width:20px; }
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
.main-content.full { margin-left:0; }

.page-title {
    color:#5A0E24;
    margin-bottom:25px;
}

/* CLASS GRID */
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
.class-card h3 { color:#5A0E24; margin-bottom:10px; }

/* PROFILE POPUP */
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
        <span><?= htmlspecialchars($student_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_student.php">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes_student.php" class="active"><i class="fas fa-chalkboard"></i> My Classes</a>
    <a href="view_result_student.php"><i class="fas fa-chart-line"></i> Results</a>
    <a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
</div>

<div class="main-content full" id="mainContent">
    <h2 class="page-title">My Classes</h2>

    <div class="class-grid">
        <?php if ($classes && mysqli_num_rows($classes) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($classes)): ?>
                <div class="class-card">
                    <h3><?= htmlspecialchars($row['class_name']) ?></h3>
                    <p><b>Teacher:</b> <?= htmlspecialchars($row['teacher_name']) ?></p>
                    <p><b>Sub Teacher:</b> <?= $row['sub_teachers'] ? htmlspecialchars($row['sub_teachers']) : 'None assigned' ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You are not assigned to any classes yet.</p>
        <?php endif; ?>
    </div>
</div>

<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($student_name) ?></h2>
    </div>
</div>

<script>
const menuToggle  = document.getElementById('menuToggle');
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

/* restore sidebar */
window.addEventListener('DOMContentLoaded', () => {
    const state = sessionStorage.getItem('sidebar');
    if (state === 'open') {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }
    setTimeout(() => sidebar.classList.remove('no-transition'), 50);
});

/* toggle sidebar */
menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
    sessionStorage.setItem(
        'sidebar',
        sidebar.classList.contains('collapsed') ? 'closed' : 'open'
    );
};

/* dropdown */
function toggleProfileMenu() {
    const menu = document.getElementById('profileDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

/* close dropdown */
document.addEventListener('click', function(e) {
    const profile = document.querySelector('.top-profile');
    if (profile && !profile.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});

/* profile popup */
function openProfilePopup() {
    document.getElementById('profilePopup').style.display = 'flex';
}
function closeProfilePopup() {
    document.getElementById('profilePopup').style.display = 'none';
}
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>