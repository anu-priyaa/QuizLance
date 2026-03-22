<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "QuizLance");

if(isset($_POST['quiz_id'])){
    $live_id = intval($_POST['quiz_id']);
    
    // Increment the current_question number by 1
    mysqli_query($conn, "UPDATE live_quizzes SET current_question = current_question + 1, status = 'running' WHERE id = $live_id");
}

header("Location: live_quiz.php");
exit();