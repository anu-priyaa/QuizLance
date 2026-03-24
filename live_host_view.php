<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "quizlance");
$live_id = mysqli_real_escape_string($conn, $_GET['live_id']);

// Get current session status
$session_query = "SELECT l.*, q.title FROM live_quizzes l 
                  JOIN quizzes q ON l.quiz_id = q.id 
                  WHERE l.id = $live_id";
$session_res = mysqli_query($conn, $session_query);
$session = mysqli_fetch_assoc($session_res);

$current_step = $session['current_question']; 
$offset = $current_step - 1;

// Fetch the current question
$q_query = "SELECT * FROM questions WHERE quiz_id = {$session['quiz_id']} LIMIT 1 OFFSET $offset";
$q_res = mysqli_query($conn, $q_query);
$question = mysqli_fetch_assoc($q_res);

// Fallback if time_limit is missing in DB
$time_limit = (isset($question['time_limit']) && $question['time_limit'] > 0) ? $question['time_limit'] : 30;

if (!$question) {
    mysqli_query($conn, "UPDATE live_quizzes SET status = 'finished' WHERE id = $live_id");
    header("Location: live_results.php?live_id=" . $live_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Host View - QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; text-align: center; }
        .host-container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .question-box { background: #5A0E24; color: white; padding: 25px; border-radius: 10px; font-size: 22px; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .stat-card { background: #f8f9fa; border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
        .stat-val { font-size: 50px; color: #5d9415; font-weight: bold; display: block; }
        .btn-next { background: #5d9415; color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-size: 18px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="host-container">
    <h2><?= htmlspecialchars($session['title']) ?> - Question <?= $current_step ?></h2>
    <div class="question-box"><?= htmlspecialchars($question['question_text']) ?></div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Students Answered</h3>
            <span id="answerCount" class="stat-val">0</span>
        </div>
        <div class="stat-card">
            <h3>Time Remaining</h3>
            <span id="timer" class="stat-val"><?= $time_limit ?>s</span>
        </div>
    </div>

    <form action="next_question.php" method="POST">
        <input type="hidden" name="live_id" value="<?= $live_id ?>">
        <button type="submit" class="btn-next">Next Question <i class="fas fa-arrow-right"></i></button>
    </form>
</div>

<script>
    // Timer Logic
    let timeLeft = <?= $time_limit ?>;
    const timerDisplay = document.getElementById('timer');

    const countdown = setInterval(() => {
        if(timeLeft > 0) {
            timeLeft--;
            timerDisplay.innerText = timeLeft + "s";
        } else {
            clearInterval(countdown);
            timerDisplay.style.color = "red";
            timerDisplay.innerText = "TIME UP";
        }
    }, 1000);

    // Answer Counter Logic - Polls get_answer_stats.php
    function checkAnswers() {
        fetch(`get_answer_stats.php?live_id=<?= $live_id ?>&question_id=<?= $question['id'] ?>`)
            .then(r => r.json())
            .then(data => { 
                document.getElementById('answerCount').innerText = data.count; 
            })
            .catch(err => console.error("Error fetching stats:", err));
    }

    setInterval(checkAnswers, 2000); // Check every 2 seconds
    checkAnswers(); // Initial check
</script>
</body>
</html>