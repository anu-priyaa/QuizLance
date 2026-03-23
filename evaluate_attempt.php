<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized");
}

$conn = mysqli_connect("localhost","root","","QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

// Save marks if submitted
if(isset($_POST['marks'])){
    foreach($_POST['marks'] as $answer_id => $mark){
        $mark = (float)$mark;
        $answer_id = (int)$answer_id;
        mysqli_query($conn,"
            UPDATE student_answers 
            SET marks_awarded = $mark 
            WHERE id = $answer_id
        ");
    }

    // Recalculate total
    $totalRes = mysqli_query($conn,"
        SELECT SUM(marks_awarded) as total 
        FROM student_answers 
        WHERE attempt_id=$attempt_id
    ");
    $totalRow = mysqli_fetch_assoc($totalRes);
    $total = $totalRow['total'] ?? 0;

    mysqli_query($conn,"
        UPDATE quiz_attempts 
        SET total_marks=$total, evaluated=1 
        WHERE id=$attempt_id
    ");

    echo "<script>alert('Evaluation Saved'); window.location.href='teacher_attempts.php';</script>";
    exit;
}

// Fetch attempt details
$attemptRes = mysqli_query($conn,"
    SELECT qa.*, q.title, s.name as student_name 
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN Students s ON qa.student_id = s.id
    WHERE qa.id=$attempt_id
");
$attemptInfo = mysqli_fetch_assoc($attemptRes);

// Fetch all answers
$answers = mysqli_query($conn,"
    SELECT sa.*, q.question_text, q.question_type, q.marks 
    FROM student_answers sa
    JOIN questions q ON sa.question_id=q.id
    WHERE sa.attempt_id=$attempt_id
    ORDER BY q.id ASC
");

// Pre-check for manual questions
$hasManualType = false;
if (mysqli_num_rows($answers) > 0) {
    while($row = mysqli_fetch_assoc($answers)){
        if(in_array($row['question_type'], ['descriptive','video','audio'])){
            $hasManualType = true;
            break;
        }
    }
    // RESET pointer so we can loop again in the HTML
    mysqli_data_seek($answers, 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Student Attempt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS remains exactly the same */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f0f2f5; padding-top: 60px; }
        .topbar { position: fixed; top: 0; left: 0; width: 100%; height: 60px; background: #5A0E24; color: white; display: flex; align-items: center; padding: 0 20px; z-index: 1001; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .topbar h1 { font-size: 20px; font-weight: 600; }
        .container { max-width: 1000px; margin: 20px auto; padding: 30px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; background: #5A0E24; color: white; padding: 10px 18px; border-radius: 5px; text-decoration: none; font-weight: 600; margin-bottom: 20px; transition: all 0.3s ease; }
        .back-btn:hover { background: #5d9415; }
        .attempt-header { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-bottom: 25px; }
        .attempt-header h2 { color: #5A0E24; margin-bottom: 10px; font-size: 24px; }
        .attempt-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
        .meta-item { padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 4px solid #5A0E24; }
        .meta-item label { display: block; color: #999; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
        .meta-item value { display: block; color: #333; font-weight: 500; font-size: 15px; }
        .questions-section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-bottom: 25px; }
        .section-title { color: #5A0E24; font-size: 20px; font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #5d9415; display: flex; align-items: center; gap: 10px; }
        .question-card { margin-bottom: 25px; padding: 20px; background: #f9f9f9; border-radius: 8px; border-left: 5px solid #5A0E24; transition: all 0.3s ease; }
        .question-text { color: #333; font-weight: 600; font-size: 15px; margin-bottom: 15px; }
        .marks-badge { display: inline-block; background: #5A0E24; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 8px; }
        .answer-display { margin: 12px 0; padding: 12px; background: white; border-radius: 5px; border-left: 3px solid #5d9415; max-height: 250px; overflow-y: auto; }
        .answer-label { color: #999; font-size: 12px; font-weight: 600; margin-bottom: 5px; text-transform: uppercase; }
        .answer-content { color: #333; font-weight: 500; margin-top: 5px; word-break: break-word; white-space: pre-wrap; }
        .eval-form-group { margin-top: 15px; }
        .eval-form-group label { display: block; color: #5A0E24; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .eval-form-group input { width: 100%; padding: 10px; border: 2px solid #5A0E24; border-radius: 5px; font-size: 14px; }
        .marks-info { color: #5d9415; font-size: 12px; margin-top: 5px; font-weight: 500; }
        .submit-btn { background: #5A0E24; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; font-size: 14px; margin-top: 20px; }
        .no-data { text-align: center; padding: 40px; color: #999; background: #f9f9f9; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="topbar"><h1>QuizLance</h1></div>

    <div class="container">
        <a href="teacher_attempts.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Attempts</a>

        <div class="attempt-header">
            <h2><i class="fas fa-file-alt"></i> Evaluate Student Attempt</h2>
            <div class="attempt-meta">
                <div class="meta-item"><label>Quiz Title</label><value><?= htmlspecialchars($attemptInfo['title'] ?? 'N/A') ?></value></div>
                <div class="meta-item"><label>Student Name</label><value><?= htmlspecialchars($attemptInfo['student_name'] ?? 'N/A') ?></value></div>
                <div class="meta-item"><label>Submitted On</label><value><?= isset($attemptInfo['submitted_at']) ? date("d M Y, h:i A", strtotime($attemptInfo['submitted_at'])) : 'N/A' ?></value></div>
            </div>
        </div>

        <div class="questions-section">
            <div class="section-title"><i class="fas fa-pen"></i> Evaluation</div>

            <form method="POST">
                <?php 
                $hasQuestions = false;
                mysqli_data_seek($answers, 0); // Reset pointer
                while($row = mysqli_fetch_assoc($answers)): 
                    $hasQuestions = true;
                ?>
                    <div class="question-card">
                        <div class="question-text">
                            <?= htmlspecialchars($row['question_text']) ?>
                            <span class="marks-badge"><?= $row['marks'] ?> marks</span>
                        </div>

                        <div class="answer-display">
                            <div class="answer-label">Student's Answer:</div>
                            <?php if(!empty($row['media_path'])): 
                                $ext = strtolower(pathinfo($row['media_path'], PATHINFO_EXTENSION));
                                if(in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                    <img src="<?= htmlspecialchars($row['media_path']) ?>" style="max-width:300px; display:block; margin:10px auto; border-radius:10px;">
                                <?php elseif(in_array($ext, ['mp4','webm'])): ?>
                                    <video controls style="max-width:100%; margin:10px 0;"><source src="<?= htmlspecialchars($row['media_path']) ?>"></video>
                                <?php elseif(in_array($ext, ['mp3','wav','ogg'])): ?>
                                    <audio controls style="width:100%; margin:10px 0;"><source src="<?= htmlspecialchars($row['media_path']) ?>"></audio>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="answer-content">
                                <?= nl2br(htmlspecialchars($row['answer_text'] ?? $row['selected_answer'] ?? 'No answer provided')) ?>
                            </div>
                        </div>

                        <div class="eval-form-group">
                            <label for="marks_<?= $row['id'] ?>"><i class="fas fa-star"></i> Award Marks</label>
                            <input type="number" id="marks_<?= $row['id'] ?>" name="marks[<?= $row['id'] ?>]" 
                                   min="0" max="<?= $row['marks'] ?>" step="0.5" 
                                   value="<?= $row['marks_awarded'] ?? '' ?>" required>
                            <div class="marks-info">Maximum marks: <?= $row['marks'] ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if (!$hasQuestions): ?>
                    <div class="no-data"><p>No answers found for this attempt.</p></div>
                <?php else: ?>
                    <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save All Marks</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>