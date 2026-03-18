<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");

$quiz_id = intval($_POST['quiz_id']);

// Mark quiz as finished
mysqli_query($conn,"
    UPDATE live_quizzes
    SET status='finished'
    WHERE id=$quiz_id
");

header("Location: live_quiz.php");
exit();
?>