<?php
session_start();

// 1. Session & Role Security
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// 2. Database Connection
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) { die("Database connection failed"); }

// 3. Quiz Context Logic
if (!isset($_GET['quiz_id'])) {
    header("Location: student_dashboard.php");
    exit();
}

$quiz_id = (int)$_GET['quiz_id'];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

// Fetch Quiz Title
$quiz_res = mysqli_query($conn, "SELECT title FROM sample_quizzes WHERE id=$quiz_id");
$quiz = mysqli_fetch_assoc($quiz_res);

// Fetch all questions for this quiz
$questions_all = mysqli_query($conn, "SELECT * FROM sample_questions WHERE quiz_id=$quiz_id ORDER BY id ASC");
$total_questions = mysqli_num_rows($questions_all);
$all_q_data = mysqli_fetch_all($questions_all, MYSQLI_ASSOC);

// Check if this is the last question
$is_last_step = ($step == $total_questions - 1);

// Redirect if quiz is finished
if ($step >= $total_questions && $total_questions > 0) {
    header("Location: student_dashboard.php?msg=QuizCompleted");
    exit();
}

$q = $all_q_data[$step] ?? null;
$qid = $q['id'] ?? 0;
$results = null;

/* =========================
   CHECK ANSWER LOGIC
   ========================= */
if (isset($_POST['check_answer'])) {
    $user_val = trim($_POST["answer_$qid"] ?? '');
    $question_type = $q['question_type'];
    $explanation_text = $q['answer_explanation'];
    
    if ($question_type === 'mcq') {
        $optRes = mysqli_query($conn, "SELECT option_text FROM sample_question_options WHERE question_id=$qid AND is_correct=1 LIMIT 1");
        $optRow = mysqli_fetch_assoc($optRes);
        $correct_text = $optRow['option_text'] ?? '';
    } else {
        $correct_text = $q['correct_answer_text'] ?? '';
    }

    $is_correct = (strtolower(trim($correct_text)) == strtolower(trim($user_val)));

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['title'] ?? 'Quiz') ?> | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #5A0E24; --secondary: #5d9415; --bg: #f8f9fa; --text: #2d3436; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: var(--text); line-height: 1.6; }
        
        .quiz-header { max-width: 800px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: space-between; }
        .progress-container { flex-grow: 1; height: 8px; background: #e0e0e0; border-radius: 10px; margin: 0 20px; overflow: hidden; }
        .progress-bar { height: 100%; background: var(--secondary); transition: width 0.4s ease; }

        .container { max-width: 800px; margin: auto; }
        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); position: relative; min-height: 300px; }
        
        .q-badge { display: inline-block; padding: 6px 14px; background: rgba(90,14,36,0.1); color: var(--primary); border-radius: 8px; font-weight: 700; font-size: 0.8rem; margin-bottom: 20px; text-transform: uppercase; }
        .question-text { font-size: 1.6rem; line-height: 1.4; margin-bottom: 30px; font-weight: 600; color: #2d3436; }

        /* ONE WORD INPUT BOX */
        .one-word-input {
            width: 100%;
            padding: 15px;
            font-size: 1.2rem;
            border: 2px solid #edf2f7;
            border-radius: 12px;
            outline: none;
            transition: 0.3s;
        }
        .one-word-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(90, 14, 36, 0.05); }
        .one-word-input:disabled { background: #f8f9fa; color: var(--secondary); font-weight: bold; cursor: not-allowed; }

        /* OPTIONS STYLING (MCQ/TF) */
        .option-card { display: flex; align-items: center; padding: 18px 24px; border: 2px solid #edf2f7; border-radius: 16px; margin-bottom: 12px; cursor: pointer; transition: 0.2s; position: relative; }
        .option-card:hover:not(.disabled) { border-color: var(--primary); background: #fff9fa; }
        .option-card input { position: absolute; opacity: 0; }
        .option-circle { width: 24px; height: 24px; border: 2px solid #cbd5e0; border-radius: 50%; margin-right: 15px; }
        
        /* STATE STYLING */
        .correct-option { background: #e6fffa !important; border-color: #38b2ac !important; color: #234e52; }
        .wrong-option { background: #fff5f5 !important; border-color: #feb2b2 !important; color: #742a2a; }
        .selected:not(.disabled) { border-color: var(--primary); background: #fff9fa; }
        .selected .option-circle { border-color: var(--primary); border-width: 7px; }
        .disabled { cursor: not-allowed; opacity: 0.8; }

        /* ACTION BUTTONS */
        .actions { margin-top: 40px; display: flex; justify-content: center; }
        .btn-main { background: var(--primary); color: white; padding: 16px 60px; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; box-shadow: 0 4px 15px rgba(90, 14, 36, 0.2); }
        .btn-main:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(90, 14, 36, 0.3); }
        .btn-finish { background: var(--secondary); box-shadow: 0 4px 15px rgba(93, 148, 21, 0.2); }

        .explanation-box { margin-top: 30px; padding: 25px; border-radius: 16px; display: flex; gap: 15px; align-items: flex-start; }
        .exp-correct { background: #f0fff4; border: 1px solid #c6f6d5; }
        .exp-wrong { background: #fff5f5; border: 1px solid #fed7d7; }
    </style>
</head>
<body>

<div class="quiz-header">
    <a href="student_dashboard.php" style="color: var(--primary);"><i class="fas fa-times fa-lg"></i></a>
    <div class="progress-container">
        <div class="progress-bar" style="width: <?= (($step + 1) / $total_questions) * 100 ?>%"></div>
    </div>
    <span style="font-weight: bold;"><?= $step + 1 ?> / <?= $total_questions ?></span>
</div>

<div class="container">
    <?php if ($q): ?>
    <form method="POST" id="quizForm">
        <div class="card">
            <span class="q-badge"><?= str_replace('_', ' ', $q['question_type']) ?></span>
            
            <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>

            <div class="options-area">
                <?php if($q['question_type'] == "mcq"): ?>
                    <?php
                    $opts = mysqli_query($conn, "SELECT * FROM sample_question_options WHERE question_id=$qid");
                    while($o = mysqli_fetch_assoc($opts)):
                        $val = $o['option_text'];
                        $is_checked = (isset($_POST["answer_$qid"]) && $_POST["answer_$qid"] == $val);
                        $state = "";
                        if ($results) {
                            if ($o['is_correct'] == 1) $state = "correct-option disabled";
                            else if ($is_checked) $state = "wrong-option disabled";
                            else $state = "disabled";
                        } else if ($is_checked) { $state = "selected"; }
                    ?>
                    <label class="option-card <?= $state ?>">
                        <input type="radio" name="answer_<?= $qid ?>" value="<?= htmlspecialchars($val) ?>" <?= $is_checked ? "checked" : "" ?> <?= $results ? "disabled" : "" ?> required>
                        <div class="option-circle"></div>
                        <span class="option-text"><?= htmlspecialchars($val) ?></span>
                    </label>
                    <?php endwhile; ?>

                <?php elseif($q['question_type'] == "true_false"): ?>
                    <?php foreach(['True', 'False'] as $val): 
                        $is_checked = (isset($_POST["answer_$qid"]) && $_POST["answer_$qid"] == $val);
                        $state = "";
                        if ($results) {
                            if ($q['correct_answer_text'] == $val) $state = "correct-option disabled";
                            else if ($is_checked) $state = "wrong-option disabled";
                            else $state = "disabled";
                        } else if ($is_checked) { $state = "selected"; }
                    ?>
                    <label class="option-card <?= $state ?>">
                        <input type="radio" name="answer_<?= $qid ?>" value="<?= $val ?>" <?= $is_checked ? "checked" : "" ?> <?= $results ? "disabled" : "" ?> required>
                        <div class="option-circle"></div>
                        <span class="option-text"><?= $val ?></span>
                    </label>
                    <?php endforeach; ?>

                <?php elseif($q['question_type'] == "one_word"): ?>
                    <div style="margin-top: 10px;">
                        <input type="text" 
                               name="answer_<?= $qid ?>" 
                               class="one-word-input" 
                               placeholder="Type your answer here..." 
                               required 
                               autocomplete="off" 
                               value="<?= htmlspecialchars($results['user'] ?? '') ?>"
                               <?= $results ? "disabled" : "" ?>>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($results): ?>
                <div class="explanation-box <?= $results['status'] ? 'exp-correct' : 'exp-wrong' ?>">
                    <i class="fas <?= $results['status'] ? 'fa-check-circle' : 'fa-times-circle' ?> fa-2x"></i>
                    <div>
                        <strong style="display:block; margin-bottom: 5px;"><?= $results['status'] ? "Correct!" : "Incorrect" ?></strong>
                        <p style="margin:0; font-size: 0.95rem;">The answer was: <b><?= htmlspecialchars($results['correct']) ?></b></p>
                        <?php if(!empty($results['explanation'])): ?>
                            <p style="margin-top:10px; font-style: italic; opacity: 0.8;"><?= htmlspecialchars($results['explanation']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="actions">
                    <?php if($is_last_step): ?>
                        <a href="student_dashboard.php?msg=QuizCompleted" class="btn-main btn-finish"><i class="fas fa-flag-checkered"></i> Finish Quiz</a>
                    <?php else: ?>
                        <a href="?quiz_id=<?= $quiz_id ?>&step=<?= $step + 1 ?>" class="btn-main">Continue <i class="fas fa-arrow-right" style="margin-left: 10px;"></i></a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="actions">
                    <button type="submit" name="check_answer" class="btn-main">Check Answer</button>
                </div>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
// Logic to handle clicking the card instead of just the radio circle
document.querySelectorAll('.option-card').forEach(card => {
    card.addEventListener('click', () => {
        if(!card.classList.contains('disabled')) {
            const container = card.closest('.options-area');
            container.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            card.querySelector('input').checked = true;
        }
    });
});
</script>

</body>
</html>