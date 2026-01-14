<?php
session_start();

/* ROLE PROTECTION */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* DATABASE */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$admin_id = $_SESSION['user_id'];

/* FETCH ADMIN INFO */
$res = mysqli_query(
    $conn,
    "SELECT name, username, profile_pic FROM Admins WHERE id = $admin_id"
);
$admin = mysqli_fetch_assoc($res);

$admin_name  = $admin['name'];
$profile_pic = $admin['profile_pic'];

/* UPDATE PROFILE */
if (isset($_POST['update_profile'])) {

    $name     = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $picPath  = $profile_pic;

    /* IMAGE UPLOAD */
    if (!empty($_FILES['profile_pic']['name'])) {

        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG files allowed";
        } else {

            if (!is_dir("uploads/admins")) {
                mkdir("uploads/admins", 0777, true);
            }

            $newName = "admin_" . $admin_id . "_" . time() . "." . $ext;
            $uploadPath = "uploads/admins/" . $newName;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadPath)) {
                $picPath = $uploadPath;
            } else {
                $error = "Image upload failed";
            }
        }
    }

    /* UPDATE DATABASE */
    if (!isset($error)) {

        mysqli_query(
            $conn,
            "UPDATE Admins 
             SET name='$name', username='$username', profile_pic='$picPath'
             WHERE id=$admin_id"
        );

        /* UPDATE SESSION (CRITICAL) */
        $_SESSION['user_name'] = $name;
        $_SESSION['admin_profile_pic'] = $picPath;

        /* SUCCESS MESSAGE */
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Profile updated successfully'
        ];

        header("Location: profile_admin.php");
        exit();
    }
}

/* FINAL IMAGE SOURCE (AFTER UPDATE) */
$imgSrc = !empty($_SESSION['admin_profile_pic'])
    ? htmlspecialchars($_SESSION['admin_profile_pic']) . '?t=' . time()
    : ($profile_pic
        ? htmlspecialchars($profile_pic) . '?t=' . time()
        : 'https://via.placeholder.com/85');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

/* TOP BAR */
.topbar{
    position:fixed;top:0;left:0;width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;padding:0 20px;z-index:1001;
}
.topbar i{font-size:24px;cursor:pointer;}

.top-profile{
    margin-left:auto;display:flex;align-items:center;gap:8px;
    cursor:pointer;position:relative;
}
.top-profile img{
    width:36px;height:36px;border-radius:50%;
    object-fit:cover;border:2px solid #5d9415;
}
.top-profile span{font-size:14px;font-weight:500;}

.profile-dropdown{
    display:none;position:absolute;right:0;top:55px;
    background:white;border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,.15);
    min-width:180px;z-index:3000;
}
.profile-dropdown a{
    display:flex;align-items:center;gap:10px;
    padding:12px 15px;text-decoration:none;color:#333;
}
.profile-dropdown a:hover{background:#f2f2f2;}

/* SIDEBAR */
.sidebar{
    width:260px;background:#5A0E24;color:white;
    position:fixed;top:60px;left:0;
    height:calc(100vh - 60px);
    display:flex;flex-direction:column;
    transition:.3s ease;z-index:1000;
}
.sidebar.collapsed{transform:translateX(-100%);}
.sidebar.no-transition{transition:none!important;}

.sidebar-profile{
    text-align:center;padding:25px;border-bottom:1px solid rgba(255,255,255,.15);
}
.sidebar-profile img{
    width:85px;height:85px;border-radius:50%;
    object-fit:cover;border:3px solid #5d9415;
}
.sidebar-profile h3{margin-top:10px;font-size:16px;}

.sidebar a{
    padding:15px 25px;text-decoration:none;color:#d1d1d1;
    display:flex;align-items:center;
}
.sidebar a i{margin-right:15px;width:20px;}
.sidebar a:hover,.sidebar a.active{background:#861434;color:white;}

/* MAIN */
.main-content{
    margin-left:260px;padding:90px 40px 40px;
    transition:.3s ease;
}
.main-content.full{margin-left:0;}

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
    width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;
}
.btn{
    background:#5d9415;color:white;padding:10px 18px;
    border:none;border-radius:6px;font-weight:bold;cursor:pointer;
}
.alert-error{color:red;font-weight:bold;margin-top:10px;}
.alert-success{
    background:#d4edda;color:#155724;
    padding:12px 18px;border-radius:8px;
    margin-bottom:15px;font-weight:bold;
}
</style>
</head>

<body>

<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>

    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($admin_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_admin.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($admin_name) ?></h3>
    </div>

    <a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
    <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</a>
    <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Manage Students</a>
</div>

<div class="main-content full" id="mainContent">

<?php if(isset($_SESSION['message'])): ?>
    <div class="alert-success"><?= $_SESSION['message']['text'] ?></div>
<?php unset($_SESSION['message']); endif; ?>

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
            <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required>
        </div>

        <button class="btn" name="update_profile">Update Profile</button>
    </form>

    <?php if(isset($error)) echo "<div class='alert-error'>$error</div>"; ?>
</div>

</div>

<script>
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');
const main=document.getElementById('mainContent');

window.addEventListener('DOMContentLoaded',()=>{
    if(sessionStorage.getItem('sidebar')==='open'){
        sidebar.classList.remove('collapsed');
        main.classList.remove('full');
    }
    setTimeout(()=>sidebar.classList.remove('no-transition'),50);
});

menuToggle.onclick=()=>{
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('full');
    sessionStorage.setItem('sidebar',
        sidebar.classList.contains('collapsed')?'closed':'open'
    );
};

function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p && !p.contains(e.target)){
        document.getElementById('profileDropdown').style.display='none';
    }
});
</script>

</body>
</html>
