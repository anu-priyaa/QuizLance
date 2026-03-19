<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");
if(!$conn){
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kolkata');

/* FETCH TEACHER INFO */
$res = mysqli_query($conn,
"SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id"
);

$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic = $teacher['profile_pic'];

$imgSrc = $profile_pic ? $profile_pic.'?t='.time() : "https://via.placeholder.com/85";


/* DELETE SAMPLE QUIZ */

if(isset($_POST['confirm_delete'])){

$quiz_id = (int)$_POST['quiz_id'];

mysqli_query($conn,"
DELETE qa FROM question_answers qa
JOIN questions q ON qa.question_id=q.id
WHERE q.quiz_id=$quiz_id
");

mysqli_query($conn,"
DELETE qo FROM question_options qo
JOIN questions q ON qo.question_id=q.id
WHERE q.quiz_id=$quiz_id
");

mysqli_query($conn,"\nDELETE sa FROM student_answers sa\nJOIN questions q ON sa.question_id=q.id\nWHERE q.quiz_id=$quiz_id\n");

mysqli_query($conn,"DELETE FROM questions WHERE quiz_id=$quiz_id");

mysqli_query($conn,"DELETE FROM sample_quizzes WHERE id=$quiz_id");

$success="Sample quiz deleted successfully";

}


/* FETCH SAMPLE QUIZZES */

$quizzes = mysqli_query($conn,
"
SELECT DISTINCT q.*, c.class_name
FROM sample_quizzes q
JOIN Classes c ON q.class_id=c.id
LEFT JOIN Class_SubTeachers st ON c.id=st.class_id
WHERE
q.teacher_id=$teacher_id
OR st.teacher_id=$teacher_id
ORDER BY q.created_at DESC
");

?>
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sample Quizzes | QuizLance</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
background:#f0f2f5
}

/* TOP BAR */

.topbar{
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
z-index:1001
}

.top-profile{
margin-left:auto;
display:flex;
align-items:center;
gap:8px;
cursor:pointer;
position:relative
}

.top-profile img{
width:36px;
height:36px;
border-radius:50%;
object-fit:cover;
border:2px solid #5d9415
}

.top-profile span{
font-size:14px;
font-weight:500
}

/* DROPDOWN */

.profile-dropdown{
display:none;
position:absolute;
right:0;
top:55px;
background:white;
border-radius:8px;
box-shadow:0 6px 20px rgba(0,0,0,0.15);
min-width:180px;
overflow:hidden;
z-index:3000
}

.profile-dropdown a{
display:flex;
align-items:center;
gap:10px;
padding:12px 15px;
text-decoration:none;
color:#333;
font-size:14px
}

.profile-dropdown a:hover{
background:#f2f2f2
}

/* MAIN CONTENT */

.main-content{
padding:70px 40px 40px
}

/* PAGE CARD */

.page-card{
background:white;
padding:20px;
border-radius:15px;
box-shadow:0 4px 12px rgba(0,0,0,.05);
border-left:5px solid #5d9415
}

.page-card h1{
color:#5A0E24;
margin-bottom:10px
}

.page-card p{
margin-bottom:25px;
color:#555
}

/* TABLE */

table{
width:100%;
border-collapse:collapse
}

th,td{
padding:14px;
border-bottom:1px solid #ddd;
text-align:left
}

th{
background:#5A0E24;
color:white
}

/* BUTTON */

.create-btn{
background:#5d9415;
color:white;
padding:10px 18px;
border-radius:6px;
text-decoration:none;
font-weight:bold;
margin-bottom:20px;
display:inline-block
}

.delete-btn{
background:none;
border:none;
color:red;
font-weight:bold;
cursor:pointer
}

/* ALERTS */

.alert-success{
color:green;
font-weight:bold;
margin-bottom:15px
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

.confirm{
background:#5d9415;
color:white
}

.cancel{
background:#ccc
}

</style>
</head>

<body>


<!-- TOP BAR -->

<div class="topbar">

<div class="top-profile" onclick="toggleProfileMenu()">

<img src="<?= $imgSrc ?>">

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


<!-- MAIN CONTENT -->

<div class="main-content">

<a href="teacher_dashboard.php"
style="display:inline-block;background:#5A0E24;color:white;padding:10px 18px;border-radius:6px;text-decoration:none;font-weight:bold;margin-bottom:20px;">
← Back to Dashboard
</a>


<div class="page-card">

<h1>Sample Quizzes</h1>

<p>Create practice quizzes for students. Students can attempt unlimited times and see answers.</p>

<a href="create_sample_quiz.php" class="create-btn">
<i class="fas fa-plus"></i> Create Sample Quiz
</a>

<?php if(isset($success)) echo "<p class='alert-success'>$success</p>"; ?>


<table>

<tr>
<th>Title</th>
<th>Class</th>
<th>Created</th>
<th>Action</th>
</tr>


<?php while($q=mysqli_fetch_assoc($quizzes)): ?>

<tr>

<td><?= htmlspecialchars($q['title']) ?></td>

<td><?= htmlspecialchars($q['class_name']) ?></td>

<td><?= date("d M Y",strtotime($q['created_at'])) ?></td>

<td>

<button class="delete-btn" onclick="openModal(<?= $q['id'] ?>)">
Delete
</button>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>

</div>



<!-- DELETE MODAL -->

<div class="modal" id="deleteModal">

<div class="modal-content">

<h3>Delete Sample Quiz?</h3>

<p>This action cannot be undone.</p>

<form method="POST">

<input type="hidden" name="quiz_id" id="quiz_id">

<button class="confirm" name="confirm_delete">
Yes Delete
</button>

<button type="button" class="cancel" onclick="closeModal()">
Cancel
</button>

</form>

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

function openModal(id){
document.getElementById('quiz_id').value=id;
document.getElementById('deleteModal').style.display='flex';
}

function closeModal(){
document.getElementById('deleteModal').style.display='none';
}

</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>