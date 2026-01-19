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
$res = mysqli_query($conn,
    "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id"
);
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];
$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* ANSWER DOUBT */
if (isset($_POST['reply'])) {
    $doubt_id = (int)$_POST['doubt_id'];
    $answer   = trim(mysqli_real_escape_string($conn, $_POST['answer']));

    if ($answer !== '') {
        mysqli_query(
            $conn,
            "UPDATE doubts
             SET answer='$answer', status='answered'
             WHERE id=$doubt_id AND teacher_id=$teacher_id"
        );
    }
}

/* FETCH DOUBTS */
$doubts = mysqli_query(
    $conn,
    "SELECT d.*, s.name AS student_name, c.class_name
     FROM doubts d
     JOIN Students s ON d.student_id = s.id
     JOIN Classes c ON d.class_id = c.id
     WHERE d.teacher_id = $teacher_id
     ORDER BY d.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Doubts | QuizLance</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5}

/* ===== TOP BAR ===== */
.topbar{
    position:fixed;top:0;left:0;
    width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;
    padding:0 20px;z-index:1001
}
.topbar i{font-size:24px;cursor:pointer}

/* ===== SIDEBAR ===== */
.sidebar{
    width:260px;background:#5A0E24;color:white;
    display:flex;flex-direction:column;
    position:fixed;top:60px;left:0;
    height:calc(100vh - 60px);
    transition:.3s ease;z-index:1000
}
.sidebar.collapsed{transform:translateX(-100%)}
.sidebar.no-transition{transition:none!important}

.sidebar-profile{
    text-align:center;padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,.15);
    cursor:pointer
}
.sidebar-profile img{
    width:85px;height:85px;
    border-radius:50%;
    border:3px solid #5d9415;
    object-fit:cover
}
.sidebar-profile h3{margin-top:10px;font-size:16px}

.sidebar a{
    padding:15px 25px;
    text-decoration:none;
    color:#d1d1d1;
    display:flex;align-items:center
}
.sidebar a i{margin-right:15px;width:20px}
.sidebar a:hover,.sidebar a.active{
    background:#861434;color:white
}

/* ===== MAIN ===== */
.main-content{
    margin-left:260px;
    padding:90px 40px 40px;
    transition:.3s ease
}
.main-content.full{margin-left:0}

/* ===== PAGE CARD ===== */
.page-card{
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,.05);
    border-left:5px solid #5d9415;
}

.page-card h1{
    color:#5A0E24;
    margin-bottom:20px;
}

/* ===== DOUBT CARD ===== */
.doubt-card{
    background:#f9f9f9;
    padding:20px;
    border-radius:12px;
    margin-bottom:20px;
    border-left:5px solid #5d9415;
}

.badge{
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}
.pending{background:#ff9800;color:white}
.answered{background:#4caf50;color:white}

textarea{
    width:100%;
    padding:10px;
    border-radius:6px;
    border:1px solid #ccc;
    margin-top:10px;
}

button{
    background:#5d9415;
    color:white;
    padding:8px 16px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    margin-top:10px;
}
button:hover{background:#4e7d12}

/* ===== PROFILE POPUP ===== */
.profile-popup{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:2000;
    justify-content:center;
    align-items:center
}
.profile-popup-content{
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    position:relative
}
.profile-popup-content img{
    width:200px;height:200px;
    border-radius:50%;
    border:4px solid #5d9415;
    object-fit:cover
}
.close-btn{
    position:absolute;
    top:10px;right:14px;
    font-size:22px;
    cursor:pointer
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
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="manage_classes.php"><i class="fas fa-users"></i> Manage Class</a>
    <a href="view_students.php"><i class="fas fa-eye"></i> View Students</a>
    <a href="view_attendance_teacher.php"><i class="fas fa-clipboard-list"></i> Attendance</a>

</div>

<!-- MAIN CONTENT -->
<div class="main-content full" id="mainContent">

<div class="page-card">
    <h1>Student Doubts</h1>

    <?php if (mysqli_num_rows($doubts) === 0): ?>
        <p>No doubts raised yet.</p>
    <?php endif; ?>

    <?php while ($d = mysqli_fetch_assoc($doubts)): ?>
        <div class="doubt-card">
            <h3>
                <?= htmlspecialchars($d['student_name']) ?>
                <span class="badge <?= $d['status'] ?>">
                    <?= ucfirst($d['status']) ?>
                </span>
            </h3>

            <p><b>Class:</b> <?= htmlspecialchars($d['class_name']) ?></p>
            <p style="margin-top:10px;">
                <b>Question:</b><br>
                <?= nl2br(htmlspecialchars($d['question'])) ?>
            </p>

            <?php if ($d['status'] === 'answered'): ?>
                <p style="margin-top:10px;color:#4caf50;">
                    <b>Answer:</b><br>
                    <?= nl2br(htmlspecialchars($d['answer'])) ?>
                </p>
            <?php else: ?>
                <form method="POST">
                    <textarea name="answer" placeholder="Type your answer..." required></textarea>
                    <input type="hidden" name="doubt_id" value="<?= $d['id'] ?>">
                    <button name="reply">Submit Answer</button>
                </form>
            <?php endif; ?>
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
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const mainContent=document.getElementById('mainContent');

/* restore sidebar state */
window.addEventListener('DOMContentLoaded',()=>{
    const state=sessionStorage.getItem('sidebar');
    if(state==='open'){
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }
    setTimeout(()=>sidebar.classList.remove('no-transition'),50);
});

/* toggle sidebar */
menuToggle.onclick=()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
    sessionStorage.setItem(
        'sidebar',
        sidebar.classList.contains('collapsed')?'closed':'open'
    );
};

function openProfilePopup(){
    document.getElementById('profilePopup').style.display='flex';
}
function closeProfilePopup(){
    document.getElementById('profilePopup').style.display='none';
}
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
