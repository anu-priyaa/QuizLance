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


/* FETCH ASSIGNED CLASSES */

$classes = mysqli_query($conn,"
SELECT 
    c.class_name,
    c.created_at,
    CASE 
        WHEN c.teacher_id = $teacher_id THEN 'Class Teacher'
        ELSE 'Sub Teacher'
    END AS role
FROM Classes c
LEFT JOIN Class_SubTeachers st ON c.id = st.class_id
WHERE 
    c.teacher_id = $teacher_id
    OR st.teacher_id = $teacher_id
ORDER BY c.created_at DESC
");

?>
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Assigned Classes | QuizLance</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

/* SAME CSS AS YOUR SAMPLE QUIZ PAGE — NO CHANGE */

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
background:#f0f2f5
}

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

.main-content{
padding:70px 40px 40px
}

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

<h1>Assigned Classes</h1>

<p>These are the classes assigned to you. You can manage and conduct quizzes for these classes.</p>

<table>

<tr>
<th>Class Name</th>
<th>Created On</th>
<th>Role</th>
</tr>

<?php while($c=mysqli_fetch_assoc($classes)): ?>

<tr>
<td><?= htmlspecialchars($c['class_name']) ?></td>
<td><?= date("d M Y",strtotime($c['created_at'])) ?></td>
<td><?= $c['role'] ?></td>
</tr>

<?php endwhile; ?>

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