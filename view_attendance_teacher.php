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

/* FETCH TEACHER INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

$quiz_res = mysqli_query(
    $conn,
    "SELECT DISTINCT q.id, q.title
     FROM quizzes q
     JOIN Classes c ON q.class_id = c.id
     LEFT JOIN Class_SubTeachers s ON c.id = s.class_id
     WHERE 
        c.teacher_id = $teacher_id
        OR s.teacher_id = $teacher_id"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuizLance - Attendance</title>
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

/* ===== TOP PROFILE MENU ===== */
.top-profile {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    position: relative;
}

.top-profile img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #5d9415;
}

.top-profile span {
    font-size: 14px;
    font-weight: 500;
}

/* DROPDOWN */
.profile-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 55px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    min-width: 180px;
    overflow: hidden;
    z-index: 3000;
}

.profile-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}

.profile-dropdown a:hover {
    background: #f2f2f2;
}

.topbar i { font-size:24px; cursor:pointer; }

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
.sidebar-profile h3 {
    margin-top:10px;
    font-size:16px;
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
.main-content.full { margin-left:0; }

/* ===== PAGE CARD ===== */
.page-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
    max-width:520px;
}
.page-card h1 {
    color:#5A0E24;
    margin-bottom:10px;
}
.page-card p {
    margin-bottom:25px;
    color:#555;
}

/* FORM */
select {
    width:100%;
    padding:12px;
    border-radius:6px;
    border:1px solid #ccc;
    margin-bottom:20px;
}
button {
    background:#5d9415;
    color:white;
    padding:12px 18px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}
button:hover {
    background:#4e7d12;
    transform:translateY(-2px);
}

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

    object-fit: cover;      /* 🔥 MOST IMPORTANT */
    object-position: center;
    display: block;
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

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>

    <!-- PROFILE ICON (TOP RIGHT) -->
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $profile_pic ? $profile_pic . '?t=' . time() : 'https://via.placeholder.com/36' ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_teacher.php">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>


<!-- SIDEBAR -->
<div class="sidebar collapsed no-transition" id="sidebar">

<div class="sidebar-profile" onclick="openProfilePopup()">

        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>

    <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="manage_classes.php"><i class="fas fa-tasks"></i> Manage Class</a>
    <a href="archived_classes.php">
        <i class="fas fa-archive"></i> Archived Classes
    </a>
    <a href="view_students.php"><i class="fas fa-eye"></i> View Students</a>
    <a href="view_attendance_teacher.php" class="active"><i class="fas fa-clipboard-list"></i> Attendance</a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content full" id="mainContent">

    <div class="page-card">
        <h1>Quiz Attendance</h1>
        <p>Download attendance list of students who attempted the quiz.</p>

        <form method="GET" action="download_attendance.php">
            <label><strong>Select Quiz</strong></label>
            <select name="quiz_id" required>
                <option value="">-- Select Quiz --</option>
                <?php while ($q = mysqli_fetch_assoc($quiz_res)): ?>
                    <option value="<?= $q['id'] ?>">
                        <?= htmlspecialchars($q['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Download Attendance</button>
        </form>
    </div>

</div>

<!-- PROFILE POPUP -->
<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($teacher_name) ?></h2>
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

/* toggle sidebar */
menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');

    sessionStorage.setItem(
        'sidebar',
        sidebar.classList.contains('collapsed') ? 'closed' : 'open'
    );
};

/* PROFILE POPUP */
function openProfilePopup() {
    document.getElementById('profilePopup').style.display = 'flex';
}
function closeProfilePopup() {
    document.getElementById('profilePopup').style.display = 'none';
}
</script>

<script>
function toggleProfileMenu() {
    const menu = document.getElementById('profileDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

/* Close dropdown when clicking outside */
document.addEventListener('click', function (e) {
    const profile = document.querySelector('.top-profile');
    if (profile && !profile.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
