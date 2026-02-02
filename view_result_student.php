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

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH QUIZZES ATTEMPTED */
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Results | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

/* ===== TOP BAR ===== */
.topbar{
    position:fixed;top:0;left:0;width:100%;height:60px;
    background:#5A0E24;color:white;display:flex;align-items:center;
    padding:0 20px;z-index:1001;
}
.topbar i{font-size:24px;cursor:pointer;}

/* ===== TOP PROFILE ===== */
.top-profile{
    margin-left:auto;display:flex;align-items:center;gap:8px;
    cursor:pointer;position:relative;
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

/* ===== TOPBAR HAMBURGER ===== */
.hamburger {
    font-size: 24px;
    cursor: pointer;
    color: white;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 260px;
    background: #5A0E24;
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 60px;
    left: 0;
    height: calc(100vh - 60px);
    transition: 0.3s ease;
    z-index: 1000;
}

.sidebar.collapsed {
    transform: translateX(-100%);
}

.sidebar-profile {
    text-align: center;
    padding: 25px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    cursor: pointer;
}

.sidebar-profile img {
    width: 85px;
    height: 85px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #5d9415;
}

.sidebar-profile h3 {
    margin-top: 10px;
    font-size: 16px;
}

.sidebar a {
    padding: 15px 25px;
    text-decoration: none;
    color: #d1d1d1;
    display: flex;
    align-items: center;
}

.sidebar a i {
    margin-right: 15px;
    width: 20px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #861434;
    color: white;
}

/* ===== MAIN ===== */
.main-content{
    margin-left: 260px;
    padding: 70px 40px 40px;
    transition: 0.3s ease;
}

.main-content.full {
    margin-left: 0;
}

.page-title{color:#5A0E24;margin-bottom:20px;}

.card{
    background:white;padding:20px;border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

/* RESULT */
select{padding:12px;border-radius:6px;border:1px solid #ccc;}
.result-box{
    margin-top:30px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:20px;
}
.result-card{
    background:#f9f9f9;padding:20px;
    border-radius:12px;text-align:center;
    border-left:5px solid #5d9415;
}
.result-card h3{color:#5A0E24;}

/* PROFILE POPUP */
.profile-popup{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,0.5);
    z-index:2000;justify-content:center;align-items:center;
}
.profile-popup-content{
    background:white;padding:30px;border-radius:15px;
    text-align:center;position:relative;
}
.profile-popup-content img{
    width:200px;height:200px;border-radius:50%;
    border:4px solid #5d9415;object-fit:cover;
}
.close-btn{
    position:absolute;top:10px;right:14px;
    font-size:22px;cursor:pointer;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars hamburger" id="menuToggle"></i>
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($student_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_student.php">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>
    <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes_student.php"><i class="fas fa-chalkboard"></i> My Classes</a>
    <a href="view_result_student.php" class="active"><i class="fas fa-chart-line"></i> Results</a>
    <a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
</div>

<!-- MAIN -->
<div class="main-content" id="mainContent">
    <a href="student_dashboard.php" style="display: inline-block; background: #5A0E24; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px;">← Back to Dashboard</a>
    <h1 class="page-title">My Quiz Results</h1>

    <div class="card">
        <form method="GET">
            <label><strong>Select Quiz</strong></label><br><br>
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
            <div class="result-card"><h3>Score</h3><p><?= $result['score'] ?></p></div>
            <div class="result-card"><h3>Total</h3><p><?= $result['total_marks'] ?></p></div>
            <div class="result-card"><h3>Status</h3>
                <p><?= ($result['score'] >= ($result['total_marks']/2)) ? 'Pass' : 'Fail' ?></p>
            </div>
            <div class="result-card"><h3>Submitted</h3>
                <p><?= date("d M Y, h:i A", strtotime($result['submitted_at'])) ?></p>
            </div>
        </div>
        <?php elseif ($selected_quiz): ?>
            <p style="margin-top:20px;">Result not available.</p>
        <?php endif; ?>
    </div>
</div>

<!-- PROFILE POPUP -->
<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($student_name) ?></h2>
    </div>
</div>

<script>
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
};

function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p && !p.contains(e.target))document.getElementById('profileDropdown').style.display='none';
});
function openProfilePopup(){document.getElementById('profilePopup').style.display='flex';}
function closeProfilePopup(){document.getElementById('profilePopup').style.display='none';}
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
