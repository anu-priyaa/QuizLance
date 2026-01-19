<?php
session_start();

/* ROLE CHECK */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

/* DB */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) die("DB Error");

$student_id = $_SESSION['user_id'];

/* STUDENT INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH STUDENT CLASS FROM class_students */
$classRes = mysqli_query($conn,
    "SELECT cs.class_id
     FROM class_students cs
     WHERE cs.student_id = $student_id"
);

$classRow = mysqli_fetch_assoc($classRes);

if (!$classRow) {
    die("You are not assigned to any class");
}

$class_id = (int)$classRow['class_id'];


/* FETCH ANNOUNCEMENTS */
$ann = mysqli_query($conn,
    "SELECT a.*, c.class_name
     FROM announcements a
     JOIN classes c ON a.class_id = c.id
     WHERE a.class_id = $class_id
       AND a.status = 'active'
     ORDER BY a.created_at DESC"
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Announcements | QuizLance</title>

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
    margin-left:auto;display:flex;align-items:center;
    gap:8px;cursor:pointer;position:relative;
}
.top-profile img{
    width:36px;height:36px;border-radius:50%;
    object-fit:cover;border:2px solid #5d9415;
}
.top-profile span{font-size:14px;font-weight:500;}

/* DROPDOWN */
.profile-dropdown{
    display:none;position:absolute;right:0;top:55px;
    background:white;border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
    min-width:180px;overflow:hidden;z-index:3000;
}
.profile-dropdown a{
    display:flex;align-items:center;gap:10px;
    padding:12px 15px;text-decoration:none;
    color:#333;font-size:14px;
}
.profile-dropdown a:hover{background:#f2f2f2;}

/* ===== MAIN ===== */
.main-content{
    padding:70px 40px 40px;
}

.card{
    background:white;padding:20px;border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

h2{color:#5A0E24;margin-bottom:20px;}

/* ANNOUNCEMENTS */
.announcement {
    border-left: 5px solid #5A0E24;
    background: #fafafa;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.announcement h4 {
    color: #5A0E24;
    margin-bottom: 6px;
}

.announcement small {
    color: #777;
}

/* PROFILE POPUP */
.profile-popup{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,0.5);
    z-index:2000;justify-content:center;align-items:center;
}
.profile-popup-content{
    background:white;padding:30px;border-radius:15px;
    text-align:center;position:relative;
}
.profile-popup-content img{
    width:200px;height:200px;border-radius:50%;
    border:4px solid #5d9415;object-fit:cover;
}
.close-btn{
    position:absolute;top:10px;right:14px;
    font-size:22px;cursor:pointer;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($student_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_student.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- MAIN -->
<div class="main-content">
    <a href="student_dashboard.php" style="display: inline-block; background: #5A0E24; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px;">← Back to Dashboard</a>
    <div class="card">
        <h2>📢 Announcements</h2>

        <?php if (mysqli_num_rows($ann) == 0): ?>
            <p>No announcements available.</p>
        <?php endif; ?>

        <?php while ($a = mysqli_fetch_assoc($ann)): ?>
            <div class="announcement">
                <h4><?= htmlspecialchars($a['title']) ?></h4>
                <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>
                <small>
                    Class: <?= htmlspecialchars($a['class_name']) ?> |
                    Posted on <?= date('d M Y, h:i A', strtotime($a['created_at'])) ?>
                </small>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- PROFILE POPUP -->
<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($student_name) ?></h2>
    </div>
</div>

<script>
function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p && !p.contains(e.target))
        document.getElementById('profileDropdown').style.display='none';
});
function openProfilePopup(){document.getElementById('profilePopup').style.display='flex';}
function closeProfilePopup(){document.getElementById('profilePopup').style.display='none';}
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
