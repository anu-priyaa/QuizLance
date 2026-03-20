<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

if ($quiz_id === 0) {
    die("Invalid quiz");
}

/* ===============================
    FETCH QUIZ
   =============================== */
$qres = mysqli_query($conn, "SELECT * FROM quizzes WHERE id=$quiz_id");

if (mysqli_num_rows($qres) === 0) {
    die("Quiz not found");
}

$quiz = mysqli_fetch_assoc($qres);

/* ===============================
    CHECK ASSIGNMENT ACCESS
   =============================== */
$assignmentCheck = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM quiz_assignments WHERE quiz_id=$quiz_id");
$assignmentData = mysqli_fetch_assoc($assignmentCheck);
$hasAssignments = $assignmentData['cnt'] > 0;

if ($hasAssignments) {
    $studentAssigned = mysqli_query($conn, "SELECT id FROM quiz_assignments WHERE quiz_id=$quiz_id AND student_id=$student_id");
    
    if (mysqli_num_rows($studentAssigned) === 0) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Denied</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
                body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
                .error-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
                .error-icon { font-size: 60px; color: #d32f2f; margin-bottom: 20px; }
                h2 { color: #5A0E24; margin-bottom: 15px; }
                p { color: #666; line-height: 1.6; margin-bottom: 20px; }
                .btn { display: inline-block; background: #5A0E24; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; }
                .btn:hover { background: #5d9415; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon"><i class="fas fa-lock"></i></div>
                <h2>Access Denied</h2>
                <p>This quiz has been assigned to specific students only, and you are not authorized to attempt it.</p>
                <a href="scheduled_quizzes_student.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

/* ===============================
    CHECK IF QUIZ ALREADY STARTED
   =============================== */
$attemptRes = mysqli_query($conn, "SELECT id FROM quiz_attempts WHERE quiz_id=$quiz_id AND student_id=$student_id");
$quizAlreadyStarted = mysqli_num_rows($attemptRes) > 0;

if (!$quizAlreadyStarted && !isset($_POST['start_quiz'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title']) ?> - Rules</title>
    <style>
        body { font-family:'Segoe UI', sans-serif; background:#f0f2f5; padding:40px; }
        .card { background:white; padding:30px; border-radius:15px; max-width:700px; margin:auto; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
        .btn { background:#5d9415; color:white; padding:12px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; }
    </style>
</head>
<body>
<div class="card">
    <h2><?= htmlspecialchars($quiz['title']) ?> – Quiz Rules</h2>
    <?php if (!empty($quiz['quiz_rules'])): ?>
        <p><?= nl2br(htmlspecialchars($quiz['quiz_rules'])) ?></p>
    <?php else: ?>
        <p>No rules provided for this quiz.</p>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
        <button class="btn" name="start_quiz">Start Quiz</button>
    </form>
</div>
</body>
</html>
<?php
exit();
}

/* ===============================
    QUIZ EXECUTION LOGIC
   =============================== */
$shuffle_questions = $quiz['shuffle_questions'] ?? 'no';

// TIME VALIDATION
$now = time();
$startTime = strtotime($quiz['start_time']);
$endTime = strtotime($quiz['end_time']);

if ($now < $startTime) die("Quiz has not started yet");
if ($now > $endTime) die("Quiz has ended");

// ENROLLMENT CHECK
$checkEnroll = mysqli_query($conn, "SELECT id FROM class_students WHERE class_id={$quiz['class_id']} AND student_id=$student_id");
if (mysqli_num_rows($checkEnroll) === 0) die("You are not enrolled in this class");

// ATTEMPT MANAGEMENT
$attemptRes = mysqli_query($conn, "SELECT * FROM quiz_attempts WHERE quiz_id=$quiz_id AND student_id=$student_id");
if (mysqli_num_rows($attemptRes) > 0) {
    $attempt = mysqli_fetch_assoc($attemptRes);
    if ($attempt['status'] === 'submitted') {
        header("Location: quiz_result.php?quiz_id=$quiz_id");
        exit();
    }
    $attempt_id = $attempt['id'];
} else {
    mysqli_query($conn, "INSERT INTO quiz_attempts (quiz_id, student_id, started_at) VALUES ($quiz_id, $student_id, NOW())");
    $attempt_id = mysqli_insert_id($conn);
    $attemptRes = mysqli_query($conn, "SELECT * FROM quiz_attempts WHERE id=$attempt_id");
    $attempt = mysqli_fetch_assoc($attemptRes);
}

$expireTime = strtotime($attempt['started_at']) + ((int)$quiz['duration'] * 60);

if (time() > $expireTime) {
    mysqli_query($conn, "UPDATE quiz_attempts SET status='submitted', submitted_at=NOW() WHERE id=$attempt_id AND status != 'submitted'");
    header("Location: quiz_result.php?quiz_id=$quiz_id");
    exit();
}

// FETCH QUESTIONS
$order = ($shuffle_questions === 'yes') ? "RAND()" : "id";
$res = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id=$quiz_id ORDER BY $order");
$questionList = [];
while ($row = mysqli_fetch_assoc($res)) { $questionList[] = $row; }

$totalQuestions = count($questionList);
$currentIndex = isset($_GET['q']) ? (int)$_GET['q'] : 0;

if ($currentIndex >= $totalQuestions) {
    header("Location: submit_quiz.php?quiz_id=$quiz_id");
    exit();
}

$q = $questionList[$currentIndex];
$questionTime = (int)($q['time_limit'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title']) ?> | Quiz</title>
    <style>
        body { font-family:'Segoe UI', sans-serif; background:#f0f2f5; padding:40px; }
        .quiz-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .timer { background:#5A0E24; color:white; padding:10px 18px; border-radius:8px; font-weight:bold; }
        .card { background:white; padding:30px; border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
        .question { margin-bottom:25px; }
        .btn { background:#5d9415; color:white; padding:12px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; }
        .option-card { display: flex; align-items: center; gap: 15px; background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; padding: 14px 18px; margin-bottom: 12px; cursor: pointer; transition: all 0.2s ease; }
        .option-card:hover { border-color: #5d9415; background: #f0f7e8; }
        .option-card input { display: none; }
        .option-label { width: 36px; height: 36px; border-radius: 50%; background: #e5e7eb; color: #333; font-weight: bold; display: flex; align-items: center; justify-content: center; }
        .option-text { font-size: 16px; font-weight: 500; }
        .option-card input:checked + .option-label { background: #5d9415; color: white; }
        .option-card input:checked + .option-label + .option-text { font-weight: bold; }
        .text-area { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        .blank-input { border: none; border-bottom: 2px dashed #5A0E24; width: 150px; font-size: 18px; font-weight: bold; color: #5d9415; text-align: center; outline: none; background: transparent; }
    .textbox-input {
    width: 100%;
    max-width: 400px;
    padding: 10px 12px;
    border: 2px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: 0.2s;
}

.textbox-input:focus {
    border-color: #5d9415;
    box-shadow: 0 0 5px rgba(93,148,21,0.3);
}
    </style>
</head>
<body>

<div class="quiz-header">
    <h2><?= htmlspecialchars($quiz['title']) ?></h2>
    <?php if ($questionTime > 0): ?>
        <div class="timer">Question Time: <span id="qTimer"><?= $questionTime ?></span>s</div>
    <?php endif; ?>
    <div class="timer" id="mainTimer">Loading...</div>
</div>

<form method="POST" action="save_answer.php" id="quizForm">
    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
    <input type="hidden" name="attempt_id" value="<?= $attempt_id ?>">
    <input type="hidden" name="next_q" value="<?= $currentIndex + 1 ?>">

    <div class="card">
        <div class="question">
            <?php $type = strtolower($q['question_type']); ?>
            <h4>
    <?= $currentIndex + 1 ?>. 
    <?php 
    if ($type === 'fill_blank') {
        $input_tag = '<input type="text" name="answer['.$q['id'].']" class="blank-input" placeholder="..." required autocomplete="off">';

        if (strpos($q['question_text'], '[blank]') !== false) {
            echo str_replace('[blank]', $input_tag, $q['question_text']);
        } else {
            echo htmlspecialchars($q['question_text']) . " " . $input_tag;
        }

    } else {
        // 🔥 THIS WAS MISSING
        echo htmlspecialchars($q['question_text']);
    }
    ?>
</h4>

            <?php if (!empty($q['media_path'])): ?>
                <div style="margin:20px 0; text-align: center;">
                    <?php 
                    $ext = strtolower(pathinfo($q['media_path'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                        <img src="<?= htmlspecialchars($q['media_path']) ?>" 
     style="max-width:300px; width:100%; height:auto; border-radius:10px; display:block; margin:auto;">
                    <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                        <video controls style="max-width:100%;"><source src="<?= htmlspecialchars($q['media_path']) ?>" type="video/<?= $ext ?>"></video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="options-container" style="margin-top: 20px;">
                <?php if ($type === 'mcq'): 
                    $opts = mysqli_query($conn, "SELECT * FROM question_options WHERE question_id={$q['id']}");
                    $i = 0; while ($o = mysqli_fetch_assoc($opts)): $i++; ?>
                    <label class="option-card">
                        <input type="radio" name="answer[<?= $q['id'] ?>]" value="<?= htmlspecialchars($o['option_text']) ?>" required>
                        <span class="option-label"><?= chr(64 + $i) ?></span>
                        <span class="option-text"><?= htmlspecialchars($o['option_text']) ?></span>
                    </label>
                <?php endwhile; ?>

                <?php elseif ($type === 'true_false'): ?>
                    <label class="option-card">
                        <input type="radio" name="answer[<?= $q['id'] ?>]" value="True" required>
                        <span class="option-label">A</span><span class="option-text">True</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="answer[<?= $q['id'] ?>]" value="False" required>
                        <span class="option-label">B</span><span class="option-text">False</span>
                    </label>

                <?php elseif ($type === 'descriptive'): ?>
    <textarea name="answer[<?= $q['id'] ?>]" rows="5" class="text-area" required placeholder="Type your answer here..."></textarea>

<?php elseif ($type === 'one_word' || $type === 'short_answer'): ?>
    <input type="text" 
           name="answer[<?= $q['id'] ?>]" 
           class="textbox-input" 
           placeholder="Type your answer" 
           required autocomplete="off">

<?php elseif ($type === 'image' || $type === 'video' || $type === 'audio'): ?>
    <!-- 🔥 ADD THIS BLOCK -->
    <input type="text" 
           name="answer[<?= $q['id'] ?>]" 
           class="textbox-input" 
           placeholder="Type your answer" 
           required autocomplete="off">

<?php endif; ?>

                
            </div>
        </div>

        <button type="submit" class="btn">
            <?= ($currentIndex + 1 === $totalQuestions) ? 'Finish Quiz' : 'Next Question' ?>
        </button>
    </div>
</form>

<div id="violationBox" style="position:fixed; bottom:10px; right:10px; background:#5A0E24; color:white; padding:6px 12px; border-radius:6px; font-size:13px; z-index:9999;">
    Violations: 0 / 3
</div>

<script>
var isSubmitting = false;
document.getElementById("quizForm").addEventListener("submit", function () {
    isSubmitting = true;
});
let violationCount = 0;
const MAX_VIOLATIONS = 3;
let lastViolationTime = 0;

// Main Timer
let remaining = <?= $expireTime ?> - Math.floor(Date.now() / 1000);
const mainTimerDisplay = document.getElementById("mainTimer");
setInterval(() => {
    if (isSubmitting) return;
    if (remaining <= 0) autoSubmit("Quiz time expired!");
    mainTimerDisplay.innerText = Math.floor(remaining / 60) + "m " + (remaining % 60) + "s";
    remaining--;
}, 1000);

// Question Timer
let qRemaining = <?= $questionTime ?>;
if (qRemaining > 0) {
    const qTimerDisplay = document.getElementById("qTimer");
    setInterval(() => {
        if (isSubmitting) return;
        if (qRemaining <= 0) autoSubmit("Question time expired!");
        qTimerDisplay.innerText = qRemaining--;
    }, 1000);
}

function autoSubmit(msg) {
    if (isSubmitting) return;
    isSubmitting = true;
    alert(msg);
    document.getElementById("quizForm").submit();
}

function registerViolation(source) {
    if (isSubmitting) return;
    const now = Date.now();
    if (now - lastViolationTime < 2000) return; 
    lastViolationTime = now;
    violationCount++;
    document.getElementById("violationBox").innerText = "Violations: " + violationCount + " / " + MAX_VIOLATIONS;
    if (violationCount >= MAX_VIOLATIONS) {
        autoSubmit('Quiz auto-submitted due to multiple violations.');
    } else {
        alert("Violation detected: " + source);
    }
}

document.addEventListener('visibilitychange', () => {
    if (!isSubmitting && document.hidden) {
        registerViolation('Tab Switch');
    }
});
window.addEventListener('blur', () => {
    if (!isSubmitting) {
        registerViolation('Window Switch');
    }
});
</script>
</body>
</html>