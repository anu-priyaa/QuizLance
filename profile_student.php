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
$res = mysqli_query(
    $conn,
    "SELECT name, username, profile_pic
     FROM Students WHERE id = $student_id"
);
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* UPDATE PROFILE */
if (isset($_POST['update_profile'])) {

    $name     = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $picPath  = $profile_pic;

    if (!empty($_FILES['profile_pic']['name'])) {

        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG allowed";
        } else {

            if (!is_dir("uploads/students")) {
                mkdir("uploads/students", 0777, true);
            }

            $newName = "student_" . $student_id . "_" . time() . "." . $ext;
            $uploadPath = "uploads/students/" . $newName;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadPath)) {
                $picPath = $uploadPath;
            } else {
                $error = "Image upload failed!";
            }
        }
    }

    if (!isset($error)) {
        mysqli_query(
            $conn,
            "UPDATE Students
             SET name='$name', username='$username', profile_pic='$picPath'
             WHERE id=$student_id"
        );

        $_SESSION['user_name'] = $name;
        header("Location: profile_student.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile | QuizLance</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

/* ===== TOP BAR ===== */
.topbar{
    position:fixed;top:0;left:0;width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;
    padding:0 20px;z-index:1001;
}
.topbar i{font-size:24px;cursor:pointer;}

/* ===== TOP PROFILE ===== */
.top-profile{
    margin-left:auto;display:flex;align-items:center;
    gap:8px;cursor:pointer;position:relative;
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

/* ===== SIDEBAR ===== */
.sidebar{
    width:260px;background:#5A0E24;color:white;
    display:flex;flex-direction:column;
    position:fixed;top:60px;left:0;
    height:calc(100vh - 60px);
    transition:0.3s ease;z-index:1000;
}
.sidebar.collapsed{transform:translateX(-100%);}
.sidebar.no-transition{transition:none!important;}

.sidebar-profile{
    text-align:center;padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    cursor:pointer;
}
.sidebar-profile img{
    width:85px;height:85px;border-radius:50%;
    object-fit:cover;border:3px solid #5d9415;
}
.sidebar-profile h3{margin-top:10px;font-size:16px;}

.sidebar a{
    padding:15px 25px;text-decoration:none;
    color:#d1d1d1;display:flex;align-items:center;
}
.sidebar a i{margin-right:15px;width:20px;}
.sidebar a:hover,.sidebar a.active{
    background:#861434;color:white;
}

/* ===== MAIN ===== */
.main-content{
    margin-left:260px;padding:90px 40px 40px;
    transition:0.3s ease;
}
.main-content.full{margin-left:0;}

/* ===== PROFILE CARD ===== */
.card{
    background:white;padding:30px;border-radius:15px;
    max-width:600px;border-left:5px solid #5d9415;
}
.profile-pic-large{
    width:120px;height:120px;border-radius:50%;
    object-fit:cover;border:4px solid #5d9415;
    margin-bottom:15px;
}
.form-group{margin-bottom:15px;}
.form-group label{font-weight:bold;}
.form-group input{
    width:100%;padding:10px;
    border-radius:6px;border:1px solid #ccc;
}
.btn{
    background:#5d9415;color:white;
    padding:10px 18px;border:none;
    border-radius:6px;font-weight:bold;
    cursor:pointer;
}
.alert-error{color:red;font-weight:bold;margin-top:10px;}

/* ===== PROFILE POPUP ===== */
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
<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile" onclick="openProfilePopup()">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes_student.php"><i class="fas fa-chalkboard"></i> My Classes</a>
    <a href="view_result_student.php"><i class="fas fa-chart-line"></i> Results</a>
    <a href="leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
</div>

<!-- MAIN -->
<div class="main-content full" id="mainContent">
    <div class="card">
        <h2>My Profile</h2>

        <img src="<?= $imgSrc ?>" class="profile-pic-large">

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Profile Picture</label>
                <input type="file" name="profile_pic">
            </div>

            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($student['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username"
                       value="<?= htmlspecialchars($student['username']) ?>" required>
            </div>

            <button class="btn" name="update_profile">Update Profile</button>
        </form>

        <?php if (isset($error)) echo "<div class='alert-error'>$error</div>"; ?>
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
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const mainContent=document.getElementById('mainContent');

window.addEventListener('DOMContentLoaded',()=>{
    const state=sessionStorage.getItem('sidebar');
    if(state==='open'){
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }
    setTimeout(()=>sidebar.classList.remove('no-transition'),50);
});

menuToggle.onclick=()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');
    sessionStorage.setItem(
        'sidebar',
        sidebar.classList.contains('collapsed')?'closed':'open'
    );
};

function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p && !p.contains(e.target))
        document.getElementById('profileDropdown').style.display='none';
});
function openProfilePopup(){document.getElementById('profilePopup').style.display='flex';}
function closeProfilePopup(){document.getElementById('profilePopup').style.display='none';}
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
