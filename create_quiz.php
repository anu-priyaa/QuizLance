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

/* TOP PROFILE */
.top-profile{
    margin-left:auto;display:flex;align-items:center;
    gap:8px;cursor:pointer;position:relative;
}
.top-profile img{
    width:36px;height:36px;border-radius:50%;
    object-fit:cover;border:2px solid #5d9415;
}
.top-profile span{font-size:14px;font-weight:500;}

/* DROPDOWN */
.profile-dropdown{
    display:none;position:absolute;right:0;top:55px;
    background:white;border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
    min-width:180px;overflow:hidden;z-index:3000;
}
.profile-dropdown a{
    display:flex;align-items:center;gap:10px;
    padding:12px 15px;text-decoration:none;
    color:#333;font-size:14px;
}
.profile-dropdown a:hover{background:#f2f2f2;}

/* MAIN CONTENT */
.main-content{
    padding:70px 40px 40px;
}

.card{
    background:white;
    padding:20px;
    border-radius:15px;
    max-width:700px;          /* ⬅ increased from 600px */
    width:100%;
    box-shadow:0 4px 12px rgba(0,0,0,.05);
    border-left:5px solid #5d9415;
}

.card h2{color:#5A0E24;margin-bottom:20px}

.form-group{margin-bottom:15px}
.form-group label{font-weight:bold;display:block;margin-bottom:6px}
input,textarea,select{
    width:100%;padding:10px;
    border-radius:5px;border:1px solid #ccc
}
textarea{resize:vertical}

/* IMAGE SIDE */
.hero-imageee {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

.hero-imageee img {
    max-width: 95%;
    max-height: 360px;
    height: auto;
}

/* FORM SIDE */
.page-card {
    flex: 1;
}

.rules-layout{
    display:flex;
    gap:40px;
    align-items:center;
}

.rules-layout .card{
    flex: 1.4;   /* ⬅ form gets more width */
}

.rules-layout .hero-imageee{
    flex: 1;     /* ⬅ image slightly smaller */
}



/* RESPONSIVE */
@media (max-width: 900px) {
    .rules-layout {
        flex-direction: column;
    }

    .hero-imageee img {
        max-height: 260px;
    }
}


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
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_teacher.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <a href="teacher_dashboard.php" style="display: inline-block; background: #5A0E24; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px;">← Back to Dashboard</a>

    <!-- ✅ WRAPPER ADDED -->
    <div class="rules-layout">

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

        <div class="hero-imageee">
            <img src="images/quiz_image6.png" alt="Teacher creating a quiz">
        </div>

    </div>
</div>


<script>
function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p && !p.contains(e.target))
        document.getElementById('profileDropdown').style.display='none';
});
</script>

</body>
</html>
