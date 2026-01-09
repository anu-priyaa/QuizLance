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

/* CREATE CLASS */
if (isset($_POST['create_class'])) {
    $class_name = trim(mysqli_real_escape_string($conn, $_POST['class_name']));

    if ($class_name === '') {
        $error = "Class name is required";
    } else {
        do {
            $class_code = strtoupper(substr(md5(uniqid()), 0, 6));
            $check = mysqli_query($conn, "SELECT id FROM Classes WHERE class_code='$class_code'");
        } while (mysqli_num_rows($check) > 0);

        mysqli_query($conn,
            "INSERT INTO Classes (teacher_id, class_name, class_code)
             VALUES ($teacher_id, '$class_name', '$class_code')"
        );

        $success = "Class created successfully";
    }
}

/* FETCH CLASSES */
$classes = mysqli_query(
    $conn,
    "SELECT * FROM Classes WHERE teacher_id=$teacher_id ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
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

/* Prevent animation on page load */
.sidebar.no-transition {
    transition: none !important;
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

/* ===== CARD ===== */
.card {
    background:white;
    padding:30px;
    border-radius:15px;
    max-width:500px;
    margin-bottom:30px;
    border-left:5px solid #5d9415;
}

.card h2 { color:#5A0E24; margin-bottom:20px; }

.form-group { margin-bottom:15px; }
.form-group input {
    width:100%;
    padding:10px;
    border-radius:5px;
    border:1px solid #ccc;
}

.btn {
    background:#5d9415;
    color:white;
    padding:10px 18px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

.alert-success { color:green; font-weight:bold; margin-top:10px; }
.alert-error { color:red; font-weight:bold; margin-top:10px; }

/* ===== CLASS GRID ===== */
.class-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px,1fr));
    gap:20px;
}

.class-card {
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
    border-bottom:4px solid #5d9415;
}

.class-card h3 { color:#5A0E24; }

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
    width:300px;
    text-align:center;
}

.profile-popup-content img {
    width:200px;
    height:200px;
    border-radius:50%;
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

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>
</div>

<!-- SIDEBAR -->
<div class="sidebar collapsed no-transition" id="sidebar">


    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>

    <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes.php" class="active"><i class="fas fa-users"></i> My Classes</a>
    <a href="view_attendance_teacher.php"><i class="fas fa-clipboard-list"></i> Attendance</a>
    <a href="profile_teacher.php"><i class="fas fa-user-edit"></i> Profile</a>

    <div class="logout">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content full" id="mainContent">

    <div class="card">
        <h2>Create Class</h2>
        <form method="POST">
            <div class="form-group">
                <label>Class Name</label>
                <input type="text" name="class_name" required>
            </div>
            <button type="submit" name="create_class" class="btn">Create Class</button>
        </form>

        <?php if (isset($success)) echo "<div class='alert-success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert-error'>$error</div>"; ?>
    </div>

    <h2>My Classes</h2>

    <div class="class-grid">
        <?php while ($row = mysqli_fetch_assoc($classes)): ?>
        <div class="class-card">
            <h3><?= htmlspecialchars($row['class_name']) ?></h3>
            <p>Class Code</p>
            <strong><?= htmlspecialchars($row['class_code']) ?></strong>
        </div>
        <?php endwhile; ?>
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

/* ===== RESTORE SIDEBAR STATE (NO POP-UP) ===== */
window.addEventListener('DOMContentLoaded', () => {
    const state = sessionStorage.getItem('sidebar');

    if (state === 'open') {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }

    /* Enable animation AFTER layout is correct */
    setTimeout(() => {
        sidebar.classList.remove('no-transition');
    }, 50);
});

/* ===== TOGGLE ONLY ON MENU CLICK ===== */
menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');

    if (sidebar.classList.contains('collapsed')) {
        sessionStorage.setItem('sidebar', 'closed');
    } else {
        sessionStorage.setItem('sidebar', 'open');
    }
};

/* PROFILE POPUP */
function openProfilePopup() {
    document.getElementById('profilePopup').style.display = 'flex';
}
function closeProfilePopup() {
    document.getElementById('profilePopup').style.display = 'none';
}
</script>




</body>
</html>
