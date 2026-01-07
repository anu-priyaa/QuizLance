<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];

if (!isset($_POST['attempt_id'], $_POST['quiz_id'], $_POST['answer'])) {
    die("Invalid submission");
}

$attempt_id = (int)$_POST['attempt_id'];
$quiz_id    = (int)$_POST['quiz_id'];
$answers    = $_POST['answer'];

$total_score = 0;
$total_marks = 0;

/* ===============================
   LOOP THROUGH STUDENT ANSWERS
   =============================== */
foreach ($answers as $question_id => $selected_answer) {

    $question_id = (int)$question_id;
    $selected_answer = trim(mysqli_real_escape_string($conn, $selected_answer));

    /* SAVE STUDENT ANSWER */
    mysqli_query(
        $conn,
        "INSERT INTO student_answers (attempt_id, question_id, selected_answer)
         VALUES ($attempt_id, $question_id, '$selected_answer')"
    );

    /* GET QUESTION DETAILS */
    $qRes = mysqli_query(
        $conn,
        "SELECT question_type, marks FROM questions WHERE id=$question_id"
    );
    $q = mysqli_fetch_assoc($qRes);

    $marks = (int)$q['marks'];
    $total_marks += $marks;

    $is_correct = false;

    /* MCQ */
    if ($q['question_type'] === 'mcq') {

        $optRes = mysqli_query(
            $conn,
            "SELECT option_text FROM question_options
             WHERE question_id=$question_id AND is_correct=1 LIMIT 1"
        );
        $opt = mysqli_fetch_assoc($optRes);

        if ($opt && $selected_answer === $opt['option_text']) {
            $is_correct = true;
        }
    }
    /* TRUE/FALSE, ONE WORD, FILL BLANK */
    else {

        $ansRes = mysqli_query(
            $conn,
            "SELECT correct_answer FROM question_answers
             WHERE question_id=$question_id LIMIT 1"
        );
        $ans = mysqli_fetch_assoc($ansRes);

        if ($ans && strcasecmp(trim($ans['correct_answer']), $selected_answer) === 0) {
            $is_correct = true;
        }
    }

    if ($is_correct) {
        $total_score += $marks;
    }
}

/* ===============================
   UPDATE QUIZ ATTEMPT
   =============================== */
mysqli_query(
    $conn,
    "UPDATE quiz_attempts
     SET score = $total_score,
         total_marks = $total_marks,
         status = 'submitted',
         submitted_at = NOW()
     WHERE id = $attempt_id"
);

/* REDIRECT TO RESULT PAGE */
header("Location: quiz_result.php?attempt_id=$attempt_id");
exit();
