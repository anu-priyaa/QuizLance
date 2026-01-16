<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quiz_id === 0) {
    die("Invalid Quiz ID");
}

/* =========================
   FETCH QUIZ DETAILS
   ========================= */
$quizRes = mysqli_query($conn,
    "SELECT q.*, c.class_name, c.class_code
     FROM quizzes q
     JOIN Classes c ON q.class_id = c.id
     WHERE q.id = $quiz_id"
);

$quiz = mysqli_fetch_assoc($quizRes);
if (!$quiz) {
    die("Quiz not found");
}

/* =========================
   PUBLISH QUIZ
   ========================= */
if (isset($_POST['publish_quiz'])) {

    $shuffle = isset($_POST['shuffle_questions']) ? 'yes' : 'no';

    $checkQ = mysqli_query($conn,
        "SELECT id FROM questions WHERE quiz_id = $quiz_id"
    );

    if (mysqli_num_rows($checkQ) == 0) {
        $error = "Add at least one question before publishing";
    } else {
        mysqli_query($conn,
            "UPDATE quizzes 
             SET status='scheduled',
                 shuffle_questions='$shuffle'
             WHERE id=$quiz_id"
        );
        $success = "Quiz published successfully!";
    }
}


/* =========================
   FETCH QUESTIONS
   ========================= */
$questions = mysqli_query($conn,
    "SELECT * FROM questions WHERE quiz_id=$quiz_id"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Review Quiz | QuizLance</title>

<style>
body {
    font-family:'Segoe UI', sans-serif;
    background:#f0f2f5;
    padding:40px;
}

.card {
    background:white;
    max-width:900px;
    margin:auto;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

h2, h3 { color:#5A0E24; }

.quiz-info p {
    margin:6px 0;
}

.question-box {
    background:#fafafa;
    padding:20px;
    border-radius:10px;
    margin-bottom:20px;
    border-left:4px solid #5A0E24;
}

.option {
    margin-left:15px;
}

.correct {
    color:green;
    font-weight:bold;
}

.media {
    margin-top:10px;
}

.btn {
    padding:10px 18px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}

.btn-add {
    background:#5A0E24;
    color:white;
}

.btn-publish {
    background:#5d9415;
    color:white;
    
}

.btn-back {
    background:#5A0E24;
    color:white;
    margin-top:15px;
    display:inline-block;
    text-decoration:none;
}

.alert-success {
    color:green;
    font-weight:bold;
    margin-top:15px;
}

.alert-error {
    color:red;
    font-weight:bold;
    margin-top:15px;
}
</style>
</head>

<body>

<div class="card">
    <?php if (isset($success)): ?>
        <div class='alert-success'><?= $success ?></div>
        <a href="teacher_dashboard.php" class="btn btn-back">⬅️ Back to Dashboard</a>
    <?php endif; ?>
    <h2>Review Quiz</h2>

    <!-- QUIZ INFO -->
    <div class="quiz-info">
        <p><strong>Title:</strong> <?= htmlspecialchars($quiz['title']) ?></p>
        <p><strong>Class:</strong> <?= htmlspecialchars($quiz['class_name']) ?> (<?= $quiz['class_code'] ?>)</p>
        <p><strong>Duration:</strong> <?= $quiz['duration'] ?> minutes</p>
        <p><strong>Status:</strong> <?= ucfirst($quiz['status']) ?></p>
    </div>

    <hr><br>

    <h3>Questions</h3>

    <?php if (mysqli_num_rows($questions) == 0): ?>
        <p>No questions added yet.</p>
    <?php endif; ?>

    <?php while ($q = mysqli_fetch_assoc($questions)): ?>
        <div class="question-box">

            <p><strong>Q:</strong> <?= htmlspecialchars($q['question_text']) ?></p>
            <p><em>Type:</em> <?= ucfirst(str_replace('_',' ', $q['question_type'])) ?></p>
            <p><em>Marks:</em> <?= $q['marks'] ?></p>

            <!-- MEDIA -->
            <?php if (!empty($q['media_path'])): ?>
                <div class="media">
                    <?php if ($q['question_type'] === 'image'): ?>
                        <img src="<?= $q['media_path'] ?>" width="200">
                    <?php elseif ($q['question_type'] === 'video'): ?>
                        <video src="<?= $q['media_path'] ?>" controls width="300"></video>
                    <?php elseif ($q['question_type'] === 'audio'): ?>
                        <audio src="<?= $q['media_path'] ?>" controls></audio>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- MCQ OPTIONS -->
            <?php if ($q['question_type'] === 'mcq'): ?>
                <?php
                $opts = mysqli_query($conn,
                    "SELECT * FROM question_options WHERE question_id=".$q['id']
                );
                while ($o = mysqli_fetch_assoc($opts)):
                ?>
                    <div class="option <?= $o['is_correct'] ? 'correct' : '' ?>">
                        • <?= htmlspecialchars($o['option_text']) ?>
                        <?= $o['is_correct'] ? '(Correct)' : '' ?>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <!-- ANSWER -->
            <?php
            $ans = mysqli_query($conn,
                "SELECT correct_answer FROM question_answers WHERE question_id=".$q['id']
            );
            if ($a = mysqli_fetch_assoc($ans)):
            ?>
                <p class="correct">Answer: <?= htmlspecialchars($a['correct_answer']) ?></p>
            <?php endif; ?>

            <?php if ($q['hint']): ?>
                <p><strong>Hint:</strong> <?= htmlspecialchars($q['hint']) ?></p>
            <?php endif; ?>

            <?php if ($q['answer_explanation']): ?>
                <p><strong>Explanation:</strong> <?= htmlspecialchars($q['answer_explanation']) ?></p>
            <?php endif; ?>

        </div>
    <?php endwhile; ?>

    <!-- ACTION BUTTONS -->
    <form method="post">
        <a href="add_questions.php?quiz_id=<?= $quiz_id ?>" class="btn btn-add">
            ➕ Add More Questions
        </a>

        <label style="display:block; margin:15px 0; font-weight:bold;">
    <input type="checkbox" name="shuffle_questions" value="yes"
        <?= ($quiz['shuffle_questions'] === 'yes') ? 'checked' : '' ?>>
    Shuffle questions for students
</label>


        <?php if ($quiz['status'] === 'draft'): ?>
            <button name="publish_quiz" class="btn btn-publish">
                🚀 Publish Quiz
            </button>
        <?php endif; ?>
    </form>

    

    <?php if (isset($error)): ?>
        <div class='alert-error'><?= $error ?></div>
    <?php endif; ?>

</div>

</body>
</html>
