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

/* TEACHER INFO */

$res = mysqli_query($conn,
"SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");

$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];

$imgSrc = $profile_pic ? $profile_pic.'?t='.time() : "https://via.placeholder.com/85";


/* FETCH CLASSES */

$classes = mysqli_query($conn,
"
SELECT DISTINCT c.*
FROM Classes c
LEFT JOIN Class_SubTeachers st ON c.id=st.class_id
WHERE c.teacher_id=$teacher_id OR st.teacher_id=$teacher_id
ORDER BY c.class_name
");


/* CREATE SAMPLE QUIZ */

if(isset($_POST['create_quiz'])){

$title = mysqli_real_escape_string($conn,$_POST['title']);
$class_id = (int)$_POST['class_id'];

mysqli_query($conn,
"INSERT INTO sample_quizzes (teacher_id,class_id,title)
VALUES ($teacher_id,$class_id,'$title')");

$quiz_id = mysqli_insert_id($conn);

/* redirect to add questions */

header("Location:add_sample_questions.php?quiz_id=".$quiz_id);
exit();

}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Create Sample Quiz | QuizLance</title>

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

.top-profile span{
font-size:14px;
font-weight:500
}

.profile-dropdown{
display:none;
position:absolute;
right:0;
top:55px;
background:white;
border-radius:8px;
box-shadow:0 6px 20px rgba(0,0,0,0.15);
min-width:180px;
overflow:hidden
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


/* MAIN */

.main-content{
padding:80px 40px
}


/* CARD */

.form-card{
background:white;
padding:25px;
border-radius:15px;
box-shadow:0 4px 12px rgba(0,0,0,.05);
border-left:5px solid #5d9415;
max-width:600px
}

.form-card h1{
color:#5A0E24;
margin-bottom:20px
}


/* FORM */

label{
font-weight:bold;
display:block;
margin-top:15px
}

input,select{
width:100%;
padding:10px;
margin-top:5px;
border:1px solid #ccc;
border-radius:6px
}

button{
margin-top:20px;
background:#5d9415;
color:white;
border:none;
padding:12px 18px;
border-radius:6px;
cursor:pointer;
font-weight:bold
}

button:hover{
background:#4e7e12
}

</style>

</head>

<body>


<!-- TOPBAR -->

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

<a href="sample_quizzes.php"
style="display:inline-block;background:#5A0E24;color:white;padding:10px 18px;border-radius:6px;text-decoration:none;font-weight:bold;margin-bottom:20px;">
← Back
</a>


<div class="form-card">

<h1>Create Sample Quiz</h1>

<form method="POST">

<label>Quiz Title</label>
<input type="text" name="title" required>


<label>Select Class</label>

<select name="class_id" required>

<option value="">Select Class</option>

<?php while($c=mysqli_fetch_assoc($classes)): ?>

<option value="<?= $c['id'] ?>">
<?= htmlspecialchars($c['class_name']) ?>
</option>

<?php endwhile; ?>

</select>


<button name="create_quiz">
<i class="fas fa-plus"></i> Create Quiz
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

</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>