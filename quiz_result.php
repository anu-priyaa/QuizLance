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

if (!isset($_GET['attempt_id'])) {
    die("Invalid access");
}

$attempt_id = (int)$_GET['attempt_id'];

/* FETCH ATTEMPT + QUIZ INFO (INCLUDING PASS MARKS) */
$res = mysqli_query(
    $conn,
    "SELECT qa.score, q.title, q.id AS quiz_id, q.pass_marks
     FROM quiz_attempts qa
     JOIN quizzes q ON qa.quiz_id = q.id
     WHERE qa.id = $attempt_id AND qa.student_id = $student_id"
);

if (mysqli_num_rows($res) === 0) {
    die("Result not found");
}

$data = mysqli_fetch_assoc($res);

/* TOTAL MARKS */
$totalRes = mysqli_query(
    $conn,
    "SELECT SUM(marks) AS total_marks 
     FROM questions 
     WHERE quiz_id = " . $data['quiz_id']
);
$total = mysqli_fetch_assoc($totalRes);

/* PASS / FAIL LOGIC */
$passed = ($data['score'] >= $data['pass_marks']);

/* STUDENT INFO */
$stuRes = mysqli_query(
    $conn,
    "SELECT name, profile_pic FROM Students WHERE id=$student_id"
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

    <div class="score-box">
        <h1><?= $data['score'] ?> / <?= $total['total_marks'] ?></h1>
        <p>Your Score</p>
    </div>

    <div class="status">
        <?= $passed ? 'Test Passed ✅' : 'Test Failed ❌' ?>
    </div>

    <div class="message">
        <?php if ($passed): ?>
            🎊 Congratulations! You have successfully passed this quiz. Keep up the great work!
        <?php else: ?>
            ⚠️ Don’t worry! You did not meet the passing marks this time. Review the topics and try again.
        <?php endif; ?>
    </div>

    <a href="student_dashboard.php" class="btn">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
