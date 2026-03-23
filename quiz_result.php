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

$student_id = (int) $_SESSION['user_id'];

// Check if attempt_id is provided
if (!isset($_GET['attempt_id'])) {
    die("Invalid access: No attempt ID provided.");
}

$attempt_id = (int) $_GET['attempt_id'];

/* ===============================
    FETCH ATTEMPT DATA
   =============================== */
$res = mysqli_query(
    $conn,
    "SELECT 
        qa.total_marks AS earned_score,
        (SELECT SUM(marks) FROM questions WHERE quiz_id = qa.quiz_id) AS max_marks,
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
    die("Result not found or the quiz attempt is not yet submitted.");
}

$data = mysqli_fetch_assoc($res);
$score      = (float) $data['earned_score']; 
$totalMarks = (float) $data['max_marks'];    
$evaluated  = (int) $data['evaluated'];
$passMarks  = (float) $data['pass_marks'];
$quiz_id    = (int) $data['quiz_id'];

// Check if quiz contains descriptive questions for evaluation notice
$descriptiveRes = mysqli_query($conn, "SELECT COUNT(*) AS descriptive_count 
FROM questions 
WHERE quiz_id = $quiz_id 
AND question_type IN ('descriptive','video','image','audio')");
$descriptiveRow = mysqli_fetch_assoc($descriptiveRes);
$hasDescriptive = (int) $descriptiveRow['descriptive_count'] > 0;

$passed = ($score >= $passMarks);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
        body { background:#f0f2f5; padding:40px; }
        .card { background:white; max-width:850px; margin:auto; padding:35px; border-radius:16px; box-shadow:0 4px 15px rgba(0,0,0,0.08); border-top:8px solid <?= ($hasDescriptive && $evaluated == 0) ? '#ffc107' : ($passed ? '#5d9415' : '#d32f2f') ?>; }
        .text-center { text-align: center; }
        .quiz-title { font-size:24px; font-weight:bold; margin-bottom:10px; color:#5A0E24; }
        .score-box { background:#f8f9fa; padding:25px; border-radius:12px; margin: 20px 0; text-align: center; }
        .score-box h1 { font-size:54px; margin-bottom: 5px; color:<?= $passed ? '#5d9415' : '#d32f2f' ?>; }
        .status-badge { display:inline-block; padding:10px 25px; border-radius:50px; font-weight:bold; margin-bottom:20px; font-size: 18px; background: <?= $passed ? '#e8f5e9' : '#ffebee' ?>; color: <?= $passed ? '#2e7d32' : '#c62828' ?>; }
        
        .review-section { margin-top: 40px; }
        .question-item { background: #fff; border: 1px solid #eee; padding: 25px; border-radius: 12px; margin-bottom: 20px; position: relative; }
        .q-text { font-size: 17px; font-weight: 600; margin-bottom: 15px; color: #333; display: block; }
        
        .ans-row {
    padding: 12px 15px;
    border-radius: 8px;
    margin-top: 10px;
    font-size: 15px;
    display: flex;
    align-items: flex-start; /* 🔥 change this */
    gap: 10px;
    flex-wrap: wrap; /* 🔥 add this */
}
        .ans-row span {
    word-break: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}
        .correct-ans { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .student-ans { background: #f1f3f4; color: #3c4043; border: 1px solid #dadce0; }
        .wrong-ans { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        .btn { display:inline-block; background:#5A0E24; color:white; padding:14px 28px; border-radius:8px; text-decoration:none; font-weight:bold; transition: 0.3s; border: none; cursor: pointer; }
        .btn:hover { background: #3d0a18; transform: translateY(-2px); }
        .btn-outline { background: transparent; color: #5A0E24; border: 2px solid #5A0E24; margin-left: 10px; }
        
        .explanation-box { background: #fff9e6; border-left: 4px solid #ffc107; padding: 15px; margin-top: 15px; font-size: 14px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="card">
    <div class="text-center">
        <h2>Quiz Completed! 🎉</h2>
        <div class="quiz-title"><?= htmlspecialchars($data['title']) ?></div>

        <?php if ($hasDescriptive && $evaluated == 0): ?>
            <div class="score-box" style="background:#fff9e6; border: 1px solid #ffeeba;">
                <p style="font-size:18px; color:#856404;"><i class="fas fa-hourglass-half"></i> <strong>Evaluation Pending</strong></p>
                <p style="font-size:15px; color:#856404; margin-top:10px;">Your descriptive answers are waiting for teacher review. Final score will be updated soon.</p>
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
        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <?php
        $reviewQuery = "SELECT q.*, sa.selected_answer 
                        FROM questions q 
                        LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.attempt_id = $attempt_id 
                        WHERE q.quiz_id = $quiz_id";
        $reviewRes = mysqli_query($conn, $reviewQuery);
        $count = 1;

        while($q = mysqli_fetch_assoc($reviewRes)):
            $student_ans = trim($q['selected_answer'] ?? '');
            $correct_ans = '';

            // 1. Get correct answer from options table (MCQ/TF)
            $optRes = mysqli_query($conn, "SELECT option_text FROM question_options WHERE question_id={$q['id']} AND is_correct=1 LIMIT 1");
            if ($optRow = mysqli_fetch_assoc($optRes)) {
                $correct_ans = trim($optRow['option_text']);
            } else {
                // 2. Fallback to correct_answer_text (Fill Blank)
                $correct_ans = trim($q['correct_answer_text'] ?? '');
            }

            if (in_array($q['question_type'], ['descriptive', 'video', 'image', 'audio'])) {
    $is_correct = null; // manual evaluation
} else {
    $is_correct = (!empty($correct_ans) && strcasecmp($student_ans, $correct_ans) === 0);
}
        ?>
            <div class="question-item">
                <span class="q-text"><?= $count++ ?>. <?= htmlspecialchars($q['question_text']) ?></span>
                
                <?php if (!empty($q['media_path'])): ?>
                    <div style="margin: 15px 0;">
                        <?php 
                        $file_ext = strtolower(pathinfo($q['media_path'], PATHINFO_EXTENSION));
                        $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($file_ext, $image_exts)): ?>
                            <img src="<?= htmlspecialchars($q['media_path']) ?>" 
                                 style="max-width:300px; border-radius:10px; display: block; border: 1px solid #ddd; margin-bottom: 15px;">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="ans-row 
<?= 
    in_array($q['question_type'], ['descriptive','video','image','audio'])
        ? 'student-ans' 
        : (empty($student_ans) 
            ? 'student-ans' 
            : ($is_correct ? 'correct-ans' : 'wrong-ans')
        ) 
?>">
                    <i class="fas <?= (empty($student_ans)) ? 'fa-minus' : ($is_correct ? 'fa-check-circle' : 'fa-times-circle') ?>"></i>
                    <span><strong>Your Answer:</strong> <?= !empty($student_ans) ? htmlspecialchars($student_ans) : '<i>Not Answered</i>' ?></span>
                </div>

                <?php if(!in_array($q['question_type'], ['descriptive','video','image','audio'])): ?>
                    <div class="ans-row correct-ans">
                        <i class="fas fa-check-double"></i>
                        <span><strong>Correct Answer:</strong> <?= !empty($correct_ans) ? htmlspecialchars($correct_ans) : "Pending Teacher Review" ?></span>
                    </div>
                <?php endif; ?>

                <?php if(!empty($q['answer_explanation'])): ?>
                    <div class="explanation-box">
                        <strong><i class="fas fa-info-circle"></i> Explanation:</strong><br>
                        <?= htmlspecialchars($q['answer_explanation']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="text-center" style="margin-top: 40px;">
        <a href="scheduled_quizzes_student.php" class="btn"><i class="fas fa-home"></i> Back to Dashboard</a>
        <a href="download_answer_key.php?quiz_id=<?= $quiz_id ?>" class="btn btn-outline"><i class="fas fa-download"></i> Download Key</a>
    </div>
</div>

</body>
</html>