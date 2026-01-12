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

/* TEACHER INFO (for sidebar) */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id = $teacher_id");
$teacher = mysqli_fetch_assoc($res);
$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH QUIZZES */
$quiz_res = mysqli_query(
    $conn,
    "SELECT id, title FROM Quizzes WHERE teacher_id = $teacher_id"
);

$selected_quiz = $_GET['quiz_id'] ?? null;
$results = null;
$stats = null;

if ($selected_quiz) {
    $results = mysqli_query(
        $conn,
        "SELECT s.name, r.score, r.total_marks
         FROM Results r
         JOIN Students s ON r.student_id = s.id
         WHERE r.quiz_id = $selected_quiz"
    );

    $stats = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT 
            COUNT(*) AS total_students,
            AVG(score) AS avg_score,
            MAX(score) AS max_score,
            MIN(score) AS min_score
         FROM Results
         WHERE quiz_id = $selected_quiz"
    ));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>QuizLance - Results & Analytics</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { display:flex; background:#f0f2f5; min-height:100vh; }

/* ===== SIDEBAR (SAME AS DASHBOARD) ===== */
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
    cursor:pointer;
}

.sidebar-profile img {
    width:85px;
    height:85px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #5d9415;
}

.sidebar-profile h3 {
    margin-top:10px;
    font-size:16px;
    font-weight:bold;
}

.sidebar a {
    padding:15px 25px;
    text-decoration:none;
    color:#d1d1d1;
    display:flex;
    align-items:center;
}

.sidebar a i {
    margin-right:15px;
    width:20px;
    text-align:center;
}

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
    padding:40px;
    flex:1;
}

h1 {
    color:#5A0E24;
    margin-bottom:20px;
}

/* QUIZ SELECT */
.quiz-select {
    margin-bottom:20px;
}

.quiz-select select {
    padding:10px;
    border-radius:6px;
    border:1px solid #ccc;
    min-width:250px;
}

/* ANALYTICS */
.analytics {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.analytics-card {
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
    text-align:center;
}

/* TABLE */
table {
    width:100%;
    background:white;
    border-collapse:collapse;
    border-radius:12px;
    overflow:hidden;
}

th, td {
    padding:15px;
    border-bottom:1px solid #eee;
    text-align:left;
}

th {
    background:#5A0E24;
    color:white;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-profile">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>

    <a href="teacher_dashboard.php">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="my_classes.php">
        <i class="fas fa-users"></i> My Classes
    </a>
    <a href="manage_classes.php"><i class="fas fa-users"></i> Manage Class</a>
    <a href="view_attendance_teacher.php">
        <i class="fas fa-clipboard-list"></i> Attendance
    </a>
    <a href="profile_teacher.php">
        <i class="fas fa-user-edit"></i> Profile
    </a>

    <div class="logout">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <h1>Results & Analytics</h1>

    <!-- QUIZ SELECT -->
    <form method="GET" class="quiz-select">
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

    <?php if ($selected_quiz && $stats && $stats['total_students'] > 0): ?>

    <!-- ANALYTICS -->
    <div class="analytics">
        <div class="analytics-card">Total Students<br><strong><?= $stats['total_students'] ?></strong></div>
        <div class="analytics-card">Average Score<br><strong><?= round($stats['avg_score'],2) ?></strong></div>
        <div class="analytics-card">Highest Score<br><strong><?= $stats['max_score'] ?></strong></div>
        <div class="analytics-card">Lowest Score<br><strong><?= $stats['min_score'] ?></strong></div>
    </div>

    <!-- RESULTS TABLE -->
    <table>
        <tr>
            <th>Student Name</th>
            <th>Score</th>
            <th>Total Marks</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($results)) { ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= $row['score'] ?></td>
            <td><?= $row['total_marks'] ?></td>
        </tr>
        <?php } ?>
    </table>

    <?php elseif ($selected_quiz): ?>
        <p>No results available for this quiz.</p>
    <?php endif; ?>

</div>

</body>
</html>
