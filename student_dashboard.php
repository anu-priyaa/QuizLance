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

$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id = $student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

    /* ===== FETCH PERFORMANCE DATA ===== */
$quiz_names = [];
$scores = [];

$performance_query = "
SELECT q.title, qa.total_marks, qa.submitted_at
FROM quiz_attempts qa
JOIN Quizzes q ON qa.quiz_id = q.id
WHERE qa.student_id = $student_id
AND qa.status = 'submitted'
ORDER BY qa.submitted_at ASC
";

$performance_result = mysqli_query($conn, $performance_query);

if ($performance_result) {
    while ($row = mysqli_fetch_assoc($performance_result)) {

    $date = date("d M Y", strtotime($row['submitted_at']));

    $quiz_names[] = $row['title'] . " (" . $date . ")";
    $scores[] = $row['total_marks'];
}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuizLance - Student Dashboard</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', sans-serif;
}
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

.topbar i {
    font-size:24px;
    cursor:pointer;
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

/* ===== SIDEBAR ===== */
.sidebar {
    width:260px;
    background:#5A0E24;
    color:white;
    display:flex;
    flex-direction:column;
    position:fixed;
    top:60px;
    left:0;
    height:calc(100vh - 60px);
    transition:0.3s ease;
    z-index:1000;
}

.sidebar.collapsed {
    transform:translateX(-100%);
}

/* PROFILE */
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
}

/* MENU */
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

/* ===== MAIN ===== */
.main-content {
    margin-left:260px;
    padding:90px 40px 40px;
    transition:0.3s ease;
}

.main-content.full {
    margin-left:0;
}

/* ===== WELCOME ===== */
.welcome-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    margin-bottom:30px;
    border-left:5px solid #5d9415;
}

.welcome-card h1 {
    color:#5A0E24;
}

/* ===== GRID ===== */
.dashboard-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px,1fr));
    gap:20px;
}

.menu-link { text-decoration:none; color:inherit; }

.menu-card {
    background:white;
    padding:30px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 2px 10px rgba(0,0,0,0.03);
    transition:0.3s;
}

.menu-card:hover {
    transform:translateY(-5px);
    box-shadow:0 10px 20px rgba(0,0,0,0.1);
}

.menu-card i {
    font-size:40px;
    color:#5A0E24;
    margin-bottom:15px;
}

/* ===== PROFILE POPUP ===== */
.profile-popup {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    z-index:2000;
    justify-content:center;
    align-items:center;
}

.profile-popup-content {
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    position:relative;
}

.profile-popup-content img {
    width:200px;
    height:200px;
    border-radius:50%;
    border:4px solid #5d9415;
    object-fit:cover;
}

.performance-section {
    margin: 40px;
    background: #ffffff;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.chart-container {
    width: 100%;
    height: 400px;
}


.close-btn {
    position:absolute;
    top:10px;
    right:14px;
    font-size:22px;
    cursor:pointer;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>

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
<div class="sidebar collapsed" id="sidebar">

    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php" class="active">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="my_classes_student.php">
        <i class="fas fa-chalkboard"></i> My Classes
    </a>
    <a href="view_result_student.php">
        <i class="fas fa-chart-line"></i> Results
    </a>
    <a href="leaderboard.php">
        <i class="fas fa-trophy"></i> Leaderboard
    </a>
</div>

<!-- MAIN -->
<div class="main-content full" id="mainContent">
    <div class="welcome-card">
        <h1>Welcome to QuizLance, <?= htmlspecialchars($student_name) ?>!</h1>
        <p>Attempt quizzes, track progress, and compete with confidence.</p>
    </div>

    <div class="dashboard-grid">

    <a href="sample_quizzes_student.php" class="menu-link">
    <div class="menu-card">
        <i class="fas fa-book-open"></i>
        <h3>Sample Quizzes</h3>
    </div>
</a>


    <a href="student_doubts.php" class="menu-link">
            <div class="menu-card">
                <i class="fas fa-play-circle"></i>
                <h3>Doubts</h3>
            </div>
        </a>
        <a href="attempt_live_quizzes.php" class="menu-link">
            <div class="menu-card">
                <i class="fas fa-play-circle"></i>
                <h3>Live Quiz</h3>
            </div>
        </a>

        <a href="scheduled_quizzes_student.php" class="menu-link">
            <div class="menu-card">
                <i class="fas fa-clock"></i>
                <h3>Scheduled Quizzes</h3>
            </div>
        </a>

        <a href="certificates.php" class="menu-link">
            <div class="menu-card">
                <i class="fas fa-award"></i>
                <h3>Certificates</h3>
            </div>
        </a>

        <a href="student_announcements.php" class="menu-link">
            <div class="menu-card">
                <i class="fas fa-award"></i>
                <h3>Announcements</h3>
            </div>
        </a>

</div>
<div class="performance-section">
    <h2>Overall Performance</h2>
    <div class="chart-container">
        <canvas id="performanceChart"></canvas>
    </div>


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
const menuToggle  = document.getElementById('menuToggle');
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
};

function toggleProfileMenu() {
    const menu = document.getElementById('profileDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e) {
    const profile = document.querySelector('.top-profile');
    if (profile && !profile.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});

function openProfilePopup() {
    document.getElementById('profilePopup').style.display = 'flex';
}

function closeProfilePopup() {
    document.getElementById('profilePopup').style.display = 'none';
}
</script>

<?php include 'includes/auto_logout.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const quizNames = <?php echo json_encode($quiz_names); ?>;
const scores = <?php echo json_encode($scores); ?>;

const ctx = document.getElementById('performanceChart').getContext('2d');

if (quizNames.length > 0) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: quizNames,
            datasets: [{
                label: 'Marks',
                data: scores,
                borderColor: '#7a0f22',
                backgroundColor: 'rgba(122,15,34,0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
} else {
    ctx.canvas.parentNode.innerHTML =
        "<p style='text-align:center;color:gray;'>No quiz attempts yet.</p>";
}
</script>


</body>
</html>