<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];

/* FETCH STUDENT INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

/* FETCH SCHEDULED QUIZZES */
$quizzes = mysqli_query(
    $conn,
    "SELECT q.id, q.title, q.start_time, q.end_time, c.class_name
     FROM quizzes q
     JOIN Classes c ON q.class_id = c.id
     JOIN class_students cs ON cs.class_id = c.id
     WHERE cs.student_id = $student_id
     ORDER BY q.start_time ASC"
);

$now = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Scheduled Quizzes | QuizLance</title>
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

.sidebar-profile h3 {
    margin-top:10px;
    font-size:16px;
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

/* MAIN CONTENT */
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

h2 {
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

.btn {
    padding:6px 12px;
    border-radius:5px;
    font-weight:bold;
    font-size:14px;
}

.btn-live { background:#5d9415; color:white; }
.btn-upcoming { background:#999; color:white; }
.btn-expired { background:#ccc; color:#333; }
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-profile">
        <img src="<?= $profile_pic ?: 'https://via.placeholder.com/85' ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php" class="active">
        <i class="fas fa-home"></i> Dashboard
    </a>

    <a href="join_class.php"><i class="fas fa-users"></i> Join Class</a>
    <a href="my_classes_student.php"><i class="fas fa-chalkboard"></i> My Classes</a>
    <a href="results.php"><i class="fas fa-chart-line"></i> Results</a>
    <a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
    <a href="profile_student.php"><i class="fas fa-user-edit"></i> Profile</a>

    <div class="logout">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="card">
        <h2>Scheduled Quizzes</h2>

        <table>
            <tr>
                <th>Quiz Title</th>
                <th>Class</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Action</th>
            </tr>

            <?php while ($q = mysqli_fetch_assoc($quizzes)): 
                $start = strtotime($q['start_time']);
                $end   = strtotime($q['end_time']);
            ?>
            <tr>
                <td><?= htmlspecialchars($q['title']) ?></td>
                <td><?= htmlspecialchars($q['class_name']) ?></td>
                <td><?= date("d M Y, h:i A", $start) ?></td>
                <td><?= date("d M Y, h:i A", $end) ?></td>
                <td>
                    <?php if ($now >= $start && $now <= $end): ?>
                        <a href="attempt_quiz.php?quiz_id=<?= $q['id'] ?>" class="btn btn-live">
                            Attempt
                        </a>
                    <?php elseif ($now < $start): ?>
                        <span class="btn btn-upcoming">Upcoming</span>
                    <?php else: ?>
                        <span class="btn btn-expired">Expired</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

    </div>

</div>

</body>
</html>
