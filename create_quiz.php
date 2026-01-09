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

$teacher_id = $_SESSION['user_id'];

/* =========================
   FETCH TEACHER INFO
   ========================= */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);

/* =========================
   FETCH CLASSES
   ========================= */
$classes = mysqli_query(
    $conn,
    "SELECT id, class_name, class_code 
     FROM Classes 
     WHERE teacher_id=$teacher_id"
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

    if (
        $title === '' ||
        $class_id === 0 ||
        $start_time === '' ||
        $end_time === '' ||
        $duration <= 0 ||
        $pass_marks < 0
    ) {
        $error = "All required fields must be filled correctly";
    } else {

        mysqli_query(
            $conn,
            "INSERT INTO quizzes
            (teacher_id, class_id, title, description, start_time, end_time, duration, pass_marks)
            VALUES
            ($teacher_id, $class_id, '$title', '$description', '$start_time', '$end_time', $duration, $pass_marks)"
        );

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
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { display:flex; background:#f0f2f5; min-height:100vh; }

/* ===== SIDEBAR ===== */
.sidebar {
    width:260px;
    background:#5A0E24;
    color:white;
    display:flex;
    flex-direction:column;
    position:fixed;
    height:100vh;
}

.sidebar-profile {
    text-align:center;
    padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
}

.sidebar-profile img {
    width:85px;
    height:85px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #5d9415;
}

.sidebar-profile h3 { margin-top:10px; font-size:16px; }

.sidebar a {
    padding:15px 25px;
    text-decoration:none;
    color:#d1d1d1;
    display:flex;
    align-items:center;
}

.sidebar a i { margin-right:15px; width:20px; }

.sidebar a:hover,
.sidebar a.active {
    background:#861434;
    color:white;
}

.logout {
    margin-top:auto;
    border-top:1px solid rgba(255,255,255,0.15);
}

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left:260px;
    flex:1;
    padding:40px;
}

.card {
    background:white;
    padding:30px;
    border-radius:15px;
    max-width:600px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

.card h2 {
    color:#5A0E24;
    margin-bottom:20px;
}

.form-group { margin-bottom:15px; }
.form-group label { display:block; font-weight:bold; margin-bottom:6px; }

.form-group input,
.form-group textarea,
.form-group select {
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
}

.alert-error {
    color:red;
    font-weight:bold;
    margin-top:10px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-profile">
        <img src="<?= $teacher['profile_pic'] ?: 'https://via.placeholder.com/85' ?>">
        <h3><?= htmlspecialchars($teacher['name']) ?></h3>
    </div>

    <a href="teacher_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="profile.php"><i class="fas fa-user-edit"></i> Profile</a>

    <div class="logout">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
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
                            <?= htmlspecialchars($c['class_name']) ?> (<?= $c['class_code'] ?>)
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

            <button type="submit" name="create_quiz" class="btn">
                Create Quiz & Add Questions →
            </button>
        </form>

        <?php if (isset($error)) echo "<div class='alert-error'>$error</div>"; ?>
    </div>

</div>

</body>
</html>
