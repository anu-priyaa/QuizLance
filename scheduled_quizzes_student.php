<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];

/* STUDENT INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH SCHEDULED QUIZZES */
$quizzes = mysqli_query(
    $conn,
    "SELECT q.id, q.title, q.start_time, q.end_time, c.class_name
     FROM quizzes q
     JOIN Classes c ON q.class_id = c.id
     JOIN class_students cs ON cs.class_id = c.id
     WHERE cs.student_id = $student_id
     ORDER BY q.start_time ASC"
);

$now = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scheduled Quizzes | QuizLance</title>

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

/* ===== SIDEBAR ===== */
.sidebar{
    width:260px;background:#5A0E24;color:white;
    display:flex;flex-direction:column;
    position:fixed;top:60px;left:0;
    height:calc(100vh - 60px);
    transition:0.3s ease;z-index:1000;
}
.sidebar.collapsed{transform:translateX(-100%);}
.sidebar.no-transition{transition:none!important;}

.sidebar-profile{
    text-align:center;padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    cursor:pointer;
}
.sidebar-profile img{
    width:85px;height:85px;border-radius:50%;
    object-fit:cover;border:3px solid #5d9415;
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

/* ===== MAIN ===== */
.main-content{
    margin-left:260px;
    padding:90px 40px 40px;
    transition:0.3s ease;
}
.main-content.full{margin-left:0;}

.card{
    background:white;padding:30px;border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

h2{color:#5A0E24;margin-bottom:20px;}

/* TABLE */
table{width:100%;border-collapse:collapse;}
th,td{padding:14px;border-bottom:1px solid #ddd;text-align:left;}
th{background:#5A0E24;color:white;}

.btn{
    padding:6px 12px;border-radius:5px;
    font-weight:bold;font-size:14px;
}
.btn-live{background:#5d9415;color:white;}
.btn-upcoming{background:#999;color:white;}
.btn-expired{background:#ccc;color:#333;}

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
    <i class="fas fa-bars" id="menuToggle"></i>

    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($student_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_student.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php" class= "active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes_student.php"><i class="fas fa-chalkboard"></i> My Classes</a>
    <a href="view_result_student.php"><i class="fas fa-chart-line"></i> Results</a>
    <a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
</div>

<!-- MAIN -->
<div class="main-content full" id="mainContent">
    <div class="card">
        <h2>Scheduled Quizzes</h2>

        <table>
            <tr>
                <th>Quiz Title</th>
                <th>Class</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Action</th>
            </tr>

            <?php while ($q = mysqli_fetch_assoc($quizzes)):
                $start = strtotime($q['start_time']);
                $end   = strtotime($q['end_time']);
            ?>
            <tr>
                <td><?= htmlspecialchars($q['title']) ?></td>
                <td><?= htmlspecialchars($q['class_name']) ?></td>
                <td><?= date("d M Y, h:i A", $start) ?></td>
                <td><?= date("d M Y, h:i A", $end) ?></td>
                <td>
                    <?php if ($now >= $start && $now <= $end): ?>
                        <a href="attempt_quiz.php?quiz_id=<?= $q['id'] ?>" class="btn btn-live">Attempt</a>
                    <?php elseif ($now < $start): ?>
                        <span class="btn btn-upcoming">Upcoming</span>
                    <?php else: ?>
                        <span class="btn btn-expired">Expired</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
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
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const mainContent=document.getElementById('mainContent');

window.addEventListener('DOMContentLoaded',()=>{
    const state=sessionStorage.getItem('sidebar');
    if(state==='open'){
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }
    setTimeout(()=>sidebar.classList.remove('no-transition'),50);
});

menuToggle.onclick=()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
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
    if(p && !p.contains(e.target))
        document.getElementById('profileDropdown').style.display='none';
});
function openProfilePopup(){document.getElementById('profilePopup').style.display='flex';}
function closeProfilePopup(){document.getElementById('profilePopup').style.display='none';}
</script>

</body>
</html>
