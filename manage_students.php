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

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'] ?? 'Admin';

/* PROFILE PIC SYNC */
$imgSrc = !empty($_SESSION['admin_profile_pic'])
    ? htmlspecialchars($_SESSION['admin_profile_pic']) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* CURRENT VIEW */
$view = $_GET['view'] ?? 'all';

/* ACTIVATE / DEACTIVATE LOGIC */
if (isset($_GET['disable'])) {
    mysqli_query($conn,"UPDATE Students SET status='inactive' WHERE id=".(int)$_GET['disable']);
    header("Location: manage_students.php?view=$view"); exit();
}
if (isset($_GET['activate'])) {
    mysqli_query($conn,"UPDATE Students SET status='active' WHERE id=".(int)$_GET['activate']);
    header("Location: manage_students.php?view=$view"); exit();
}

/* COUNTS */
$total_students    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM Students"))['c'];
$active_students   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM Students WHERE status='active'"))['c'];
$inactive_students = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM Students WHERE status='inactive'"))['c'];

/* FETCH STUDENTS */
$where = "";
if($view === 'active')   $where = "WHERE status='active'";
if($view === 'inactive') $where = "WHERE status='inactive'";
$students = mysqli_query($conn, "SELECT * FROM Students $where ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* ===== UI & FONT SYNCED CSS ===== */
* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', sans-serif;
}
body { background:#f0f2f5; }

/* TOPBAR */
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

/* SIDEBAR */
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

.sidebar-profile {
    text-align:center;
    padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    cursor:pointer; /* Added cursor */
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
.sidebar a:hover, .sidebar a.active {
    background:#861434;
    color:white;
}

/* MAIN CONTENT */
.main-content {
    margin-left:260px;
    padding:90px 40px 40px;
    transition:0.3s ease;
}
.main-content.full { margin-left:0; }

/* WELCOME/PAGE CARD */
.welcome-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    margin-bottom:30px;
    border-left:5px solid #5d9415;
}
.welcome-card h1 { color:#5A0E24; margin-bottom: 5px; }
.welcome-card p { color: #666; font-size: 15px; }

/* PDF DOWNLOAD BUTTON */
.btn-download {
    display:inline-block;
    margin-bottom:20px;
    background:#5d9415;
    color:white;
    padding:12px 20px;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
    transition: 0.3s;
}
.btn-download:hover { background: #4a7a11; }

/* STATS GRID */
.dashboard-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:20px;
    margin-bottom: 30px;
}
.menu-card {
    background:white;
    padding:25px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,0.03);
    text-decoration: none;
    color: inherit;
    transition:0.3s;
}
.menu-card:hover {
    transform:translateY(-5px);
    box-shadow:0 10px 20px rgba(0,0,0,0.1);
}
.menu-card i { font-size:32px; color:#5A0E24; margin-bottom:12px; }
.menu-card h3 { font-size: 16px; font-weight: 600; margin-bottom: 5px; }
.menu-card .count { font-size: 24px; font-weight: bold; color: #5d9415; }

/* TABLE */
.table-container {
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}
table { width:100%; border-collapse:collapse; }
th { background:#5A0E24; color:white; text-align:left; padding:15px; font-size:15px; }
td { padding:15px; border-bottom:1px solid #eee; font-size:14px; color:#444; }

.status-active { color:green; font-weight:bold; }
.status-inactive { color:red; font-weight:bold; }

/* ACTION BUTTONS */
.action-btn {
    text-decoration:none;
    padding:7px 12px;
    border-radius:6px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}
.btn-disable { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.btn-activate { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }

/* PROFILE POPUP CSS */
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
            <a href="profile_admin.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="sidebar collapsed" id="sidebar">
    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($admin_name) ?></h3>
    </div>
    <a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
    <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</a>
    <a href="manage_students.php" class="active"><i class="fas fa-user-graduate"></i> Manage Students</a>
</div>

<div class="main-content full" id="mainContent">

    <div class="welcome-card">
        <h1>Student Management</h1>
        <p>Manage teachers, students, and system activity.</p>
    </div>

    <a href="download_students_pdf.php" class="btn-download">
        <i class="fas fa-file-pdf"></i> Download Students PDF
    </a>

    <div class="dashboard-grid">
        <a href="?view=all" class="menu-card">
            <i class="fas fa-users"></i>
            <h3>Total Students</h3>
            <div class="count"><?= $total_students ?></div>
        </a>
        <a href="?view=active" class="menu-card">
            <i class="fas fa-user-check"></i>
            <h3>Active Students</h3>
            <div class="count"><?= $active_students ?></div>
        </a>
        <a href="?view=inactive" class="menu-card">
            <i class="fas fa-user-times"></i>
            <h3>Inactive Students</h3>
            <div class="count"><?= $inactive_students ?></div>
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Admission ID</th>
                    <th>Email Address</th>
                    <th>Current Status</th>
                    <th>Management Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($students) > 0): ?>
                    <?php while($s = mysqli_fetch_assoc($students)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong><br><small>@<?= htmlspecialchars($s['username']) ?></small></td>
                        <td><?= htmlspecialchars($s['admission_id']) ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td>
                            <span class="status-<?= $s['status'] ?>">
                                <?= ucfirst($s['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($s['status'] === 'active'): ?>
                                <a href="?view=<?= $view ?>&disable=<?= $s['id'] ?>" class="action-btn btn-disable" onclick="return confirm('Deactivate this student?')">
                                    <i class="fas fa-user-slash"></i> Deactivate
                                </a>
                            <?php else: ?>
                                <a href="?view=<?= $view ?>&activate=<?= $s['id'] ?>" class="action-btn btn-activate">
                                    <i class="fas fa-user-check"></i> Activate
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:40px; color:gray;">No student records found in this category.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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

// Handle sidebar state
window.addEventListener('DOMContentLoaded', () => {
    if(sessionStorage.getItem('sidebar') === 'open'){
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }
});

menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
    sessionStorage.setItem('sidebar', sidebar.classList.contains('collapsed') ? 'closed' : 'open');
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

/* POPUP FUNCTIONS */
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