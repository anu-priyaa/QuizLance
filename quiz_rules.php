<?php
session_start();

/* ROLE PROTECTION */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

/* DATABASE */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];

/* FETCH TEACHER INFO */
$res = mysqli_query(
    $conn,
    "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id"
);
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* SAVE QUIZ RULES */
if (isset($_POST['save_rules'])) {

    $rules_text = trim($_POST['quiz_rules']);

    if ($rules_text === '') {
        $error = "Please enter quiz rules before continuing.";
    } else {
        $_SESSION['quiz_rules'] = $rules_text;
        header("Location: create_quiz.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz Rules | QuizLance</title>

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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



.topbar i { font-size:24px; cursor:pointer; }

/* ===== MAIN CONTENT ===== */
.main-content {
    padding:70px 40px 40px;
}

/* ===== PAGE CARD ===== */
/* RULES PAGE LAYOUT */
.rules-layout {
    display: flex;
    gap: 40px;
    align-items: center;
    max-width: 1100px;
}

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

/* RESPONSIVE */
@media (max-width: 900px) {
    .rules-layout {
        flex-direction: column;
    }

    .hero-imageee img {
        max-height: 260px;
    }
}

.page-card h1 {
    color:#5A0E24;
    margin-bottom:10px;
}
.page-card p {
    margin-bottom:20px;
    color:#555;
}

/* FORM */
textarea {
    width:100%;
    min-height:220px;
    padding:15px;
    border-radius:8px;
    border:1px solid #ccc;
    resize:vertical;
}
button {
    background:#5d9415;
    color:white;
    padding:12px 20px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}
button:hover {
    background:#4e7d12;
    transform:translateY(-2px);
}

.alert {
    color:red;
    font-weight:bold;
    margin-top:10px;
}

/* PROFILE POPUP */
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

    <div class="rules-layout">

    <div class="page-card">
        <h1>Quiz Rules & Instructions</h1>
        <p>Enter the rules exactly as students should see before starting the quiz.</p>

        <form method="POST">
            <textarea name="quiz_rules"
             placeholder="Type quiz rules here..."><?= htmlspecialchars($_POST['quiz_rules'] ?? '') ?></textarea>

            <br><br>
            <button type="submit" name="save_rules">
                Next → Create Quiz
            </button>
        </form>

        <?php if(isset($error)): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>
    </div>

    <div class="hero-imageee">
        <img src="images/quiz_image5.png" alt="Teacher setting quiz rules">
    </div>

</div>


</div>

<!-- PROFILE POPUP -->
<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($teacher_name) ?></h2>
    </div>
</div>

<script>
function toggleProfileMenu() {
    const menu = document.getElementById('profileDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}
document.addEventListener('click', e => {
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

</body>
</html>
