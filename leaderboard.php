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

/* FETCH STUDENT INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* QUIZ LIST (Quizzes the student has submitted) */
$quiz_res = mysqli_query($conn, "
    SELECT DISTINCT q.id, q.title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.student_id = $student_id
    AND qa.status='submitted'
");

$selected_quiz = $_GET['quiz_id'] ?? null;
$leaderboard = null;
$totalMarks = 0;

if ($selected_quiz) {
    /* TOTAL MARKS FOR THE QUIZ */
    $totalQ = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(marks) AS total FROM questions WHERE quiz_id=$selected_quiz"
    ));
    $totalMarks = $totalQ['total'] ?? 0;

    /* LEADERBOARD DATA */
    $leaderboard = mysqli_query($conn, "
        SELECT s.name, qa.total_marks
        FROM quiz_attempts qa
        JOIN Students s ON qa.student_id = s.id
        WHERE qa.quiz_id = $selected_quiz
        AND qa.status='submitted'
        ORDER BY qa.total_marks DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { background:#f0f2f5; }

/* ===== TOP BAR ===== */
.topbar {
    position:fixed; top:0; left:0; width:100%; height:60px;
    background:#5A0E24; color:white;
    display:flex; align-items:center; padding:0 20px; z-index:1001;
}
.topbar i { font-size:24px; cursor:pointer; }

.top-profile {
    margin-left:auto; display:flex; align-items:center; gap:8px;
    cursor:pointer; position:relative;
}
.top-profile img {
    width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #5d9415;
}

/* DROPDOWN */
.profile-dropdown {
    display:none; position:absolute; right:0; top:55px;
    background:white; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.15);
    min-width:180px; overflow:hidden; z-index:3000;
}
.profile-dropdown a {
    display:flex; align-items:center; gap:10px; padding:12px 15px;
    text-decoration:none; color:#333; font-size:14px;
}
.profile-dropdown a:hover { background:#f2f2f2; }

/* ===== SIDEBAR ===== */
.sidebar {
    width:260px; background:#5A0E24; color:white;
    display:flex; flex-direction:column; position:fixed;
    top:60px; left:0; height:calc(100vh - 60px);
    transition:0.3s ease; z-index:1000;
}
.sidebar.collapsed { transform:translateX(-100%); }
.sidebar.no-transition { transition:none !important; }

.sidebar-profile {
    text-align:center; padding:25px 15px; border-bottom:1px solid rgba(255,255,255,0.15);
}
.sidebar-profile img {
    width:85px; height:85px; border-radius:50%; object-fit:cover; border:3px solid #5d9415;
}

.sidebar a {
    padding:15px 25px; text-decoration:none; color:#d1d1d1;
    display:flex; align-items:center;
}
.sidebar a i { margin-right:15px; width:20px; }
.sidebar a:hover, .sidebar a.active { background:#861434; color:white; }

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left:260px; padding:90px 40px 40px; transition:0.3s ease;
}
.main-content.full { margin-left:0; }

.card {
    background:white; padding:30px; border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

.page-title { color:#5A0E24; margin-bottom:20px; }

/* FORM STYLING */
select {
    padding:10px; border-radius:6px; border:1px solid #ccc;
    width:100%; max-width:400px; margin-bottom:25px; outline:none;
}

/* TABLE STYLING */
table { width:100%; border-collapse:collapse; margin-top:10px; overflow:hidden; border-radius:8px; }
th { background:#5A0E24; color:white; padding:15px; text-align:left; }
td { padding:15px; border-bottom:1px solid #eee; color: #444; }

/* RANK STYLES */
.gold { background:#fffdf2; }
.silver { background:#f9f9f9; }
.bronze { background:#fffaf5; }
.you { background:#e8f5e9 !important; font-weight:bold; border-left: 5px solid #5d9415; }

.rank-icon { font-size: 1.2rem; }

/* POPUP */
.profile-popup {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
    z-index:2000; justify-content:center; align-items:center;
}
.profile-popup-content {
    background:white; padding:30px; border-radius:15px; text-align:center; position:relative;
}
.profile-popup-content img { width:200px; height:200px; border-radius:50%; border:4px solid #5d9415; object-fit:cover; }
.close-btn { position:absolute; top:10px; right:14px; font-size:22px; cursor:pointer; }
</style>
</head>

<body>

<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($student_name) ?></span>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_student.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3 style="margin-top:10px; font-size:16px;"><?= htmlspecialchars($student_name) ?></h3>
    </div>
    <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes_student.php"><i class="fas fa-chalkboard"></i> My Classes</a>
    <a href="view_result_student.php"><i class="fas fa-chart-line"></i> Results</a>
    <a href="leaderboard.php" class="active"><i class="fas fa-trophy"></i> Leaderboard</a>
</div>

<div class="main-content full" id="mainContent">
    <h2 class="page-title">Leaderboard</h2>

    <div class="card">
        <form method="GET">
            <label style="display:block; margin-bottom:8px; font-weight:500;">Select Quiz to View Rankings:</label>
            <select name="quiz_id" onchange="this.form.submit()">
                <option value="">-- Choose a Quiz --</option>
                <?php while($q = mysqli_fetch_assoc($quiz_res)): ?>
                    <option value="<?= $q['id'] ?>" <?= ($selected_quiz == $q['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($q['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>

        <?php if($leaderboard): ?>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>Score</th>
                        <th>Total Marks</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $rank = 1;
                while($row = mysqli_fetch_assoc($leaderboard)):
                    $class = "";
                    if($rank == 1) $class = "gold";
                    elseif($rank == 2) $class = "silver";
                    elseif($rank == 3) $class = "bronze";

                    if($row['name'] == $student_name) $class .= " you";
                ?>
                    <tr class="<?= $class ?>">
                        <td>
                            <span class="rank-icon">
                                <?php 
                                    if($rank == 1) echo "🥇";
                                    elseif($rank == 2) echo "🥈";
                                    elseif($rank == 3) echo "🥉";
                                    else echo $rank;
                                ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= $row['total_marks'] ?></td>
                        <td><?= $totalMarks ?></td>
                    </tr>
                <?php $rank++; endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:#666; text-align:center; padding:20px;">Please select a quiz to see the leaderboard rankings.</p>
        <?php endif; ?>
    </div>
</div>

<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2 style="margin-top:15px;"><?= htmlspecialchars($student_name) ?></h2>
    </div>
</div>

<script>
const menuToggle  = document.getElementById('menuToggle');
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

/* restore sidebar state */
window.addEventListener('DOMContentLoaded', () => {
    const state = sessionStorage.getItem('sidebar');
    if (state === 'open') {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }
    setTimeout(() => sidebar.classList.remove('no-transition'), 50);
});

/* toggle sidebar */
menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
    sessionStorage.setItem('sidebar', sidebar.classList.contains('collapsed') ? 'closed' : 'open');
};

/* dropdown toggle */
function toggleProfileMenu() {
    const menu = document.getElementById('profileDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

/* close dropdown on outside click */
document.addEventListener('click', function(e) {
    const profile = document.querySelector('.top-profile');
    if (profile && !profile.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});

/* profile popup functions */
function openProfilePopup() { document.getElementById('profilePopup').style.display = 'flex'; }
function closeProfilePopup() { document.getElementById('profilePopup').style.display = 'none'; }
</script>

</body>
</html>