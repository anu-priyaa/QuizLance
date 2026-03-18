<?php
$conn = mysqli_connect("localhost","root","","QuizLance");

$quiz_id = $_POST['quiz_id'];

/* Get live quiz info */
$live = mysqli_query($conn,"
    SELECT * FROM live_quizzes WHERE id=$quiz_id
");

$data = mysqli_fetch_assoc($live);

$current = $data['current_question'];
$real_quiz_id = $data['quiz_id'];

/* Count total questions */
$total_q = mysqli_query($conn,"
    SELECT COUNT(*) as total FROM questions
    WHERE quiz_id=$real_quiz_id
");

$total_data = mysqli_fetch_assoc($total_q);
$total = $total_data['total'];

if($current >= $total){
    mysqli_query($conn,"
        UPDATE live_quizzes
        SET status='finished'
        WHERE id=$quiz_id
    ");
}else{
    mysqli_query($conn,"
        UPDATE live_quizzes
        SET current_question = current_question + 1
        WHERE id=$quiz_id
    ");
}

header("Location: live_quiz.php");
?>