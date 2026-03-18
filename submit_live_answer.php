<?php
session_start();
$conn = mysqli_connect("localhost","root","","QuizLance");

$quiz_id = $_POST['quiz_id'];
$question_id = $_POST['question_id'];
$option_id = $_POST['option_id'];
$student_id = $_SESSION['user_id'];

/* Prevent duplicate answer */
$check = mysqli_query($conn,"
   SELECT * FROM live_answers
   WHERE quiz_id=$quiz_id
   AND student_id=$student_id
   AND question_id=$question_id
");

if(mysqli_num_rows($check) > 0){
    exit("Already answered");
}

/* Check correctness */
$opt = mysqli_query($conn,"
   SELECT is_correct FROM question_options
   WHERE id=$option_id
");

$data = mysqli_fetch_assoc($opt);
$is_correct = $data['is_correct'];

/* Get marks */
$q = mysqli_query($conn,"
   SELECT marks FROM questions WHERE id=$question_id
");

$qdata = mysqli_fetch_assoc($q);
$marks = $qdata['marks'];

/* Save answer */
mysqli_query($conn,"
    INSERT INTO live_answers
    (quiz_id, student_id, question_id, selected_option, is_correct)
    VALUES ($quiz_id,$student_id,$question_id,$option_id,$is_correct)
");

/* Update score */
if($is_correct){
    mysqli_query($conn,"
        UPDATE live_participants
        SET score = score + $marks
        WHERE quiz_id=$quiz_id AND student_id=$student_id
    ");
}

echo "done";
?>