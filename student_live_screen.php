<?php
session_start();
// 1. Authorization Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// 2. Database Connection
$conn = mysqli_connect("localhost", "root", "", "quizlance");
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }

// 3. Data Fetching
$live_id = mysqli_real_escape_string($conn, $_GET['live_id'] ?? 0);
$session_query = "SELECT q.title FROM live_quizzes l 
                  JOIN quizzes q ON l.quiz_id = q.id 
                  WHERE l.id = '$live_id'";

$session_res = mysqli_query($conn, $session_query);
$session_info = mysqli_fetch_assoc($session_res);
$quiz_title = $session_info['title'] ?? 'Live Quiz';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Quiz - <?= htmlspecialchars($quiz_title) ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #5A0E24;
            --secondary-color: #5d9415;
            --bg-color: #f4f4f9;
            --white: #ffffff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--primary-color);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        #game-area {
            width: 100%;
            max-width: 600px;
            transition: all 0.3s ease;
        }

        /* Card Container */
        .quiz-card {
            background: var(--white);
            color: #333;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            text-align: center;
        }

        /* Typography */
        .question-meta {
            color: var(--primary-color);
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1rem;
            display: block;
        }

        .question-text {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            line-height: 1.3;
        }

        .options-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* 2 per row */
    gap: 15px;
    margin-top: 20px;
}

.option-btn {
    padding: 15px;
    border: 2px solid #5d9415;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    font-size: 18px;
    font-weight: 600;
    color: #333;
    text-align: center;
    transition: 0.2s;
}

.option-btn:hover {
    background: #eaf7d6;
    transform: scale(1.05);
}

        /* States */
        .status-msg { margin-top: 20px; }
        .loader { font-size: 3rem; color: var(--secondary-color); margin-bottom: 1rem; }
        .spin { animation: fa-spin 2s infinite linear; }
    </style>
</head>
<body>

    <main id="game-area">
        <div class="quiz-card">
            <div class="loader"><i class="fas fa-circle-notch fa-spin"></i></div>
            <h2>Connecting...</h2>
            <p>Please wait for the teacher to start the session.</p>
        </div>
    </main>

    <script>
let currentQuestionPointer = 0;
let isAnswered = false;

function syncWithTeacher() {
    fetch(`sync_student.php?live_id=<?= $live_id ?>`)
        .then(r => r.json())
        .then(data => {
            console.log(data);

            if (data.status === 'finished') {
                window.location = `live_results.php?live_id=<?= $live_id ?>`;
                return;
            }

            if (data.status === 'running' && data.current_question !== currentQuestionPointer) {
                currentQuestionPointer = data.current_question;
                isAnswered = false;
                loadQuestion(data.question_data);
            }
        })
        .catch(err => console.log(err));
}

function loadQuestion(q) {
    const area = document.getElementById('game-area');

    if (!q) return;

    const options = q.options || [];

    const optionsHtml = options.map((opt) => {
        return `<button class="option-btn"
            onclick="submitAnswer(${q.id}, ${opt.id})">
            ${opt.option_text}
        </button>`;
    }).join('');

    area.innerHTML = `
        <div class="container">
            <span class="question-number">QUESTION ${currentQuestionPointer}</span>
            <h2 class="question-text">${q.question_text}</h2>
            <div class="options-container">${optionsHtml}</div>
        </div>`;
}

function submitAnswer(qId, choiceId) {
    if (isAnswered) return;
    isAnswered = true;

    document.getElementById('game-area').innerHTML = `
        <div class="container">
            <div class="loader"><i class="fas fa-check-circle"></i></div>
            <h2>Answer Submitted!</h2>
            <p>Waiting for the next question...</p>
        </div>`;

    fetch(`submit_live_answer.php?q_id=${qId}&choice=${choiceId}&live_id=<?= $live_id ?>`)
        .then(r => r.json())
        .then(res => console.log(res));
}

setInterval(syncWithTeacher, 2000);
</script>
</body>
</html>