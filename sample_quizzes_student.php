<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");
if(!$conn){
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];

/* FETCH STUDENT INFO */

$res = mysqli_query($conn,"SELECT name,profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic ? $profile_pic.'?t='.time() : "https://via.placeholder.com/85";


/* FETCH SAMPLE QUIZZES */

$quizzes = mysqli_query($conn,"
SELECT sq.*, t.name AS teacher_name
FROM sample_quizzes sq
JOIN Teachers t ON sq.teacher_id = t.id
WHERE sq.status='posted'
ORDER BY sq.id DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sample Quizzes | QuizLance</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif}
body{background:#f0f2f5}

/* TOPBAR */

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

/* PROFILE */

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

.profile-dropdown{
display:none;
position:absolute;
right:0;
top:55px;
background:white;
border-radius:8px;
box-shadow:0 6px 20px rgba(0,0,0,0.15);
min-width:180px
}

.profile-dropdown a{
display:flex;
align-items:center;
gap:10px;
padding:12px 15px;
text-decoration:none;
color:#333
}

.profile-dropdown a:hover{
background:#f2f2f2
}

/* MAIN */

.main-content{
padding:80px 40px
}

/* CARD */

.card{
background:white;
padding:25px;
border-radius:15px;
box-shadow:0 4px 12px rgba(0,0,0,.05);
border-left:5px solid #5d9415
}

.card h2{
color:#5A0E24;
margin-bottom:20px
}

/* TABLE */

table{
width:100%;
border-collapse:collapse
}

th,td{
padding:14px;
border-bottom:1px solid #ddd
}

th{
background:#5A0E24;
color:white;
text-align:left
}

.attempt-btn{
background:#5d9415;
color:white;
padding:8px 14px;
border-radius:6px;
text-decoration:none;
font-weight:bold
}

.attempt-btn:hover{
opacity:0.9
}

</style>

</head>

<body>

<!-- TOPBAR -->

<div class="topbar">

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


<div class="main-content">

<a href="student_dashboard.php"
style="display:inline-block;background:#5A0E24;color:white;padding:10px 18px;border-radius:6px;text-decoration:none;font-weight:bold;margin-bottom:20px;">
← Back to Dashboard
</a>

<div class="card">

<h2>Sample Quizzes</h2>

<table>

<tr>
<th>Quiz Title</th>
<th>Teacher</th>
<th>Action</th>
</tr>

<?php if(mysqli_num_rows($quizzes)==0): ?>

<tr>
<td colspan="3" style="text-align:center;color:#777">
No sample quizzes available.
</td>
</tr>

<?php else: ?>

<?php while($q=mysqli_fetch_assoc($quizzes)): ?>

<tr>

<td><?= htmlspecialchars($q['title']) ?></td>

<td><?= htmlspecialchars($q['teacher_name']) ?></td>

<td>
<a class="attempt-btn"
href="attempt_sample_quiz.php?quiz_id=<?= $q['id'] ?>">
Attempt
</a>
</td>

</tr>

<?php endwhile; ?>

<?php endif; ?>

</table>

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

</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>