<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

/* ROLE PROTECTION */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* DATABASE */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
mysqli_set_charset($conn, "utf8mb4");
if (!$conn) die("Database connection failed");

/* ADMIN INFO */
$admin_name = $_SESSION['user_name'] ?? 'Admin';

/* PROFILE PIC */
$imgSrc = !empty($_SESSION['admin_profile_pic'])
    ? htmlspecialchars($_SESSION['admin_profile_pic']) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* CREATE TEACHER */
if (isset($_POST['create_teacher'])) {

    $name     = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    if ($name=='' || $username=='' || $email=='' || $password=='') {
        $_SESSION['message']=['type'=>'error','text'=>'All fields are required'];
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message']=['type'=>'error','text'=>'Invalid email address'];
    }
    elseif (strlen($password) < 8) {
        $_SESSION['message']=['type'=>'error','text'=>'Password must be at least 8 characters'];
    }
    else {
        $check = mysqli_query(
            $conn,
            "SELECT id FROM Teachers WHERE email='$email' OR username='$username'"
        );

        if (mysqli_num_rows($check) > 0) {
            $_SESSION['message']=['type'=>'error','text'=>'Teacher already exists'];
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query($conn,
                "INSERT INTO Teachers(name,username,email,password,status)
                 VALUES('$name','$username','$email','$hash','active')"
            );

            /* SEND EMAIL */
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'anupriyaa245@gmail.com';
                $mail->Password = 'xnfplyshkejuehwh'; // app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('anupriyaa245@gmail.com', 'QuizLance Admin');
                $mail->addAddress($email, $name);

                $mail->isHTML(true);
                $mail->Subject = 'Your QuizLance Teacher Account';
                $mail->Body = "
                    <h3>Hello $name,</h3>
                    <p>Your teacher account has been created.</p>
                    <p><b>Login Details:</b></p>
                    <ul>
                        <li>Email: $email</li>
                        <li>Username: $username</li>
                        <li>Password: $password</li>
                    </ul>
                    <p style='color:red'>Please change your password after first login.</p>
                    <a href='http://localhost/QuizLance/login.php'>Login to QuizLance</a>
                ";
                $mail->send();
            } catch (Exception $e) {}

            $_SESSION['message']=['type'=>'success','text'=>'Teacher created & email sent'];
            header("Location: manage_teachers.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Teacher | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* 🔥 EXACT SAME CSS AS manage_teachers.php */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

.topbar{position:fixed;top:0;left:0;width:100%;height:60px;background:#5A0E24;color:white;display:flex;align-items:center;padding:0 20px;z-index:1001;}
.topbar i{font-size:24px;cursor:pointer;}

.top-profile{margin-left:auto;display:flex;align-items:center;gap:8px;cursor:pointer;position:relative;}
.top-profile img{width:36px;height:36px;border-radius:50%;border:2px solid #5d9415;object-fit:cover;}
.top-profile span{font-size:14px;font-weight:500;}

.profile-dropdown{display:none;position:absolute;right:0;top:55px;background:white;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.15);min-width:180px;z-index:3000;}
.profile-dropdown a{display:flex;align-items:center;gap:10px;padding:12px 15px;text-decoration:none;color:#333;}
.profile-dropdown a:hover{background:#f2f2f2;}

.sidebar{width:260px;background:#5A0E24;color:white;position:fixed;top:60px;left:0;height:calc(100vh - 60px);display:flex;flex-direction:column;transition:.3s ease;z-index:1000;}
.sidebar.collapsed{transform:translateX(-100%);}
.sidebar.no-transition{transition:none!important;}
.sidebar-profile{text-align:center;padding:25px;border-bottom:1px solid rgba(255,255,255,.15);}
.sidebar-profile img{width:85px;height:85px;border-radius:50%;border:3px solid #5d9415;object-fit:cover;}
.sidebar a{padding:15px 25px;text-decoration:none;color:#d1d1d1;display:flex;align-items:center;}
.sidebar a i{margin-right:15px;width:20px;}
.sidebar a:hover,.sidebar a.active{background:#861434;color:white;}
.logout{margin-top:auto;border-top:1px solid rgba(255,255,255,.15);}

.main-content{margin-left:260px;padding:90px 40px 40px;transition:.3s ease;}
.main-content.full{margin-left:0;}

.form-card{background:white;padding:30px;border-radius:15px;max-width:500px;border-left:5px solid #5d9415;}
.form-group{margin-bottom:15px;}
.form-group label{font-weight:bold;}
.form-group input{width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;}
.btn{background:#5d9415;color:white;padding:10px 18px;border:none;border-radius:6px;font-weight:bold;cursor:pointer;}

.alert{position:fixed;top:80px;left:50%;transform:translateX(-50%);padding:15px 25px;border-radius:10px;font-weight:bold;}
.alert-success{background:#d4edda;color:#155724;}
.alert-error{background:#f8d7da;color:#721c24;}
</style>
</head>

<body>

<!-- TOPBAR -->
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

<!-- SIDEBAR -->
<div class="sidebar collapsed no-transition" id="sidebar">
    <div class="sidebar-profile">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($admin_name) ?></h3>
    </div>
    <a href="admin_dashboard.php"><i class="fas fa-chart-pie"></i> Overview</a>
    <a href="manage_teachers.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</a>
    <a href="manage_students.php"><i class="fas fa-user-graduate"></i> Manage Students</a>
    <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</div>

<!-- MAIN -->
<div class="main-content full" id="mainContent">

<?php if(isset($_SESSION['message'])): $m=$_SESSION['message']; ?>
<div class="alert alert-<?= $m['type'] ?>"><?= htmlspecialchars($m['text']) ?></div>
<?php unset($_SESSION['message']); endif; ?>

<div class="form-card">
    <h2>Create Teacher</h2>
    <form method="POST">
        <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
        <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <button class="btn" name="create_teacher">Create Teacher</button>
    </form>
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
    sessionStorage.setItem('sidebar',sidebar.classList.contains('collapsed')?'closed':'open');
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
setTimeout(()=>document.querySelector('.alert')?.remove(),3000);
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
