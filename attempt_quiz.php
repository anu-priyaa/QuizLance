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

// SHOW RULES ONLY ON FIRST LOAD
if (!isset($_POST['start_quiz'])) {
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($quiz['title']) ?> - Rules</title>

<style>
body {
    font-family:'Segoe UI', sans-serif;
    background:#f0f2f5;
    padding:40px;
}
.card {
    background:white;
    padding:30px;
    border-radius:15px;
    max-width:700px;
    margin:auto;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
}
.btn {
    background:#5d9415;
    color:white;
    padding:12px 20px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}
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

$shuffle_questions = $quiz['shuffle_questions'] ?? 'no';


/* ===============================
   TIME VALIDATION (FIXED)
   =============================== */
$now        = time();
$startTime = strtotime($quiz['start_time']);
$endTime   = strtotime($quiz['end_time']);

if ($now < $startTime) {
    die("Quiz has not started yet");
}

if ($now > $endTime) {
    die("Quiz has ended");
}

/* ===============================
   CHECK STUDENT ENROLLMENT
   =============================== */
$checkEnroll = mysqli_query(
    $conn,
    "SELECT id FROM class_students 
     WHERE class_id={$quiz['class_id']} 
     AND student_id=$student_id"
);

if (mysqli_num_rows($checkEnroll) === 0) {
    die("You are not enrolled in this class");
}

/* ===============================
   CHECK / CREATE ATTEMPT
   =============================== */
$attemptRes = mysqli_query(
    $conn,
    "SELECT * FROM quiz_attempts 
     WHERE quiz_id=$quiz_id 
     AND student_id=$student_id"
);

if (mysqli_num_rows($attemptRes) > 0) {

    $attempt = mysqli_fetch_assoc($attemptRes);

    if ($attempt['status'] === 'submitted') {
        header("Location: view_score.php?quiz_id=$quiz_id");
        exit();
    }

    $attempt_id = $attempt['id'];

} else {

    mysqli_query(
        $conn,
        "INSERT INTO quiz_attempts 
        (quiz_id, student_id, started_at) 
        VALUES ($quiz_id, $student_id, NOW())"
    );

    $attempt_id = mysqli_insert_id($conn);
}

// Ensure we have the attempt row with started_at
$attemptRowRes = mysqli_query($conn, "SELECT * FROM quiz_attempts WHERE id=$attempt_id LIMIT 1");
if (mysqli_num_rows($attemptRowRes) === 0) {
    die("Attempt not found");
}
$attempt = mysqli_fetch_assoc($attemptRowRes);

// compute expiry based on attempt started_at + quiz duration
$attemptStart = strtotime($attempt['started_at']);
$expireTime = $attemptStart + ((int)$quiz['duration'] * 60);

// if attempt already expired on server, finalize and redirect to result view
if (time() > $expireTime) {
    if ($attempt['status'] !== 'submitted') {
        mysqli_query($conn, "UPDATE quiz_attempts SET status='submitted', submitted_at=DATE_ADD(started_at, INTERVAL {$quiz['duration']} MINUTE) WHERE id=$attempt_id");
    }
    header("Location: view_score.php?quiz_id=$quiz_id");
    exit();
}

/* ===============================
   FETCH QUESTIONS (WITH SHUFFLE LOGIC)
   =============================== */
if ($shuffle_questions === 'yes') {
    $res = mysqli_query(
        $conn,
        "SELECT * FROM questions WHERE quiz_id=$quiz_id ORDER BY RAND()"
    );
} else {
    $res = mysqli_query(
        $conn,
        "SELECT * FROM questions WHERE quiz_id=$quiz_id ORDER BY id"
    );
}

$questionList = [];
while ($row = mysqli_fetch_assoc($res)) {
    $questionList[] = $row;
}

$totalQuestions = count($questionList);
$currentIndex = isset($_GET['q']) ? (int)$_GET['q'] : 0;

if ($currentIndex >= $totalQuestions) {
    header("Location: submit_quiz.php?quiz_id=$quiz_id");
    exit();
}

$q = $questionList[$currentIndex];


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($quiz['title']) ?> | Quiz</title>

<style>
body {
    font-family:'Segoe UI', sans-serif;
    background:#f0f2f5;
    padding:40px;
}

.quiz-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.timer {
    background:#5A0E24;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    font-weight:bold;
}

.card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
}

.question {
    margin-bottom:25px;
}

.options label {
    display:block;
    margin-bottom:6px;
}

.btn {
    background:#5d9415;
    color:white;
    padding:12px 20px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}
</style>
</head>

<body>

<div class="quiz-header">
    <h2><?= htmlspecialchars($quiz['title']) ?></h2>
    <div class="timer" id="timer">Loading...</div>
</div>

<form method="POST" action="submit_quiz.php">

<input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
<input type="hidden" name="attempt_id" value="<?= $attempt_id ?>">

<div class="card">

<?php
$qno = 1;
while ($q = mysqli_fetch_assoc($questions)):
?>

<div class="question">
    <h4><?= $qno ?>. <?= htmlspecialchars($q['question_text']) ?></h4>

    <?php if ($q['question_type'] === 'descriptive'): ?>
    <textarea
        name="answer[<?= $q['id'] ?>]"
        rows="5"
        style="width:100%;padding:10px;margin-top:10px"
        placeholder="Write your answer here..."></textarea>
<?php endif; ?>


    <?php if (!empty($q['media_path'])): ?>
        <?php if ($q['question_type'] === 'image'): ?>
            <img src="<?= $q['media_path'] ?>" style="max-width:300px;"><br><br>
        <?php elseif ($q['question_type'] === 'video'): ?>
            <video controls width="300">
                <source src="<?= $q['media_path'] ?>">
            </video><br><br>
        <?php elseif ($q['question_type'] === 'audio'): ?>
            <audio controls>
                <source src="<?= $q['media_path'] ?>">
            </audio><br><br>
        <?php endif; ?>
    <?php endif; ?>

<!-- MCQ -->
<?php if ($q['question_type'] === 'mcq'): ?>

    <?php
    $opts = mysqli_query(
        $conn,
        "SELECT * FROM question_options WHERE question_id={$q['id']}"
    );
    while ($o = mysqli_fetch_assoc($opts)):
    ?>
        <label>
            <input type="radio"
                   name="answer[<?= $q['id'] ?>]"
                   value="<?= htmlspecialchars($o['option_text']) ?>">
            <?= htmlspecialchars($o['option_text']) ?>
        </label><br>
    <?php endwhile; ?>

<!-- TRUE / FALSE -->
<?php elseif ($q['question_type'] === 'true_false'): ?>

    <label>
        <input type="radio" name="answer[<?= $q['id'] ?>]" value="True"> True
    </label><br>
    <label>
        <input type="radio" name="answer[<?= $q['id'] ?>]" value="False"> False
    </label>

<!-- ONE WORD / FILL BLANK -->
<?php elseif ($q['question_type'] === 'one_word' || $q['question_type'] === 'fill_blank'): ?>

    <input type="text"
           name="answer[<?= $q['id'] ?>]"
           placeholder="Your answer"
           style="width:300px;padding:8px;">

<!-- DESCRIPTIVE (ONLY ADDITION) -->
<?php elseif ($q['question_type'] === 'descriptive'): ?>

    <textarea
        name="answer[<?= $q['id'] ?>]"
        rows="5"
        style="width:100%;padding:10px"
        placeholder="Write your answer here..."></textarea>

<?php endif; ?>


</div>


<?php
$qno++;
endwhile;
?>

<button class="btn">Submit Quiz</button>

</div>
</form>

<script>
let remaining = <?= $expireTime ?> - Math.floor(Date.now() / 1000);
let timer = document.getElementById("timer");

let interval = setInterval(() => {
    let min = Math.floor(remaining / 60);
    let sec = remaining % 60;
    timer.innerText = min + "m " + sec + "s";

    if (remaining <= 0) {
        clearInterval(interval);
        document.forms[0].submit();
    }
    remaining--;
}, 1000);
</script>

<script>
let violationCount = 0;
const MAX_VIOLATIONS = 2;

document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
        violationCount++;
        updateViolationBox(); // 🔹 ADD THIS LINE

        alert(
            "⚠ Warning!\n" +
            "Tab switching is not allowed.\n" +
            "Violation: " + violationCount + " / " + MAX_VIOLATIONS
        );

        if (violationCount >= MAX_VIOLATIONS) {
            alert("Quiz auto-submitted due to multiple violations.");
            document.forms[0].submit();
        }
    }
});
</script>


<script>
// Disable right-click
document.addEventListener("contextmenu", e => e.preventDefault());

// Disable copy, paste, cut
document.addEventListener("copy", e => e.preventDefault());
document.addEventListener("paste", e => e.preventDefault());
document.addEventListener("cut", e => e.preventDefault());

// Disable keyboard shortcuts
document.addEventListener("keydown", function (e) {
    if (
        (e.ctrlKey && ['c','v','x','a','u'].includes(e.key.toLowerCase())) ||
        e.key === "PrintScreen"
    ) {
        e.preventDefault();
    }
});

// Disable text selection
document.addEventListener("selectstart", e => e.preventDefault());
</script>

<div id="violationBox" style="position:fixed;bottom:10px;right:10px;
background:#5A0E24;color:white;padding:6px 10px;border-radius:5px;font-size:12px;">
Violations: 0 / 3
</div>

<script>
function updateViolationBox() {
    document.getElementById("violationBox").innerText =
        "Violations: " + violationCount + " / " + MAX_VIOLATIONS;
}
</script>

<script>
// Disable right-click
document.addEventListener("contextmenu", function (e) {
    e.preventDefault();
});

// Disable copy, paste, cut
document.addEventListener("copy", function (e) {
    e.preventDefault();
});
document.addEventListener("paste", function (e) {
    e.preventDefault();
});
document.addEventListener("cut", function (e) {
    e.preventDefault();
});

// Disable text selection
document.addEventListener("selectstart", function (e) {
    e.preventDefault();
});

// Disable common keyboard shortcuts
document.addEventListener("keydown", function (e) {

    // Ctrl + C, V, X, A, U
    if (
        e.ctrlKey &&
        ['c','v','x','a','u'].includes(e.key.toLowerCase())
    ) {
        e.preventDefault();
    }

    // Disable Print Screen
    if (e.key === "PrintScreen") {
        e.preventDefault();
    }
});
</script>

</body>
</html>
