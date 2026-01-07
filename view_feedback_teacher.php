<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];

/* FETCH TEACHER INFO (SIDEBAR) */
$tres = mysqli_query($conn,
    "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id"
);
$teacher = mysqli_fetch_assoc($tres);

/* FETCH FEEDBACK */
$feedbacks = mysqli_query($conn,
    "SELECT f.feedback_text, f.rating, f.created_at,
            s.name AS student_name,
            q.title AS quiz_title
     FROM feedback f
     JOIN Students s ON f.student_id = s.id
     JOIN quizzes q ON f.quiz_id = q.id
     WHERE q.teacher_id = $teacher_id
     ORDER BY f.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Feedback | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { display:flex; background:#f0f2f5; min-height:100vh; }

/* ===== SIDEBAR (SAME AS TEACHER DASHBOARD) ===== */
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
    transition:0.3s;
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
    flex:1;
    padding:40px;
}

.card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

.card h2 {
    color:#5A0E24;
    margin-bottom:20px;
}

/* TABLE */
table {
    width:100%;
    border-collapse:collapse;
}

th, td {
    padding:14px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

th {
    background:#5A0E24;
    color:white;
}

.rating {
    color:#f4b400;
    font-weight:bold;
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

    <a href="teacher_dashboard.php">
        <i class="fas fa-home"></i> Dashboard
    </a>

    <a href="my_classes.php">
        <i class="fas fa-users"></i> My Classes
    </a>

    <a href="profile_teacher.php">
        <i class="fas fa-user-edit"></i> Profile
    </a>

    <a href="view_feedback.php" class="active">
        <i class="fas fa-comments"></i> View Feedback
    </a>

    <div class="logout">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="card">
        <h2>Student Feedback</h2>

        <table>
            <tr>
                <th>Quiz</th>
                <th>Student</th>
                <th>Rating</th>
                <th>Feedback</th>
                <th>Date</th>
            </tr>

            <?php while ($f = mysqli_fetch_assoc($feedbacks)): ?>
            <tr>
                <td><?= htmlspecialchars($f['quiz_title']) ?></td>
                <td><?= htmlspecialchars($f['student_name']) ?></td>
                <td class="rating"><?= $f['rating'] ?>/5</td>
                <td><?= htmlspecialchars($f['feedback_text']) ?></td>
                <td><?= date("d M Y", strtotime($f['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

    </div>

</div>

</body>
</html>
