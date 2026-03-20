<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Database Connection
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

date_default_timezone_set('Asia/Kolkata');

$student_id = (int) $_SESSION['user_id'];

// Accept both POST and GET parameters for flexibility
$attempt_id = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : (isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0);
$quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : (isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0);

if ($attempt_id === 0 || $quiz_id === 0) {
    die("Invalid submission parameters.");
}

/* Get POST answers if available */
$post_answers = $_POST['answer'] ?? [];

/* ===============================
    FETCH ATTEMPT & QUIZ INFO
   =============================== */
$attRes = mysqli_query($conn, "SELECT * FROM quiz_attempts WHERE id=$attempt_id AND student_id=$student_id LIMIT 1");
if (mysqli_num_rows($attRes) === 0) {
    die("Attempt not found or access denied.");
}
$attempt = mysqli_fetch_assoc($attRes);

// If already submitted, don't re-calculate, just redirect
if ($attempt['status'] === 'submitted') {
    header("Location: quiz_result.php?attempt_id=$attempt_id");
    exit();
}

$qRes = mysqli_query($conn, "SELECT duration, negative_marks FROM quizzes WHERE id=$quiz_id LIMIT 1");
$qinfo = mysqli_fetch_assoc($qRes);
$duration = (int) $qinfo['duration'];
$negative_marks = (float) ($qinfo['negative_marks'] ?? 0);

/* ===============================
    SAVE LATEST ANSWERS TO DB
   =============================== */
foreach ($post_answers as $qid => $ans) {
    $qid = (int) $qid;
    $ans = mysqli_real_escape_string($conn, trim($ans));
    
    // Check if answer already exists to decide between Update or Insert
    $checkExisting = mysqli_query($conn, "SELECT id FROM student_answers WHERE attempt_id=$attempt_id AND question_id=$qid");
    if (mysqli_num_rows($checkExisting) > 0) {
        mysqli_query($conn, "UPDATE student_answers SET selected_answer='$ans' WHERE attempt_id=$attempt_id AND question_id=$qid");
    } else {
        mysqli_query($conn, "INSERT INTO student_answers (attempt_id, question_id, selected_answer) VALUES ($attempt_id, $qid, '$ans')");
    }
}

/* ===============================
    SCORE CALCULATION LOGIC
   =============================== */
$total_earned_score = 0;
$total_possible_marks = 0;
$has_descriptive = false;

// Fetch all questions for this quiz
$allQuestionsRes = mysqli_query($conn, "SELECT id, question_type, marks, correct_answer_text FROM questions WHERE quiz_id=$quiz_id");

while ($q = mysqli_fetch_assoc($allQuestionsRes)) {
    $qid = (int) $q['id'];
    $q_marks = (float) $q['marks'];
    $total_possible_marks += $q_marks;

    if ($q['question_type'] === 'descriptive') {
        $has_descriptive = true;
        continue; // Descriptive answers skip auto-grading
    }

    // Fetch the student's answer from DB
    $ansQuery = mysqli_query($conn, "SELECT selected_answer FROM student_answers WHERE attempt_id=$attempt_id AND question_id=$qid LIMIT 1");
    $student_row = mysqli_fetch_assoc($ansQuery);
    $student_ans = isset($student_row['selected_answer']) ? trim($student_row['selected_answer']) : null;

    $is_correct = false;

    if ($student_ans !== null && $student_ans !== '') {
        if ($q['question_type'] === 'mcq' || $q['question_type'] === 'true_false') {
            // Check against options table
            $optRes = mysqli_query($conn, "SELECT option_text FROM question_options WHERE question_id=$qid AND is_correct=1 LIMIT 1");
            $opt = mysqli_fetch_assoc($optRes);
            if ($opt && strcasecmp(trim($opt['option_text']), $student_ans) === 0) {
                $is_correct = true;
            }
        } else {
            // Check against correct_answer_text (Fill in the blanks)
            $correct_text = trim($q['correct_answer_text'] ?? '');
            if (!empty($correct_text) && strcasecmp($correct_text, $student_ans) === 0) {
                $is_correct = true;
            }
        }

        // Apply Marks
        if ($is_correct) {
            $total_earned_score += $q_marks;
        } else {
            $total_earned_score -= $negative_marks;
        }
    }
}

// Final score shouldn't be negative
if ($total_earned_score < 0) $total_earned_score = 0;

/* ===============================
    FINALIZE SUBMISSION
   =============================== */
$expireTime = strtotime($attempt['started_at']) + ($duration * 60);
$now = time();
// Ensure the submission timestamp doesn't exceed the quiz deadline
$final_submitted_at = ($now > $expireTime) ? date('Y-m-d H:i:s', $expireTime) : date('Y-m-d H:i:s', $now);

// If descriptive questions exist, evaluated = 0, otherwise evaluated = 1
$evaluation_status = ($has_descriptive) ? 0 : 1;

// Update Attempt Table
$updateAttempt = "UPDATE quiz_attempts SET 
                  score = $total_earned_score, 
                  total_marks = $total_possible_marks, 
                  status = 'submitted', 
                  evaluated = $evaluation_status,
                  submitted_at = '$final_submitted_at' 
                  WHERE id = $attempt_id";
mysqli_query($conn, $updateAttempt);

// Sync with Results Table for global reporting
$resCheck = mysqli_query($conn, "SELECT id FROM Results WHERE quiz_id=$quiz_id AND student_id=$student_id LIMIT 1");
if (mysqli_num_rows($resCheck) > 0) {
    mysqli_query($conn, "UPDATE Results SET score=$total_earned_score, total_marks=$total_possible_marks, submitted_at='$final_submitted_at' WHERE quiz_id=$quiz_id AND student_id=$student_id");
} else {
    mysqli_query($conn, "INSERT INTO Results (quiz_id, student_id, score, total_marks, submitted_at) VALUES ($quiz_id, $student_id, $total_earned_score, $total_possible_marks, '$final_submitted_at')");
}

header("Location: quiz_result.php?attempt_id=$attempt_id");
exit();