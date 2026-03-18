<?php

$conn = mysqli_connect("localhost","root","","QuizLance");

$quiz_id = intval($_GET['quiz_id']);

mysqli_query($conn,"
UPDATE live_quizzes
SET payment_status='paid'
WHERE id=$quiz_id
");

header("Location: start_live_quiz.php?quiz_id=$quiz_id");

?>