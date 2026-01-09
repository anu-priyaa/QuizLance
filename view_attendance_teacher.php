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

/* FETCH TEACHER INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH QUIZZES */
$quiz_res = mysqli_query(
    $conn,
    "SELECT id, title FROM quizzes WHERE teacher_id=$teacher_id"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuizLance - Attendance</title>
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
    flex:1;
    padding:40px;
}

/* ===== PAGE CARD (same as welcome-card style) ===== */
.page-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
    max-width:500px;
}

.page-card h1 {
    color:#5A0E24;
    margin-bottom:10px;
}

.page-card p {
    margin-bottom:25px;
    color:#555;
}

/* FORM */
select, button {
    width:100%;
    padding:12px;
    border-radius:6px;
    border:1px solid #ccc;
    margin-bottom:15px;
}

button {
    background:#5d9415;
    color:white;
    position:relative;
    border:none;
    width:auto;
    font-weight:bold;
    cursor:pointer;
}
button:hover {
    background:#4e7d12;
    transform: translateY(-2px);
}
</style>
</head>

<body>

<!-- ===== SIDEBAR ===== -->
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
    <a href="view_attendance_teacher.php" class="active">
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

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

    <div class="page-card">
        <h1>Quiz Attendance</h1>
        <p>Download attendance list of students who attempted the quiz.</p>

        <form method="GET" action="download_attendance.php">
            <label><strong>Select Quiz</strong></label>
            <select name="quiz_id" required>
                <option value="">-- Select Quiz --</option>
                <?php while ($q = mysqli_fetch_assoc($quiz_res)): ?>
                    <option value="<?= $q['id'] ?>">
                        <?= htmlspecialchars($q['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">
                Download Attendance
            </button>
        </form>
    </div>

</div>

</body>
</html>
