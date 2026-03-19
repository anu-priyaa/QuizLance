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
            $options = [
                mysqli_real_escape_string($conn, $_POST['option1']),
                mysqli_real_escape_string($conn, $_POST['option2']),
                mysqli_real_escape_string($conn, $_POST['option3']),
                mysqli_real_escape_string($conn, $_POST['option4'])
            ];
            $correct = (int)$_POST['correct'];

            foreach ($options as $index => $opt) {
                if (trim($opt) == "") continue;
                $is_correct = ($correct == $index + 1) ? 1 : 0;
                mysqli_query($conn, "INSERT INTO sample_question_options (question_id, option_text, is_correct) 
                                     VALUES ($question_id, '$opt', $is_correct)");
            }
        }
        header("Location: add_sample_questions.php?quiz_id=$quiz_id&success=1");
        exit();
    }
}

/* =========================
   DELETE QUESTION LOGIC 
   ========================= */
if (isset($_POST['delete_question'])) {
    $qid = (int)$_POST['question_id'];
    mysqli_query($conn, "DELETE FROM sample_question_options WHERE question_id=$qid");
    mysqli_query($conn, "DELETE FROM sample_questions WHERE id=$qid");
    header("Location: add_sample_questions.php?quiz_id=$quiz_id&deleted=1");
    exit();
}

/* =========================
   POST QUIZ LOGIC (FIXED)
   ========================= */
if (isset($_POST['post_quiz'])) {
    mysqli_query($conn, "UPDATE sample_quizzes SET status='posted' WHERE id=$quiz_id");
    // Redirect back to THIS page with posted=1 to show the message
    header("Location: add_sample_questions.php?quiz_id=$quiz_id&posted=1"); 
    exit();
}

$questions = mysqli_query($conn, "SELECT * FROM sample_questions WHERE quiz_id = '$quiz_id' ORDER BY id DESC");
$qcount = mysqli_num_rows($questions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Questions | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
        body { background:#f0f2f5; }
        .topbar { position:fixed; top:0; left:0; width:100%; height:60px; background:#5A0E24; color:white; display:flex; align-items:center; padding:0 20px; z-index:1001; }
        .top-profile { margin-left:auto; display:flex; align-items:center; gap:8px; cursor:pointer; position:relative; }
        .top-profile img { width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #5d9415; }
        .profile-dropdown { display:none; position:absolute; right:0; top:55px; background:white; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.15); min-width:180px; }
        .profile-dropdown a { display:flex; align-items:center; gap:10px; padding:12px 15px; text-decoration:none; color:#333; }
        .main-content { padding:80px 40px; max-width: 1000px; margin: auto; }
        .card { background:white; padding:25px; border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,.05); border-left:5px solid #5d9415; margin-bottom:25px; }
        .card h2 { color:#5A0E24; margin-bottom:15px; }
        input, textarea, select { width:100%; padding:10px; margin-top:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:6px; }
        button { background:#5d9415; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer; font-weight:bold; transition: 0.3s; }
        button:hover { opacity: 0.9; transform: translateY(-1px); }
        .delete-btn { background:#dc3545; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom:25px; display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .alert-success { color:#155724; background-color:#d4edda; border: 1px solid #c3e6cb; }
        .alert-info { color:#0d47a1; background-color:#e3f2fd; border: 1px solid #bbdefb; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px; border-bottom:1px solid #ddd; text-align: left; }
        th { background:#5A0E24; color:white; }
        .back-link { display:inline-block; background:#5A0E24; color:white; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold; margin-bottom:20px; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="main-content">
    
    <?php if($posted_success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Sample Quiz posted successfully! It is now visible to students.
        </div>
    <?php endif; ?>

    <?php if($add_success): ?>
        <div class="alert alert-info">
            <i class="fas fa-plus-circle"></i> Question added successfully!
        </div>
    <?php endif; ?>

    <?php if($delete_success): ?>
        <div class="alert alert-info" style="background-color: #fff3cd; color: #856404; border-color: #ffeeba;">
            <i class="fas fa-trash-alt"></i> Question deleted successfully.
        </div>
    <?php endif; ?>

    <a href="sample_quizzes.php" class="back-link">← Back to Quizzes</a>

    <div class="card">
        <h2>Add New Question</h2>
        <form method="POST">
            <label>Question Type</label>
            <select name="question_type" id="question_type" onchange="changeType()">
                <option value="mcq">Multiple Choice (MCQ)</option>
                <option value="true_false">True / False</option>
                <option value="one_word">One Word</option>
            </select>

            <label>Question Text</label>
            <textarea name="question" required placeholder="Enter your question..."></textarea>

            <div id="mcq_fields">
                <input type="text" name="option1" placeholder="Option 1">
                <input type="text" name="option2" placeholder="Option 2">
                <input type="text" name="option3" placeholder="Option 3">
                <input type="text" name="option4" placeholder="Option 4">
                <label>Correct Option</label>
                <select name="correct">
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
            </div>

            <div id="tf_fields" style="display:none">
                <label>Correct Answer</label>
                <select name="tf_answer">
                    <option value="True">True</option>
                    <option value="False">False</option>
                </select>
            </div>

            <div id="oneword_fields" style="display:none">
                <label>Correct Answer</label>
                <input type="text" name="oneword_answer" placeholder="Type the one-word answer">
            </div>

            <label>Explanation (Optional)</label>
            <textarea name="explanation" placeholder="Why is this answer correct?"></textarea>

            <button type="submit" name="add_question"><i class="fas fa-plus"></i> Add Question</button>
        </form>
    </div>

    <div class="card">
        <h2>Manage Questions (<?= $qcount ?>)</h2>
        <table>
            <thead>
                <tr><th>Question</th><th>Type</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if($qcount == 0): ?>
                    <tr><td colspan="3" style="text-align:center;">No questions added yet.</td></tr>
                <?php else: ?>
                    <?php while($q = mysqli_fetch_assoc($questions)): ?>
                        <tr>
                            <td><?= htmlspecialchars($q['question_text']) ?></td>
                            <td><span style="font-size:12px; background:#eee; padding:2px 6px; border-radius:4px;"><?= ucfirst($q['question_type']) ?></span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this question?');">
                                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                    <button type="submit" class="delete-btn" name="delete_question">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="text-align:center; margin-top: 20px;">
        <form method="POST" onsubmit="return confirm('Ready to publish? Students will be able to see this quiz immediately.');">
            <button type="submit" name="post_quiz" style="background:#5A0E24; padding:15px 50px; font-size: 1.2rem; border-radius: 50px; box-shadow: 0 4px 15px rgba(90,14,36,0.3);">
                <i class="fas fa-paper-plane"></i> Finalize & Post Sample Quiz
            </button>
        </form>
    </div>
</div>

<script>
// Toggle field visibility based on question type
function changeType() {
    let type = document.getElementById("question_type").value;
    document.getElementById("mcq_fields").style.display = (type == "mcq") ? "block" : "none";
    document.getElementById("tf_fields").style.display = (type == "true_false") ? "block" : "none";
    document.getElementById("oneword_fields").style.display = (type == "one_word") ? "block" : "none";
}

// User Profile dropdown
function toggleProfileMenu() {
    const m = document.getElementById('profileDropdown');
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}

// Automatically hide alerts after 3 seconds
setTimeout(function() {
    let alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = "opacity 0.6s ease";
        alert.style.opacity = "0";
        setTimeout(() => alert.remove(), 600);
    });
}, 3000);

// Close dropdown on outside click
document.addEventListener('click', e => {
    const p = document.querySelector('.top-profile');
    if (p && !p.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});
</script>

</body>
</html>