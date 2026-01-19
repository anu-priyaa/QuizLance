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
body { background:#f0f2f5; }

/* ===== TOP BAR ===== */
.topbar {
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:60px;
    background:#5A0E24;
    color:white;
    display:flex;
    align-items:center;
    padding:0 20px;
    z-index:1001;
}

/* ===== TOP PROFILE ===== */
.top-profile {
    margin-left:auto;
    display:flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    position:relative;
}
.top-profile img {
    width:36px;
    height:36px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #5d9415;
}
.top-profile span {
    font-size:14px;
    font-weight:500;
}

/* DROPDOWN */
.profile-dropdown {
    display:none;
    position:absolute;
    right:0;
    top:55px;
    background:white;
    border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
    min-width:180px;
    overflow:hidden;
    z-index:3000;
}
.profile-dropdown a {
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px 15px;
    text-decoration:none;
    color:#333;
    font-size:14px;
}
.profile-dropdown a:hover {
    background:#f2f2f2;
}

/* ===== MAIN CONTENT ===== */
.main-content {
    padding:70px 40px 40px;
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
    padding:15px;
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

<!-- TOP BAR -->
<div class="topbar">
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_teacher.php">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <a href="teacher_dashboard.php" style="display: inline-block; background: #5A0E24; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px;">← Back to Dashboard</a>

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

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
