<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) { die("Database connection failed"); }

if (!isset($_GET['quiz_id'])) {
    header("Location: student_dashboard.php");
    exit();
}

$quiz_id = (int)$_GET['quiz_id'];
// Get current step (starting at 0)
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

// Fetch Quiz Details
$quiz_res = mysqli_query($conn, "SELECT * FROM sample_quizzes WHERE id=$quiz_id");
$quiz = mysqli_fetch_assoc($quiz_res);

// Fetch all questions for this quiz to handle navigation
$questions_all = mysqli_query($conn, "SELECT * FROM sample_questions WHERE quiz_id=$quiz_id ORDER BY id ASC");
$total_questions = mysqli_num_rows($questions_all);
$all_q_data = mysqli_fetch_all($questions_all, MYSQLI_ASSOC);

// If step is out of bounds, redirect to results or dashboard
if ($step >= $total_questions && $total_questions > 0) {
    header("Location: student_dashboard.php?msg=QuizCompleted");
    exit();
}

// Current Question Data
$q = $all_q_data[$step] ?? null;
$qid = $q['id'] ?? 0;
$results = null;

// Handle Individual Question Submission
if (isset($_POST['check_answer'])) {
    $user_val = trim($_POST["answer_$qid"] ?? '');
    $question_type = $q['question_type'];
    $explanation_text = $q['answer_explanation'];
    $correct_text = "";

    if ($question_type === 'mcq') {
        $optRes = mysqli_query($conn, "SELECT option_text FROM sample_question_options WHERE question_id=$qid AND is_correct=1 LIMIT 1");
        $optRow = mysqli_fetch_assoc($optRes);
        $correct_text = $optRow['option_text'] ?? '';
    } else {
        $correct_text = $q['correct_answer_text'] ?? '';
    }

    $is_correct = (strtolower(trim($correct_text)) == strtolower($user_val));

    $results = [
        "user" => $user_val,
        "correct" => $correct_text,
        "explanation" => $explanation_text,
        "status" => $is_correct
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title'] ?? 'Quiz') ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 700px; margin: auto; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-top: 5px solid #5A0E24; }
        .progress { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
        .question h3 { color: #333; margin-top: 0; line-height: 1.4; }
        
        .option-card { display: flex; align-items: center; gap: 15px; background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; padding: 14px 18px; margin-bottom: 12px; cursor: pointer; transition: 0.2s; position: relative; }
        .option-card:hover:not(.disabled) { border-color: #5d9415; background: #f0f7e8; }
        .option-card input { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; z-index: 2; }
        .option-card.disabled input { cursor: default; }
        
        .option-label { width: 32px; height: 32px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .option-card.selected { border-color: #2e7d32; background: #e8f8ec; }
        .option-card.selected .option-label { background: #2e7d32; color: #fff; }
        
        .correct-option { border-color: #28a745 !important; background: #d4edda !important; }
        .wrong-option { border-color: #dc3545 !important; background: #f8d7da !important; }

        .btn { background: #5d9415; color: white; padding: 14px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .btn:hover { background: #4a7511; }
        .btn-next { background: #5A0E24; text-decoration: none; display: inline-block; }
        .btn-next:hover { background: #430a1b; }

        .text-input { width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #ddd; font-size: 16px; box-sizing: border-box; }
        .feedback { margin-top: 20px; padding: 15px; border-radius: 8px; }
        .feedback.correct { background: #d4edda; color: #155724; }
        .feedback.wrong { background: #f8d7da; color: #721c24; }
        .explanation { margin-top: 10px; color: #555; font-size: 0.95rem; line-height: 1.5; }
    </style>
</head>
<body>

<div class="container">
    <div class="progress">Question <?= $step + 1 ?> of <?= $total_questions ?></div>
    
    <?php if ($q): ?>
    <form method="POST">
        <div class="card">
            <div class="question">
                <h3><?= htmlspecialchars($q['question_text']) ?></h3>
            </div>

            <div class="options-area">
                <?php if($q['question_type'] == "mcq"): ?>
                    <?php
                    $opts = mysqli_query($conn, "SELECT * FROM sample_question_options WHERE question_id=$qid");
                    $oIdx = 0;
                    while($o = mysqli_fetch_assoc($opts)):
                        $oIdx++;
                        $val = $o['option_text'];
                        $is_checked = (isset($_POST["answer_$qid"]) && $_POST["answer_$qid"] == $val);
                        
                        $state = "";
                        if ($results) {
                            if ($o['is_correct'] == 1) $state = "correct-option";
                            elseif ($is_checked) $state = "wrong-option";
                            $state .= " disabled";
                        } elseif ($is_checked) {
                            $state = "selected";
                        }
                    ?>
                    <label class="option-card <?= $state ?>">
                        <input type="radio" name="answer_<?= $qid ?>" value="<?= htmlspecialchars($val) ?>" 
                            <?= $is_checked ? "checked" : "" ?> <?= $results ? "disabled" : "" ?> required>
                        <span class="option-label"><?= chr(65 + $step) ?></span> <span class="option-text"><?= htmlspecialchars($val) ?></span>
                    </label>
                    <?php endwhile; ?>

                <?php elseif($q['question_type'] == "true_false"): ?>
                    <?php foreach(['True', 'False'] as $val): 
                        $is_checked = (isset($_POST["answer_$qid"]) && $_POST["answer_$qid"] == $val);
                        $state = "";
                        if ($results) {
                            if ($q['correct_answer_text'] == $val) $state = "correct-option";
                            elseif ($is_checked) $state = "wrong-option";
                            $state .= " disabled";
                        }
                    ?>
                    <label class="option-card <?= $state ?>">
                        <input type="radio" name="answer_<?= $qid ?>" value="<?= $val ?>" 
                            <?= $is_checked ? "checked" : "" ?> <?= $results ? "disabled" : "" ?> required>
                        <span class="option-label"><?= $val[0] ?></span>
                        <span class="option-text"><?= $val ?></span>
                    </label>
                    <?php endforeach; ?>

                <?php elseif($q['question_type'] == "one_word"): ?>
                    <input type="text" name="answer_<?= $qid ?>" class="text-input" placeholder="Type answer here..."
                        value="<?= htmlspecialchars($_POST["answer_$qid"] ?? '') ?>" <?= $results ? "disabled" : "" ?> required>
                <?php endif; ?>
            </div>

            <?php if ($results): ?>
                <div class="feedback <?= $results['status'] ? 'correct' : 'wrong' ?>">
                    <strong><?= $results['status'] ? "✓ Correct!" : "✗ Incorrect" ?></strong>
                    <div>Correct Answer: <b><?= htmlspecialchars($results['correct']) ?></b></div>
                    <?php if(!empty($results['explanation'])): ?>
                        <div class="explanation"><b>Explanation:</b> <?= htmlspecialchars($results['explanation']) ?></div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 20px;">
                    <?php if ($step + 1 < $total_questions): ?>
                        <a href="?quiz_id=<?= $quiz_id ?>&step=<?= $step + 1 ?>" class="btn btn-next">Next Question →</a>
                    <?php else: ?>
                        <a href="student_dashboard.php" class="btn btn-next">Finish Quiz</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="margin-top: 20px;">
                    <button type="submit" name="check_answer" class="btn">Check Answer</button>
                </div>
            <?php endif; ?>
        </div>
    </form>
    <?php else: ?>
        <div class="card"><h3>No questions found.</h3></div>
    <?php endif; ?>
</div>

<script>
// Visual selection handler
document.querySelectorAll('.option-card input').forEach(input => {
    input.addEventListener('change', function() {
        const parent = this.closest('.options-area');
        parent.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
        if(this.checked) this.closest('.option-card').classList.add('selected');
    });
});
</script>

</body>
</html>