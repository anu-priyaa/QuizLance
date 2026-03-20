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
$res = mysqli_query(
    $conn,
    "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id"
);
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];
$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* RESTORE CLASS */
if (isset($_POST['restore_class'])) {
    $class_id = (int)$_POST['class_id'];

    mysqli_query(
        $conn,
        "UPDATE Classes
         SET status='active'
         WHERE id=$class_id AND teacher_id=$teacher_id"
    );

    $success = "Class restored successfully";
}

/* FETCH ARCHIVED CLASSES */
$archived = mysqli_query(
    $conn,
    "SELECT id, class_name, created_at
     FROM Classes
     WHERE teacher_id=$teacher_id AND status='deleted'
     ORDER BY created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Archived Classes | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* === SAME CSS STRUCTURE AS YOUR OTHER TEACHER PAGES === */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

/* TOP BAR */
.topbar{
    position:fixed;top:0;left:0;width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;padding:0 20px;z-index:1001;
}
.topbar i{font-size:24px;cursor:pointer;}

/* TOP PROFILE */
.top-profile{
    margin-left:auto;display:flex;align-items:center;
    gap:8px;cursor:pointer;position:relative;
}
.top-profile img{
    width:36px;height:36px;border-radius:50%;
    border:2px solid #5d9415;object-fit:cover;
}
.profile-dropdown{
    display:none;position:absolute;right:0;top:55px;
    background:white;border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,.15);
    min-width:180px;z-index:3000;
}
.profile-dropdown a{
    display:flex;gap:10px;padding:12px 15px;
    text-decoration:none;color:#333;font-size:14px;
}
.profile-dropdown a:hover{background:#f2f2f2;}

/* SIDEBAR */
.sidebar{
    width:260px;background:#5A0E24;color:white;
    position:fixed;top:60px;left:0;
    height:calc(100vh - 60px);
    display:flex;flex-direction:column;
    transition:.3s ease;
}
.sidebar.collapsed{transform:translateX(-100%);}
.sidebar.no-transition{transition:none!important;}
.sidebar-profile{
    text-align:center;padding:25px;
    border-bottom:1px solid rgba(255,255,255,.15);
}
.sidebar-profile img{
    width:85px;height:85px;border-radius:50%;
    border:3px solid #5d9415;object-fit:cover;
}
.sidebar-profile h3{margin-top:10px;font-size:16px;}
.sidebar a{
    padding:15px 25px;text-decoration:none;
    color:#d1d1d1;display:flex;align-items:center;
}
.sidebar a i{margin-right:15px;width:20px;}
.sidebar a:hover,.sidebar a.active{
    background:#861434;color:white;
}

/* MAIN */
.main-content{
    margin-left:260px;padding:90px 40px;
    transition:.3s ease;
}
.main-content.full{margin-left:0;}

/* PAGE CARD */
.page-card{
    background:white;padding:30px;border-radius:15px;
    border-left:5px solid #5d9415;
    margin-bottom:30px;
}

/* CLASS CARD */
.class-card{
    background:#f9f9f9;
    padding:20px;
    border-radius:12px;
    border-left:5px solid #999;
    margin-bottom:15px;
}

.restore-btn{
    margin-top:10px;
    background:#5d9415;color:white;
    padding:8px 14px;border:none;border-radius:6px;
    cursor:pointer;font-weight:bold;
}
.restore-btn:hover{background:#4e7d12;}

.success{color:green;font-weight:bold;margin-bottom:15px;}
.empty{color:#777;}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>

    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_teacher.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>

    <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="manage_classes.php"><i class="fas fa-tasks"></i> Manage Class</a>
    <a href="archived_classes.php" class="active">
        <i class="fas fa-archive"></i> Archived Classes
    </a>
    <a href="view_students.php"><i class="fas fa-eye"></i> View Students</a>
    <a href="view_attendance_teacher.php"><i class="fas fa-clipboard-list"></i> Attendance</a>
</div>

<!-- MAIN -->
<div class="main-content full" id="mainContent">

<?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>

<div class="page-card">
    <h2 style="color:#5A0E24;margin-bottom:10px;">Archived Classes</h2>
    <p style="color:#555;margin-bottom:20px;">
        Restore accidentally deleted classes. All quizzes, students, and attendance will be restored.
    </p>

    <?php if (mysqli_num_rows($archived) === 0): ?>
        <p class="empty">No archived classes found.</p>
    <?php endif; ?>

    <?php while ($c = mysqli_fetch_assoc($archived)): ?>
        <div class="class-card">
            <h3><?= htmlspecialchars($c['class_name']) ?></h3>
            <small>
                Created on <?= date("d M Y, h:i A", strtotime($c['created_at'])) ?>
            </small>

            <form method="POST">
                <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                <button name="restore_class" class="restore-btn">
                    <i class="fas fa-undo"></i> Restore Class
                </button>
            </form>
        </div>
    <?php endwhile; ?>
</div>

</div>

<script>
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const mainContent=document.getElementById('mainContent');

window.addEventListener('DOMContentLoaded',()=>{
    const s=sessionStorage.getItem('sidebar');
    if(s==='open'){sidebar.classList.remove('collapsed');mainContent.classList.remove('full');}
    setTimeout(()=>sidebar.classList.remove('no-transition'),50);
});

menuToggle.onclick=()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
    sessionStorage.setItem('sidebar',sidebar.classList.contains('collapsed')?'closed':'open');
};

function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p&&!p.contains(e.target))
        document.getElementById('profileDropdown').style.display='none';
});
</script>

<?php include 'includes/auto_logout.php'; ?>
</body>
</html>
