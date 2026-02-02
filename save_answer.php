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

$student_id = (int)$_SESSION['user_id'];

if (!isset($_POST['attempt_id'], $_POST['quiz_id'])) {
    die("Invalid request");
}

$attempt_id = (int)$_POST['attempt_id'];
$quiz_id    = (int)$_POST['quiz_id'];

$answers = $_POST['answer'] ?? [];

if (!empty($answers)) {

    // Only ONE question per page → get first key
    $question_id = (int) array_key_first($answers);
    $selected_answer = trim($answers[$question_id]);

    $selected_answer = mysqli_real_escape_string($conn, $selected_answer);

    // Prevent duplicate answer
    mysqli_query(
        $conn,
        "DELETE FROM student_answers
         WHERE attempt_id=$attempt_id
         AND question_id=$question_id"
    );

    mysqli_query(
        $conn,
        "INSERT INTO student_answers (attempt_id, question_id, selected_answer)
         VALUES ($attempt_id, $question_id, '$selected_answer')"
    );
}


$next_q     = (int)($_POST['next_q'] ?? 0);

/* ===============================
   CHECK TOTAL QUESTIONS
   =============================== */
$countRes = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM questions
     WHERE quiz_id=$quiz_id"
);
$row = mysqli_fetch_assoc($countRes);
$totalQuestions = (int)$row['total'];

/* ===============================
   REDIRECT
   =============================== */
if ($next_q >= $totalQuestions) {
    // last question → final submit
    header("Location: submit_quiz.php?quiz_id=$quiz_id&attempt_id=$attempt_id");
} else {
    // next question
    header("Location: attempt_quiz.php?quiz_id=$quiz_id&q=$next_q");
}
exit();
