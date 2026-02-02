<?php
session_start();

/* =========================
   ROLE PROTECTION
   ========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

/* =========================
   DATABASE CONNECTION
   ========================= */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = (int) $_SESSION['user_id'];

/* =========================
   VALIDATE ACCESS
   ========================= */
if (!isset($_GET['attempt_id'])) {
    die("Invalid access");
}

$attempt_id = (int) $_GET['attempt_id'];

/* =========================
   FETCH ATTEMPT + QUIZ INFO
   ========================= */
$res = mysqli_query(
    $conn,
    "SELECT 
        qa.score,
        q.title,
        q.id AS quiz_id,
        q.pass_marks
     FROM quiz_attempts qa
     JOIN quizzes q ON qa.quiz_id = q.id
     WHERE qa.id = $attempt_id
       AND qa.student_id = $student_id
       AND qa.status = 'submitted'"
);

if (mysqli_num_rows($res) === 0) {
    die("Result not found");
}

$data = mysqli_fetch_assoc($res);

$score      = (float) $data['score'];
$passMarks = (float) $data['pass_marks'];
$quiz_id   = (int) $data['quiz_id'];

/* =========================
   CHECK FOR DESCRIPTIVE QUESTIONS
   ========================= */
$descriptiveRes = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS descriptive_count
     FROM questions
     WHERE quiz_id = $quiz_id AND question_type = 'descriptive'"
);
$descriptiveRow = mysqli_fetch_assoc($descriptiveRes);
$hasDescriptive = (int) $descriptiveRow['descriptive_count'] > 0;

/* =========================
   TOTAL MARKS (FROM QUESTIONS)
   ========================= */
$totalRes = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(marks), 0) AS total_marks
     FROM questions
     WHERE quiz_id = $quiz_id"
);

$totalRow   = mysqli_fetch_assoc($totalRes);
$totalMarks = (float) $totalRow['total_marks'];

/* =========================
   PASS / FAIL LOGIC
   ========================= */
$passed = ($score >= $passMarks);

/* =========================
   STUDENT INFO (OPTIONAL)
   ========================= */
$stuRes = mysqli_query(
    $conn,
    "SELECT name, profile_pic
     FROM Students
     WHERE id = $student_id"
);

$student = mysqli_fetch_assoc($stuRes);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quiz Result | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { background:#f0f2f5; padding:40px; }

.card {
    background:white;
    max-width:600px;
    margin:auto;
    padding:35px;
    border-radius:16px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
    border-left:6px solid <?= $passed ? '#5d9415' : '#d32f2f' ?>;
    text-align:center;
}

.card h2 {
    color:#5A0E24;
    margin-bottom:10px;
}

.quiz-title {
    font-size:20px;
    font-weight:bold;
    margin-bottom:25px;
}

.score-box {
    background:#f8f9fa;
    padding:25px;
    border-radius:12px;
    margin-bottom:25px;
}

.score-box h1 {
    font-size:48px;
    color:<?= $passed ? '#5d9415' : '#d32f2f' ?>;
}

.score-box p {
    font-size:16px;
    color:#555;
}

.status {
    font-weight:bold;
    margin-bottom:15px;
    color:<?= $passed ? '#2e7d32' : '#c62828' ?>;
    font-size:18px;
}

.message {
    font-size:15px;
    color:#444;
    margin-bottom:30px;
}

.btn {
    display:inline-block;
    background:#5d9415;
    color:white;
    padding:12px 22px;
    border-radius:6px;
    text-decoration:none;
    font-weight:bold;
}
</style>
</head>

<body>

<div class="card">

    <h2>Quiz Completed 🎉</h2>

    <div class="quiz-title">
        <?= htmlspecialchars($data['title']) ?>
    </div>

    <?php if ($hasDescriptive): ?>
        <div class="score-box" style="background:#fff3cd; border-left:4px solid #ffc107;">
            <p style="font-size:16px; color:#856404; margin:0;">
                <i class="fas fa-clock"></i> <strong>Pending Evaluation</strong>
            </p>
            <p style="font-size:14px; color:#856404; margin-top:10px;">
                Your quiz contains questions that require manual evaluation by the teacher. 
                Your final score will be displayed once the teacher has reviewed and marked your answers.
            </p>
        </div>
    <?php else: ?>
        <div class="score-box">
            <h1><?= $score ?> / <?= $totalMarks ?></h1>

            <p>Your Score</p>
        </div>

        <div class="status">
            <?= $passed ? 'Test Passed ✅' : 'Test Failed ❌' ?>
        </div>

        <div class="message">
            <?php if ($passed): ?>
                🎊 Congratulations! You have successfully passed this quiz. Keep up the great work!
            <?php else: ?>
                ⚠️ Don't worry! You did not meet the passing marks this time. Review the topics and try again.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a href="scheduled_quizzes_student.php" class="btn">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
