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

$student_id = (int) $_SESSION['user_id'];

// Accept both POST and GET parameters
$attempt_id = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : (isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0);
$quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : (isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0);

if ($attempt_id === 0 || $quiz_id === 0) {
    die("Invalid submission");
}

/* Get POST answers if available */
$answers = $_POST['answer'] ?? [];

/* ===============================
   FETCH ATTEMPT
   =============================== */
$attRes = mysqli_query($conn, "SELECT * FROM quiz_attempts WHERE id=$attempt_id LIMIT 1");
if (mysqli_num_rows($attRes) === 0) {
    die("Attempt not found");
}
$attempt = mysqli_fetch_assoc($attRes);

if ($attempt['status'] === 'submitted') {
    header("Location: quiz_result.php?attempt_id=$attempt_id");
    exit();
}

/* ===============================
   FETCH QUIZ INFO
   =============================== */
$qRes = mysqli_query($conn, "SELECT duration, negative_marks FROM quizzes WHERE id=$quiz_id LIMIT 1");
$qinfo = mysqli_fetch_assoc($qRes);
$duration = (int) $qinfo['duration'];
$negative_marks = (float) $qinfo['negative_marks'];

/* ===============================
   CALCULATE SUBMISSION TIME
   =============================== */
$expireTime = strtotime($attempt['started_at']) + ($duration * 60);
$now = time();
$submitted_at = ($now > $expireTime) ? date('Y-m-d H:i:s', $expireTime) : date('Y-m-d H:i:s', $now);

/* ===============================
   SCORE CALCULATION
   =============================== */
$total_score = 0;
$total_marks = 0;

/* Save POST answers to database first */
foreach ($answers as $question_id => $selected_answer) {
    $question_id = (int) $question_id;
    $selected_answer = trim(mysqli_real_escape_string($conn, $selected_answer));

    mysqli_query($conn, "DELETE FROM student_answers WHERE attempt_id=$attempt_id AND question_id=$question_id");
    mysqli_query($conn, "INSERT INTO student_answers (attempt_id, question_id, selected_answer) VALUES ($attempt_id, $question_id, '$selected_answer')");
}

/* Fetch all student answers */
$studentAnswersRes = mysqli_query($conn, "SELECT question_id, selected_answer FROM student_answers WHERE attempt_id=$attempt_id");
$studentAnswers = [];
while ($row = mysqli_fetch_assoc($studentAnswersRes)) {
    $studentAnswers[$row['question_id']] = $row['selected_answer'];
}

/* Fetch all questions and their CORRECT answers from the 'questions' table */
$allQuestionsRes = mysqli_query($conn, "SELECT id, question_type, marks, correct_answer_text FROM questions WHERE quiz_id=$quiz_id");

while ($q = mysqli_fetch_assoc($allQuestionsRes)) {
    $question_id = (int) $q['id'];
    $marks = (float) $q['marks'];
    $total_marks += $marks;

    if ($q['question_type'] === 'descriptive') continue;

    $is_correct = false;

    if (isset($studentAnswers[$question_id])) {
        $selected_answer = trim($studentAnswers[$question_id]);

        if ($q['question_type'] === 'mcq') {
            // MCQ still uses the options table
            $optRes = mysqli_query($conn, "SELECT option_text FROM question_options WHERE question_id=$question_id AND is_correct=1 LIMIT 1");
            $opt = mysqli_fetch_assoc($optRes);
            if ($opt && $selected_answer === $opt['option_text']) {
                $is_correct = true;
            }
        } else {
            // 🔥 FIXED: Check the 'correct_answer_text' column in the 'questions' table
            $correct_text = trim($q['correct_answer_text'] ?? '');
            
            if (!empty($correct_text) && strcasecmp($correct_text, $selected_answer) === 0) {
                $is_correct = true;
            }
        }
    }

    /* APPLY MARKING */
    if ($is_correct) {
        $total_score += $marks;
    } else {
        if ($negative_marks > 0 && isset($studentAnswers[$question_id])) {
            $total_score -= $negative_marks;
        }
    }
}

if ($total_score < 0) $total_score = 0;

/* ===============================
   UPDATE TABLES
   =============================== */
mysqli_query($conn, "UPDATE quiz_attempts SET score = $total_score, total_marks = $total_marks, status = 'submitted', submitted_at = '$submitted_at' WHERE id = $attempt_id");

$resCheck = mysqli_query($conn, "SELECT id FROM Results WHERE quiz_id=$quiz_id AND student_id=$student_id LIMIT 1");
if (mysqli_num_rows($resCheck) > 0) {
    mysqli_query($conn, "UPDATE Results SET score=$total_score, total_marks=$total_marks, submitted_at='$submitted_at' WHERE quiz_id=$quiz_id AND student_id=$student_id");
} else {
    mysqli_query($conn, "INSERT INTO Results (quiz_id, student_id, score, total_marks, submitted_at) VALUES ($quiz_id, $student_id, $total_score, $total_marks, '$submitted_at')");
}

header("Location: quiz_result.php?attempt_id=$attempt_id");
exit();