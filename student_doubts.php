<?php
session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

/* POST DOUBT */

if(isset($_POST['post_doubt'])){

$question = mysqli_real_escape_string($conn,$_POST['question']);

mysqli_query($conn,"
INSERT INTO doubts (student_id,question,status)
VALUES ($student_id,'$question','pending')
");

header("Location: student_doubts.php");

}

/* FETCH DOUBTS */

$doubts = mysqli_query($conn,"
SELECT d.*, s.name
FROM doubts d
JOIN students s ON d.student_id=s.id
ORDER BY d.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Class Doubts</title>

<style>

body{
font-family:Segoe UI;
background:#f3f4f6;
padding:40px;
}

.container{
max-width:900px;
margin:auto;
}

.post-box{
background:white;
padding:20px;
border-radius:10px;
margin-bottom:25px;
box-shadow:0 3px 10px rgba(0,0,0,0.05);
}

textarea{
width:100%;
height:90px;
padding:10px;
border-radius:6px;
border:1px solid #ccc;
resize:none;
}

button{
background:#5A0E24;
color:white;
border:none;
padding:10px 18px;
margin-top:10px;
border-radius:6px;
cursor:pointer;
}

button:hover{
background:#7a1634;
}

.doubt-card{
background:white;
padding:18px;
border-radius:10px;
margin-bottom:15px;
box-shadow:0 3px 10px rgba(0,0,0,0.05);
}

.student{
font-weight:bold;
color:#5A0E24;
}

.time{
font-size:12px;
color:gray;
}

.answer{
background:#eef7ee;
padding:12px;
border-left:4px solid green;
margin-top:12px;
border-radius:6px;
}

.back-btn{
display:inline-block;
background:#5A0E24;
color:white;
padding:8px 16px;
border-radius:6px;
text-decoration:none;
margin-bottom:20px;
font-size:14px;
}

.back-btn:hover{
background:#7a1634;
}

</style>

</head>

<body>

<div class="container">

<a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>

<h2>Class Doubts</h2>

<div class="post-box">

<form method="POST">

<textarea name="question" placeholder="Ask your doubt..." required></textarea>

<br>

<button name="post_doubt">Post Doubt</button>

</form>

</div>


<?php

while($d = mysqli_fetch_assoc($doubts)){

echo "<div class='doubt-card'>";

if($d['student_id'] == $student_id){
    echo "<div class='student'>You</div>";
}else{
    echo "<div class='student'>".$d['name']."</div>";
}
echo "<p>".$d['question']."</p>";

echo "<div class='time'>".$d['created_at']."</div>";

if($d['answer']){

echo "<div class='answer'>";

echo "<b>Teacher Answer:</b><br>";

echo $d['answer'];

echo "</div>";

}

echo "</div>";

}

?>

</div>

</body>
</html>