<?php
session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");
if(!$conn){
    die("Database connection failed");
}

/* ✅ Logged in teacher */
$logged_teacher = $_SESSION['user_id'];

/* ✅ Get inputs safely */
$teacher_id = (int)$_GET['teacher_id'];
$class_id   = (int)$_GET['class_id'];

/* 🔒 CHECK: Is this teacher the CLASS TEACHER? */
$check = mysqli_query($conn,"
SELECT * FROM Classes 
WHERE id = $class_id AND teacher_id = $logged_teacher
");

if(mysqli_num_rows($check) == 0){
    die("Access denied"); // ❌ not allowed
}

/* ✅ If allowed → delete */
mysqli_query($conn,"
DELETE FROM class_subteachers 
WHERE teacher_id = $teacher_id AND class_id = $class_id
");

echo "success";
?>