<?php
session_start();

/* =========================
   ROLE PROTECTION
   ========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

/* =========================
   RULES PROTECTION
   ========================= */
$quiz_rules = $_SESSION['quiz_rules'] ?? null;
if (!$quiz_rules) {
    header("Location: quiz_rules.php");
    exit();
}

/* =========================
   DATABASE CONNECTION
   ========================= */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];

/* =========================
   FETCH TEACHER INFO
   ========================= */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$imgSrc = $teacher['profile_pic']
    ? $teacher['profile_pic'] . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* =========================
   FETCH CLASSES
   ========================= */
$classes = mysqli_query(
    $conn,
    "SELECT id, class_name
     FROM Classes
     WHERE teacher_id=$teacher_id AND status='active'"
);


/* =========================
   CREATE QUIZ
   ========================= */
if (isset($_POST['create_quiz'])) {

    $title       = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $class_id    = (int) $_POST['class_id'];
    $start_time  = $_POST['start_time'];
    $end_time    = $_POST['end_time'];
    $duration    = (int) $_POST['duration'];
    $pass_marks  = (int) $_POST['pass_marks'];

    // Negative marking (optional)
    $negative_marks = ($_POST['negative_marks'] !== '')
        ? (float) $_POST['negative_marks']
        : 0;

    /* VALIDATION */
    if (
        $title === '' ||
        $class_id === 0 ||
        $start_time === '' ||
        $end_time === '' ||
        $duration <= 0 ||
        $pass_marks < 0 ||
        $negative_marks < 0
    ) {
        $error = "All required fields must be filled correctly";
    } else {

        $rules_text = mysqli_real_escape_string($conn, $quiz_rules);

        mysqli_query(
            $conn,
            "INSERT INTO quizzes
            (
                teacher_id, class_id, title, description,
                start_time, end_time, duration,
                pass_marks, negative_marks,
                status, quiz_rules
            )
            VALUES
            (
                $teacher_id, $class_id, '$title', '$description',
                '$start_time', '$end_time', $duration,
                $pass_marks, $negative_marks,
                'draft', '$rules_text'
            )"
        );

        unset($_SESSION['quiz_rules']);
        $quiz_id = mysqli_insert_id($conn);

        header("Location: add_questions.php?quiz_id=$quiz_id");
        exit();
    }
}

?>




<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Quiz | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5}

/* TOP BAR */
.topbar{
    position:fixed;top:0;left:0;
    width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;
    padding:0 20px;z-index:1001
}
.topbar i{font-size:24px;cursor:pointer}

/* SIDEBAR */
.sidebar{
    width:260px;background:#5A0E24;color:white;
    display:flex;flex-direction:column;
    position:fixed;top:60px;left:0;
    height:calc(100vh - 60px);
    transition:.3s ease;z-index:1000
}
.sidebar.collapsed{transform:translateX(-100%)}
.sidebar-profile{
    text-align:center;padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,.15)
}
.sidebar-profile img{
    width:85px;height:85px;border-radius:50%;
    border:3px solid #5d9415;object-fit:cover
}
.sidebar-profile h3{margin-top:10px;font-size:16px}

.sidebar a{
    padding:15px 25px;text-decoration:none;
    color:#d1d1d1;display:flex;align-items:center
}
.sidebar a i{margin-right:15px;width:20px}
.sidebar a:hover,.sidebar a.active{
    background:#861434;color:white
}
.logout{
    margin-top:auto;
    border-top:1px solid rgba(255,255,255,.15)
}

/* MAIN CONTENT */
.main-content{
    margin-left:260px;
    padding:90px 40px 40px
}

/* CARD */
.card{
    background:white;
    padding:30px;
    border-radius:15px;
    max-width:600px;
    box-shadow:0 4px 12px rgba(0,0,0,.05);
    border-left:5px solid #5d9415
}
.card h2{color:#5A0E24;margin-bottom:20px}

.form-group{margin-bottom:15px}
.form-group label{font-weight:bold;display:block;margin-bottom:6px}
input,textarea,select{
    width:100%;padding:10px;
    border-radius:5px;border:1px solid #ccc
}
textarea{resize:vertical}

.btn{
    background:#5d9415;color:white;
    padding:10px 18px;border:none;
    border-radius:6px;font-weight:bold;
    cursor:pointer
}

.alert-error{color:red;font-weight:bold;margin-top:10px}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars"></i>
</div>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-profile">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>

    <a href="teacher_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="manage_classes.php"><i class="fas fa-users"></i> Manage Class</a>
    <a href="view_students.php"><i class="fas fa-eye"></i> View Students</a>
    <a href="view_attendance_teacher.php"><i class="fas fa-clipboard-list"></i> Attendance</a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

<div class="card">
<h2>Create New Quiz</h2>

<form method="POST">

    <div class="form-group">
        <label>Quiz Title *</label>
        <input type="text" name="title" required>
    </div>

    <div class="form-group">
        <label>Description (optional)</label>
        <textarea name="description" rows="3"></textarea>
    </div>

    <div class="form-group">
        <label>Select Class *</label>
        <select name="class_id" required>
            <option value="">-- Select Class --</option>
            <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                <option value="<?= $c['id'] ?>">
                    <?= htmlspecialchars($c['class_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Start Time *</label>
        <input type="datetime-local" name="start_time" required>
    </div>

    <div class="form-group">
        <label>End Time *</label>
        <input type="datetime-local" name="end_time" required>
    </div>

    <div class="form-group">
        <label>Duration (minutes) *</label>
        <input type="number" name="duration" min="1" required>
    </div>

    <div class="form-group">
        <label>Passing Marks *</label>
        <input type="number" name="pass_marks" min="0" required>
    </div>

    <div class="form-group">
        <label>Negative Marks for Wrong Answer (optional)</label>
        <input type="number" step="0.25" name="negative_marks" placeholder="Default: 0">
    </div>

    <button type="submit" name="create_quiz" class="btn">
        Create Quiz & Add Questions →
    </button>

</form>

<?php if(isset($error)) echo "<div class='alert-error'>$error</div>"; ?>

</div>
</div>

</body>
</html>
