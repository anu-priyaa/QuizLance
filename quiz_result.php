<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Database Connection - Ensure case sensitivity matches your DB name
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = (int) $_SESSION['user_id'];

if (!isset($_GET['attempt_id'])) {
    die("Invalid access");
}

$attempt_id = (int) $_GET['attempt_id'];

$res = mysqli_query(
    $conn,
    "SELECT 
        qa.score AS earned_score, 
        qa.total_marks AS max_marks,
        qa.evaluated,
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
    die("Result not found or access denied.");
}

$data = mysqli_fetch_assoc($res);
$score      = (float) $data['earned_score']; 
$totalMarks = (float) $data['max_marks'];    
$evaluated  = (int) $data['evaluated'];
$passMarks  = (float) $data['pass_marks'];
$quiz_id    = (int) $data['quiz_id'];

$descriptiveRes = mysqli_query($conn, "SELECT COUNT(*) AS descriptive_count FROM questions WHERE quiz_id = $quiz_id AND question_type = 'descriptive'");
$descriptiveRow = mysqli_fetch_assoc($descriptiveRes);
$hasDescriptive = (int) $descriptiveRow['descriptive_count'] > 0;

$passed = ($score >= $passMarks);
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
        .card { background:white; max-width:800px; margin:auto; padding:35px; border-radius:16px; box-shadow:0 4px 15px rgba(0,0,0,0.08); border-top:8px solid <?= $passed ? '#5d9415' : '#d32f2f' ?>; }
        .text-center { text-align: center; }
        .quiz-title { font-size:22px; font-weight:bold; margin-bottom:10px; color:#5A0E24; }
        .score-box { background:#f8f9fa; padding:25px; border-radius:12px; margin: 20px 0; text-align: center; }
        .score-box h1 { font-size:54px; color:<?= $passed ? '#5d9415' : '#d32f2f' ?>; }
        .status-badge { display:inline-block; padding:8px 20px; border-radius:50px; font-weight:bold; margin-bottom:20px; background: <?= $passed ? '#e8f5e9' : '#ffebee' ?>; color: <?= $passed ? '#2e7d32' : '#c62828' ?>; }
        .review-section { margin-top: 40px; }
        .question-item { background: #fff; border: 1px solid #eee; padding: 20px; border-radius: 10px; margin-bottom: 15px; }
        .q-text { font-weight: bold; margin-bottom: 10px; display: block; }
        .ans-row { padding: 12px; border-radius: 5px; margin-top: 10px; font-size: 14px; }
        .correct-ans { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .student-ans { background: #f1f3f4; color: #3c4043; border: 1px solid #dadce0; }
        .wrong-ans { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .btn { display:inline-block; background:#5d9415; color:white; padding:12px 22px; border-radius:6px; text-decoration:none; font-weight:bold; transition: 0.3s; }
        .btn:hover { background: #4a7711; }
    </style>
</head>
<body>

<div class="card">
    <div class="text-center">
        <h2>Quiz Completed 🎉</h2>
        <div class="quiz-title"><?= htmlspecialchars($data['title']) ?></div>

        <?php if ($hasDescriptive && $evaluated == 0): ?>
            <div class="score-box" style="background:#fff3cd; border-left:4px solid #ffc107;">
                <p style="font-size:16px; color:#856404;"><i class="fas fa-clock"></i> <strong>Pending Evaluation</strong></p>
                <p style="font-size:14px; color:#856404; margin-top:10px;">Teacher needs to manually grade your descriptive answers.</p>
            </div>
        <?php else: ?>
            <div class="score-box">
                <h1><?= $score ?> / <?= $totalMarks ?></h1>
                <p>Total Marks Earned</p>
            </div>
            <div class="status-badge"><?= $passed ? 'PASSED ✅' : 'FAILED ❌' ?></div>
        <?php endif; ?>
    </div>

    <div class="review-section">
        <h3>Question Review</h3>
        <hr style="margin: 15px 0; opacity: 0.2;">

        <?php
        $reviewQuery = "SELECT q.*, sa.selected_answer 
                        FROM questions q 
                        LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.attempt_id = $attempt_id 
                        WHERE q.quiz_id = $quiz_id";
        $reviewRes = mysqli_query($conn, $reviewQuery);

        while($q = mysqli_fetch_assoc($reviewRes)):
            $student_ans = trim($q['selected_answer'] ?? '');
            $correct_ans = '';

            // SMART ANSWER DETECTION: Checks options table first, then fallback to text column
            $optRes = mysqli_query($conn, "SELECT option_text FROM question_options WHERE question_id={$q['id']} AND is_correct=1 LIMIT 1");
            if ($optRow = mysqli_fetch_assoc($optRes)) {
                $correct_ans = trim($optRow['option_text']);
            } else {
                $correct_ans = trim($q['correct_answer_text'] ?? '');
            }

            $display_correct_ans = !empty($correct_ans) ? $correct_ans : "Not specified by teacher";
            $is_correct = (!empty($correct_ans) && strcasecmp($student_ans, $correct_ans) === 0);
        ?>
            <div class="question-item">
    <span class="q-text"><?= htmlspecialchars($q['question_text']) ?></span>
    
    <?php if (!empty($q['media_path'])): ?>
        <div style="margin: 10px 0;">
            <?php 
            $file_ext = strtolower(pathinfo($q['media_path'], PATHINFO_EXTENSION));
            $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $image_exts)): ?>
                <img src="<?= htmlspecialchars($q['media_path']) ?>" 
                     style="max-width:200px; max-height:150px; border-radius:8px; display: block; border: 1px solid #ddd; margin-bottom: 10px;">
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="ans-row <?= (empty($student_ans)) ? 'student-ans' : ($is_correct ? 'correct-ans' : 'wrong-ans') ?>">
        <strong>Your Answer:</strong> <?= !empty($student_ans) ? htmlspecialchars($student_ans) : '<i>Not Answered</i>' ?>
    </div>

                <?php if($q['question_type'] !== 'descriptive'): ?>
                    <div class="ans-row correct-ans">
                        <strong>Correct Answer:</strong> <?= htmlspecialchars($display_correct_ans) ?>
                    </div>
                <?php endif; ?>

                <?php if(!empty($q['answer_explanation'])): ?>
                    <p style="font-size:12px; margin-top:10px; color:#666;">
                        <strong>Explanation:</strong> <?= htmlspecialchars($q['answer_explanation']) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
    <div class="text-center" style="margin-top: 30px;">
        <a href="scheduled_quizzes_student.php" class="btn">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>