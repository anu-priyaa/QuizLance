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
   ADD QUESTION
   ========================= */
if (isset($_POST['add_question'])) {

    $question_type = $_POST['question_type'];
    $question_text = trim(mysqli_real_escape_string($conn, $_POST['question_text']));
    $hint          = trim(mysqli_real_escape_string($conn, $_POST['hint']));
    $explanation   = trim(mysqli_real_escape_string($conn, $_POST['answer_explanation']));
    $marks         = (int) $_POST['marks'];

    $media_path = NULL;

    if ($question_text === '' || $marks <= 0 || $question_type === '') {
        $error = "Question type, question and marks are required";
    }

    /* ===== MEDIA UPLOAD (IMAGE / VIDEO / AUDIO) ===== */
    if (!isset($error) && in_array($question_type, ['image','video','audio'])) {

        if (empty($_FILES['media_file']['name'])) {
            $error = "Media file is required for this question type";
        } else {

            $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));

            $allowed = [
                'image' => ['jpg','jpeg','png'],
                'video' => ['mp4','webm'],
                'audio' => ['mp3','wav']
            ];

            if (!in_array($ext, $allowed[$question_type])) {
                $error = "Invalid file type for $question_type question";
            } else {

                $folder = "uploads/questions/";
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }

                $fileName = uniqid($question_type."_") . "." . $ext;
                $media_path = $folder . $fileName;

                move_uploaded_file($_FILES['media_file']['tmp_name'], $media_path);
            }
        }
    }

    /* ===== INSERT QUESTION ===== */
    if (!isset($error)) {

        mysqli_query(
            $conn,
            "INSERT INTO questions
            (quiz_id, question_type, question_text, media_path, hint, answer_explanation, marks)
            VALUES
            ($quiz_id, '$question_type', '$question_text', '$media_path', '$hint', '$explanation', $marks)"
        );

        $question_id = mysqli_insert_id($conn);

        /* ===== MCQ OPTIONS ===== */
        if ($question_type === 'mcq') {

            for ($i = 1; $i <= 4; $i++) {
                $opt = trim(mysqli_real_escape_string($conn, $_POST["option$i"]));
                $is_correct = ($_POST['correct_option'] == $i) ? 1 : 0;

                if ($opt !== '') {
                    mysqli_query(
                        $conn,
                        "INSERT INTO question_options
                        (question_id, option_text, is_correct)
                        VALUES
                        ($question_id, '$opt', $is_correct)"
                    );
                }
            }
        }

        /* ===== TRUE / FALSE ===== */
        if ($question_type === 'true_false') {

            $answer = $_POST['true_false_answer'];

            mysqli_query(
                $conn,
                "INSERT INTO question_answers
                (question_id, correct_answer)
                VALUES
                ($question_id, '$answer')"
            );
        }

        /* ===== ONE WORD / FILL BLANK ===== */
        if ($question_type === 'one_word' || $question_type === 'fill_blank') {

            $answer = trim(mysqli_real_escape_string($conn, $_POST['text_answer']));

            mysqli_query(
                $conn,
                "INSERT INTO question_answers
                (question_id, correct_answer)
                VALUES
                ($question_id, '$answer')"
            );
        }

        $success = "Question added successfully";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Questions | QuizLance</title>

<style>
body { font-family:'Segoe UI', sans-serif; background:#f0f2f5; padding:40px; }

.card {
    background:white;
    max-width:800px;
    margin:auto;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

h2 { color:#5A0E24; margin-bottom:20px; }

.form-group { margin-bottom:15px; }
label { font-weight:bold; display:block; margin-bottom:6px; }

input, textarea, select {
    width:100%;
    padding:10px;
    border-radius:5px;
    border:1px solid #ccc;
}

textarea { resize:vertical; }

.btn {
    background:#5d9415;
    color:white;
    padding:10px 18px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
    margin-right:10px;
}

.alert-success { color:green; font-weight:bold; margin-top:10px; }
.alert-error { color:red; font-weight:bold; margin-top:10px; }

.hidden { display:none; }

.actions {
    margin-top:25px;
    display:flex;
    justify-content:space-between;
}
</style>
</head>

<body>

<div class="card">
<h2>Add Question</h2>

<form method="POST" enctype="multipart/form-data">

    <div class="form-group">
        <label>Question Type *</label>
        <select name="question_type" id="question_type" required onchange="toggleFields()">
            <option value="">-- Select --</option>
            <option value="mcq">MCQ</option>
            <option value="true_false">True / False</option>
            <option value="one_word">One Word</option>
            <option value="fill_blank">Fill in the Blank</option>
            <option value="image">Image Based</option>
            <option value="video">Video Based</option>
            <option value="audio">Audio Based</option>
        </select>
    </div>

    <div class="form-group">
        <label>Question *</label>
        <textarea name="question_text" required></textarea>
    </div>

    <!-- MEDIA -->
    <div id="media_fields" class="hidden">
        <label>Upload Media *</label>
        <input type="file" name="media_file" accept="image/*,video/*,audio/*">
    </div>

    <!-- MCQ -->
    <div id="mcq_fields" class="hidden">
        <?php for ($i=1;$i<=4;$i++): ?>
            <div class="form-group">
                <label>Option <?= $i ?></label>
                <input type="text" name="option<?= $i ?>">
            </div>
        <?php endfor; ?>

        <label>Correct Option</label>
        <select name="correct_option">
            <option value="1">Option 1</option>
            <option value="2">Option 2</option>
            <option value="3">Option 3</option>
            <option value="4">Option 4</option>
        </select>
    </div>

    <!-- TRUE/FALSE -->
    <div id="tf_fields" class="hidden">
        <label>Correct Answer</label>
        <select name="true_false_answer">
            <option value="True">True</option>
            <option value="False">False</option>
        </select>
    </div>

    <!-- TEXT ANSWER -->
    <div id="text_fields" class="hidden">
        <label>Correct Answer</label>
        <input type="text" name="text_answer">
    </div>

    <div class="form-group">
        <label>Hint (optional)</label>
        <textarea name="hint"></textarea>
    </div>

    <div class="form-group">
        <label>Answer Explanation (optional)</label>
        <textarea name="answer_explanation"></textarea>
    </div>

    <div class="form-group">
        <label>Marks *</label>
        <input type="number" name="marks" min="1" required>
    </div>

    <div class="actions">
        <button class="btn" name="add_question">➕ Add Question</button>
        <a href="review_quiz.php?quiz_id=<?= $quiz_id ?>" class="btn">👁 Review & Publish</a>
    </div>

</form>

<?php if(isset($success)) echo "<div class='alert-success'>$success</div>"; ?>
<?php if(isset($error)) echo "<div class='alert-error'>$error</div>"; ?>

</div>

<script>
function toggleFields() {
    ['mcq_fields','tf_fields','text_fields','media_fields']
        .forEach(id => document.getElementById(id).classList.add('hidden'));

    let type = document.getElementById('question_type').value;

    if (type === 'mcq') document.getElementById('mcq_fields').classList.remove('hidden');
    if (type === 'true_false') document.getElementById('tf_fields').classList.remove('hidden');
    if (type === 'one_word' || type === 'fill_blank')
        document.getElementById('text_fields').classList.remove('hidden');
    if (['image','video','audio'].includes(type))
        document.getElementById('media_fields').classList.remove('hidden');
}
</script>

</body>
</html>
