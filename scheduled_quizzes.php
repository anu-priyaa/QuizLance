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
date_default_timezone_set('Asia/Kolkata');

/* AUTO UPDATE QUIZ STATUS */
mysqli_query($conn,
    "UPDATE quizzes 
     SET status='completed' 
     WHERE teacher_id=$teacher_id 
       AND status IN ('scheduled','live') 
       AND end_time < NOW()"
);

mysqli_query($conn,
    "UPDATE quizzes 
     SET status='live' 
     WHERE teacher_id=$teacher_id 
       AND status='scheduled' 
       AND start_time <= NOW() 
       AND end_time >= NOW()"
);

/* FETCH TEACHER INFO */
$res = mysqli_query($conn,
    "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id"
);
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];
$imgSrc = $profile_pic ? $profile_pic . '?t=' . time() : 'https://via.placeholder.com/85';

/* DELETE QUIZ (SAFE) */
if (isset($_POST['confirm_delete'])) {

    $quiz_id = (int) $_POST['quiz_id'];

    $check = mysqli_query($conn,
        "SELECT status FROM quizzes 
         WHERE id=$quiz_id AND teacher_id=$teacher_id"
    );

    if ($row = mysqli_fetch_assoc($check)) {
        if ($row['status'] === 'draft' || $row['status'] === 'scheduled') {

            mysqli_query($conn, "DELETE FROM quiz_attempts WHERE quiz_id=$quiz_id");

            mysqli_query($conn,
                "DELETE sa FROM student_answers sa
                 JOIN questions q ON sa.question_id=q.id
                 WHERE q.quiz_id=$quiz_id"
            );

            mysqli_query($conn,
                "DELETE qa FROM question_answers qa
                 JOIN questions q ON qa.question_id=q.id
                 WHERE q.quiz_id=$quiz_id"
            );

            mysqli_query($conn,
                "DELETE qo FROM question_options qo
                 JOIN questions q ON qo.question_id=q.id
                 WHERE q.quiz_id=$quiz_id"
            );

            mysqli_query($conn, "DELETE FROM questions WHERE quiz_id=$quiz_id");
            mysqli_query($conn, "DELETE FROM quizzes WHERE id=$quiz_id");

            $success = "Quiz deleted successfully";
        } else {
            $error = "Live or completed quizzes cannot be deleted";
        }
    }
}

/* FETCH QUIZZES (NO class_code) */
$quizzes = mysqli_query($conn,
    "SELECT q.*, c.class_name
     FROM quizzes q
     JOIN Classes c ON q.class_id = c.id
     WHERE q.teacher_id=$teacher_id
     ORDER BY q.created_at DESC"
);
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
body{background:#f0f2f5}

/* TOP BAR */
.topbar{
    position:fixed;top:0;left:0;
    width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;
    padding:0 20px;z-index:1001
}
.topbar i{font-size:24px;cursor:pointer}

/* SIDEBAR */
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
.logout{
    margin-top:auto;
    border-top:1px solid rgba(255,255,255,.15)
}

/* MAIN CONTENT */
.main-content{
    margin-left:260px;
    padding:90px 40px 40px;
    transition:.3s ease
}
.main-content.full{margin-left:0}

/* PAGE CARD */
.page-card{
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,.05);
    border-left:5px solid #5d9415;
}
.page-card h1{color:#5A0E24;margin-bottom:10px}
.page-card p{margin-bottom:25px;color:#555}

/* TABLE */
table{width:100%;border-collapse:collapse}
th,td{
    padding:14px;
    border-bottom:1px solid #ddd;
    text-align:left
}
th{background:#5A0E24;color:white}

.status-draft{color:gray;font-weight:bold}
.status-scheduled{color:green;font-weight:bold}
.status-live{color:maroon;font-weight:bold}
.status-completed{color:#555;font-weight:bold}

.delete-btn{
    background:none;
    border:none;
    color:red;
    font-weight:bold;
    cursor:pointer
}

/* ALERTS */
.alert-success{color:green;font-weight:bold;margin-bottom:15px}
.alert-error{color:red;font-weight:bold;margin-bottom:15px}

/* PROFILE POPUP */
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
    font-size:22px;cursor:pointer
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    justify-content:center;
    align-items:center
}
.modal-content{
    background:white;
    padding:25px;
    border-radius:12px;
    width:320px;
    text-align:center
}
.confirm{background:#5d9415;color:white}
.cancel{background:#ccc}
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

    <a href="teacher_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="manage_classes.php"><i class="fas fa-users"></i> Manage Class</a>
    <a href="view_attendance_teacher.php"><i class="fas fa-clipboard-list"></i> Attendance</a>
    <a href="profile_teacher.php"><i class="fas fa-user-edit"></i> Profile</a>

    <div class="logout">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content full" id="mainContent">

<div class="page-card">
    <h1>Scheduled Quizzes</h1>
    <p>Manage your quizzes. Only draft and scheduled quizzes can be deleted.</p>

    <?php if(isset($success)) echo "<p class='alert-success'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p class='alert-error'>$error</p>"; ?>

    <table>
        <tr>
            <th>Title</th>
            <th>Class</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while($q=mysqli_fetch_assoc($quizzes)): ?>
        <tr>
            <td><?= htmlspecialchars($q['title']) ?></td>
            <td><?= htmlspecialchars($q['class_name']) ?></td>
            <td><?= $q['duration'] ?> min</td>
            <td class="status-<?= $q['status'] ?>"><?= ucfirst($q['status']) ?></td>
            <td>
                <?php if($q['status']=='draft' || $q['status']=='scheduled'): ?>
                    <button class="delete-btn" onclick="openModal(<?= $q['id'] ?>)">
                        Delete
                    </button>
                <?php else: ?> — <?php endif; ?>
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
        <h2><?= htmlspecialchars($teacher_name) ?></h2>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal" id="deleteModal">
<div class="modal-content">
<h3>Delete Quiz?</h3>
<p>This action cannot be undone.</p>
<form method="POST">
<input type="hidden" name="quiz_id" id="quiz_id">
<button class="confirm" name="confirm_delete">Yes, Delete</button>
<button type="button" class="cancel" onclick="closeModal()">Cancel</button>
</form>
</div>
</div>

<script>
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const mainContent=document.getElementById('mainContent');

/* restore sidebar */
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

/* profile popup */
function openProfilePopup(){
    document.getElementById('profilePopup').style.display='flex';
}
function closeProfilePopup(){
    document.getElementById('profilePopup').style.display='none';
}

/* delete modal */
function openModal(id){
    document.getElementById('quiz_id').value=id;
    document.getElementById('deleteModal').style.display='flex';
}
function closeModal(){
    document.getElementById('deleteModal').style.display='none';
}
</script>

</body>
</html>
