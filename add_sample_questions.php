<?php
session_start();

/* =========================
   ROLE PROTECTION
   ========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) { die("Database connection failed"); }

$teacher_id = $_SESSION['user_id'];
if (!isset($_GET['quiz_id'])) { 
    header("Location: sample_quizzes.php"); 
    exit(); 
}

$quiz_id = (int)$_GET['quiz_id'];

// Check flags for success messages
$posted_success = isset($_GET['posted']) && $_GET['posted'] == 1;
$add_success = isset($_GET['success']) && $_GET['success'] == 1;
$delete_success = isset($_GET['deleted']) && $_GET['deleted'] == 1;

/* =========================
   QUIZ STATUS CHECK
   ========================= */
$quiz_q = mysqli_query($conn, "SELECT status, title FROM sample_quizzes WHERE id=$quiz_id");
$quiz_data = mysqli_fetch_assoc($quiz_q);
$is_posted = ($quiz_data['status'] == 'posted');

/* =========================
   TEACHER INFO 
   ========================= */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);
$teacher_name = $teacher['name'];
$imgSrc = $teacher['profile_pic'] ? $teacher['profile_pic'] . '?t=' . time() : "https://via.placeholder.com/85";

/* =========================
   ADD QUESTION LOGIC
   ========================= */
if (isset($_POST['add_question'])) {
    $type = $_POST['question_type'];
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $explanation = mysqli_real_escape_string($conn, $_POST['explanation']);

    $correct_text = "";
    if ($type == "true_false") {
        $correct_text = $_POST['tf_answer'];
    } elseif ($type == "one_word") {
        $correct_text = mysqli_real_escape_string($conn, $_POST['oneword_answer']);
    }

    $query = "INSERT INTO sample_questions (quiz_id, question_text, question_type, answer_explanation, correct_answer_text) 
              VALUES ($quiz_id, '$question', '$type', '$explanation', '$correct_text')";
    
    if (mysqli_query($conn, $query)) {
        $question_id = mysqli_insert_id($conn);
        if ($type == "mcq") {
            for($i=1; $i<=4; $i++) {
                $opt = mysqli_real_escape_string($conn, $_POST["option$i"]);
                if (trim($opt) == "") continue;
                $is_correct = ((int)$_POST['correct'] == $i) ? 1 : 0;
                mysqli_query($conn, "INSERT INTO sample_question_options (question_id, option_text, is_correct) VALUES ($question_id, '$opt', $is_correct)");
            }
        }
        header("Location: add_sample_questions.php?quiz_id=$quiz_id&success=1");
        exit();
    }
}

/* =========================
   POST QUIZ LOGIC
   ========================= */
if (isset($_POST['post_quiz'])) {
    mysqli_query($conn, "UPDATE sample_quizzes SET status='posted' WHERE id=$quiz_id");
    header("Location: add_sample_questions.php?quiz_id=$quiz_id&posted=1"); 
    exit();
}

/* =========================
   DELETE LOGIC
   ========================= */
if (isset($_POST['delete_question'])) {
    $qid = (int)$_POST['question_id'];
    mysqli_query($conn, "DELETE FROM sample_question_options WHERE question_id=$qid");
    mysqli_query($conn, "DELETE FROM sample_questions WHERE id=$qid");
    header("Location: add_sample_questions.php?quiz_id=$quiz_id&deleted=1");
    exit();
}

$questions = mysqli_query($conn, "SELECT * FROM sample_questions WHERE quiz_id = '$quiz_id' ORDER BY id DESC");
$qcount = mysqli_num_rows($questions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions | <?= htmlspecialchars($quiz_data['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
        body { background:#f0f2f5; padding-bottom: 60px; }
        .topbar { position:fixed; top:0; left:0; width:100%; height:60px; background:#5A0E24; color:white; display:flex; align-items:center; padding:0 20px; z-index:1001; }
        .top-profile { margin-left:auto; display:flex; align-items:center; gap:8px; cursor:pointer; position:relative; }
        .top-profile img { width:36px; height:36px; border-radius:50%; border:2px solid #5d9415; }
        .profile-dropdown { display:none; position:absolute; right:0; top:55px; background:white; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.15); min-width:150px; }
        .profile-dropdown a { display:block; padding:12px; text-decoration:none; color:#333; }

        .main-content { padding:80px 20px; max-width: 900px; margin: auto; }
        .card { background:white; padding:25px; border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,.05); margin-bottom:20px; border-left: 5px solid #5d9415; }
        
        .finish-card { background: #fff; border-left: 5px solid #5A0E24; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        
        .pulse-btn { 
            background:#5A0E24; color:white; padding:12px 25px; border-radius:50px; border:none; font-weight:bold; cursor:pointer;
            box-shadow: 0 0 0 0 rgba(90, 14, 36, 0.7);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(90, 14, 36, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(90, 14, 36, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(90, 14, 36, 0); }
        }

        input, textarea, select { width:100%; padding:10px; margin-top:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:6px; }
        
        button.add-btn { background:#5d9415; color:white; border:none; padding:12px 20px; border-radius:6px; cursor:pointer; font-weight:bold; width: 100%; }
        .delete-btn { background:#dc3545; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; }
        
        table { width:100%; border-collapse:collapse; margin-top: 10px; }
        th, td { padding:12px; border-bottom:1px solid #eee; text-align: left; }
        th { background:#f8f9fa; color:#5A0E24; font-size: 13px; }
        .q-text { max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom:20px; font-weight: 500; }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .back-link { text-decoration:none; color:#5A0E24; font-weight:bold; display:inline-block; margin-bottom:15px; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="main-content">
    
    <a href="sample_quizzes.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>

    <?php if($posted_success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> This quiz is now LIVE for students!</div>
    <?php endif; ?>

    <?php if($qcount > 0 && !$is_posted): ?>
    <div class="card finish-card">
        <div>
            <h3 style="color:#5A0E24;">Ready to Publish?</h3>
            <p style="font-size:13px; color:#666;">You have added <?= $qcount ?> questions. Click to make it visible to students.</p>
        </div>
        <form method="POST" onsubmit="return confirm('Students will be able to start this quiz immediately. Continue?');">
            <button type="submit" name="post_quiz" class="pulse-btn">
                <i class="fas fa-paper-plane"></i> Finalize & Post Quiz
            </button>
        </form>
    </div>
    <?php elseif($is_posted): ?>
        <div class="card" style="border-left-color: #5A0E24; background: #fdfdfd;">
            <span style="color: #5A0E24; font-weight: bold;"><i class="fas fa-globe"></i> STATUS: POSTED</span>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Add New Question</h2>
        <form method="POST" id="questionForm">
            <label>Question Type</label>
            <select name="question_type" id="question_type" onchange="changeType()">
                <option value="mcq">Multiple Choice (MCQ)</option>
                <option value="true_false">True / False</option>
                <option value="one_word">One Word</option>
            </select>

           <textarea name="question" id="question_text_area" required 
            placeholder="Type your question here..."></textarea>

            <div id="mcq_fields">
                <input type="text" name="option1" placeholder="Option 1">
                <input type="text" name="option2" placeholder="Option 2">
                <input type="text" name="option3" placeholder="Option 3">
                <input type="text" name="option4" placeholder="Option 4">
                <label>Which option is correct?</label>
                <select name="correct">
                    <option value="1">Option 1</option><option value="2">Option 2</option>
                    <option value="3">Option 3</option><option value="4">Option 4</option>
                </select>
            </div>

            <div id="tf_fields" style="display:none">
                <label>Select Correct Answer</label>
                <select name="tf_answer"><option value="True">True</option><option value="False">False</option></select>
            </div>

            <div id="oneword_fields" style="display:none">
                <input type="text" name="oneword_answer" placeholder="Type the correct word/answer">
            </div>

            <label>Explanation (Optional)</label>
            <textarea name="explanation" placeholder="Explain the correct answer..."></textarea>

            <button type="submit" name="add_question" class="add-btn">Save Question</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Questions (<?= $qcount ?>)</h2>
        <table>
            <thead><tr><th>Question</th><th>Type</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($q = mysqli_fetch_assoc($questions)): ?>
                <tr>
                    <td><div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div></td>
                    <td><small style="color:#888;"><?= strtoupper($q['question_type']) ?></small></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this question?');">
                            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                            <button type="submit" name="delete_question" class="delete-btn"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function changeType() {
    let type = document.getElementById("question_type").value;
    document.getElementById("mcq_fields").style.display = (type == "mcq") ? "block" : "none";
    document.getElementById("tf_fields").style.display = (type == "true_false") ? "block" : "none";
    document.getElementById("oneword_fields").style.display = (type == "one_word") ? "block" : "none";
}

function toggleProfileMenu() {
    const m = document.getElementById('profileDropdown');
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}

window.onload = changeType;
</script>
</body>
</html>