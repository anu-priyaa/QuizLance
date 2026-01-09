<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];

/* STUDENT INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);
$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

/* FETCH QUIZZES ATTEMPTED BY STUDENT */
$quiz_res = mysqli_query($conn,
    "SELECT q.id, q.title
     FROM Results r
     JOIN quizzes q ON r.quiz_id = q.id
     WHERE r.student_id = $student_id"
);

$selected_quiz = $_GET['quiz_id'] ?? null;
$result = null;

if ($selected_quiz) {
    $result = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT r.score, r.total_marks, r.submitted_at
         FROM Results r
         WHERE r.quiz_id = $selected_quiz AND r.student_id = $student_id"
    ));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Results | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { display:flex; background:#f0f2f5; min-height:100vh; }

/* SIDEBAR */
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

/* MAIN CONTENT */
.main-content {
    margin-left:260px;
    padding:40px;
    flex:1;
}

h1 { color:#5A0E24; margin-bottom:20px; }

/* CARD */
.card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

/* SELECT */
select {
    padding:10px;
    border-radius:6px;
    border:1px solid #ccc;
    min-width:260px;
}

/* RESULT BOX */
.result-box {
    margin-top:30px;
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px,1fr));
    gap:20px;
}

.result-card {
    background:#f9f9f9;
    padding:20px;
    border-radius:12px;
    text-align:center;
    border-left:5px solid #5d9415;
}

.result-card h3 { color:#5A0E24; }
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-profile">
        <img src="<?= $profile_pic ?: 'https://via.placeholder.com/85' ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="join_class.php">
        <i class="fas fa-users"></i> Join Class
    </a>
    <a href="my_classes_student.php">
        <i class="fas fa-chalkboard"></i> My Classes
    </a>
    <a href="view_result_student.php" class="active">
        <i class="fas fa-chart-line"></i> Results
    </a>
    <a href="leaderboard.php">
        <i class="fas fa-trophy"></i> Leaderboard
    </a>
    <a href="profile_student.php">
        <i class="fas fa-user-edit"></i> Profile
    </a>

    <div class="logout">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <h1>My Quiz Results</h1>

    <div class="card">

        <form method="GET">
            <label>Select Quiz:</label><br><br>
            <select name="quiz_id" onchange="this.form.submit()" required>
                <option value="">-- Select Quiz --</option>
                <?php while ($q = mysqli_fetch_assoc($quiz_res)) { ?>
                    <option value="<?= $q['id'] ?>" <?= ($selected_quiz == $q['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($q['title']) ?>
                    </option>
                <?php } ?>
            </select>
        </form>

        <?php if ($result): ?>
        <div class="result-box">
            <div class="result-card">
                <h3>Score</h3>
                <p><?= $result['score'] ?></p>
            </div>
            <div class="result-card">
                <h3>Total Marks</h3>
                <p><?= $result['total_marks'] ?></p>
            </div>
            <div class="result-card">
                <h3>Status</h3>
                <p><?= ($result['score'] >= ($result['total_marks']/2)) ? 'Pass' : 'Fail' ?></p>
            </div>
            <div class="result-card">
                <h3>Submitted On</h3>
                <p><?= date("d M Y, h:i A", strtotime($result['submitted_at'])) ?></p>
            </div>
        </div>
        <?php elseif ($selected_quiz): ?>
            <p style="margin-top:20px;">Result not available.</p>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
