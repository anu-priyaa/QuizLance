<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

/* =========================
   FETCH ADMIN INFO
   ========================= */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Admins LIMIT 1");
$admin = mysqli_fetch_assoc($res);

$admin_name = $admin['name'] ?? 'Admin';
$profile_pic = $admin['profile_pic'] ?? null;

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* =========================
   ADD TEACHER MANUALLY
   ========================= */
if (isset($_POST['add_teacher'])) {

    $name  = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));

    if ($name === '' || $email === '') {
        $error = "All fields are required";
    } else {

        $check = mysqli_query($conn, "SELECT id FROM Teachers WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Teacher already exists";
        } else {

            $password = password_hash("teacher123", PASSWORD_DEFAULT);

            mysqli_query(
                $conn,
                "INSERT INTO Teachers (name, email, username, password)
                 VALUES ('$name', '$email', '$email', '$password')"
            );

            $success = "Teacher added successfully (default password: teacher123)";
        }
    }
}

/* =========================
   CSV UPLOAD (TEACHERS)
   ========================= */
if (isset($_POST['upload_csv'])) {

    if ($_FILES['csv_file']['error'] !== 0) {
        $error = "CSV upload failed";
    } else {

        $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
        $count = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {

            $name  = trim($data[0] ?? '');
            $email = trim($data[1] ?? '');

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $check = mysqli_query($conn, "SELECT id FROM Teachers WHERE email='$email'");
            if (mysqli_num_rows($check) > 0) continue;

            $password = password_hash("teacher123", PASSWORD_DEFAULT);

            mysqli_query(
                $conn,
                "INSERT INTO Teachers (name, email, username, password)
                 VALUES ('$name', '$email', '$email', '$password')"
            );
            $count++;
        }

        fclose($handle);
        $success = "$count teachers added successfully via CSV";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Teachers | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}
.topbar{position:fixed;top:0;left:0;width:100%;height:60px;background:#5A0E24;color:white;display:flex;align-items:center;padding:0 20px;}
.topbar i{font-size:24px;cursor:pointer;}
.sidebar{width:260px;background:#5A0E24;color:white;position:fixed;top:60px;left:0;height:calc(100vh - 60px);transition:.3s;}
.sidebar.collapsed{transform:translateX(-100%);}
.sidebar-profile{text-align:center;padding:25px;border-bottom:1px solid rgba(255,255,255,.15);}
.sidebar-profile img{width:85px;height:85px;border-radius:50%;border:3px solid #5d9415;object-fit:cover;}
.sidebar a{padding:15px 25px;color:#d1d1d1;text-decoration:none;display:flex;align-items:center;}
.sidebar a i{margin-right:15px;}
.sidebar a.active,.sidebar a:hover{background:#861434;color:white;}
.main-content{margin-left:260px;padding:90px 40px;}
.page-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:30px;}
.page-card{background:white;padding:30px;border-radius:15px;border-left:5px solid #5d9415;}
.page-card h1{color:#5A0E24;margin-bottom:10px;}
.page-card p{margin-bottom:25px;color:#555;}
input{width:100%;padding:12px;border-radius:6px;border:1px solid #ccc;margin-bottom:20px;}
button{background:#5d9415;color:white;padding:12px;border:none;border-radius:6px;font-weight:bold;cursor:pointer;}
button:hover{background:#4e7d12;}
.alert-success{color:green;font-weight:bold;margin-top:20px;}
.alert-error{color:red;font-weight:bold;margin-top:20px;}
</style>
</head>

<body>

<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>
</div>

<div class="sidebar collapsed" id="sidebar">
    <div class="sidebar-profile">
        <img src="<?= $imgSrc ?>">
        <h3><?= htmlspecialchars($admin_name) ?></h3>
    </div>

    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="manage_teachers.php">
        <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
    </a>
    <a href="manage_students.php">
        <i class="fas fa-user-graduate"></i> Manage Students
    </a>
    <a href="add_teacher.php" class="active"><i class="fas fa-user-plus"></i> Add Teachers</a>
</div>

<div class="main-content" id="mainContent">

<div class="page-grid">

<div class="page-card">
<h1>Add Teacher Manually</h1>
<p>Create a teacher account.</p>

<form method="POST">
<input type="text" name="name" placeholder="Teacher name" required>
<input type="email" name="email" placeholder="Teacher email" required>
<button name="add_teacher">Add Teacher</button>
</form>
</div>

<div class="page-card">
<h1>Upload Teachers via CSV</h1>
<p>CSV format: <b>Name, Email</b></p>

<form method="POST" enctype="multipart/form-data">
<input type="file" name="csv_file" accept=".csv" required>
<button name="upload_csv">Upload CSV</button>
</form>
</div>

</div>

<?php if(isset($success)) echo "<p class='alert-success'>$success</p>"; ?>
<?php if(isset($error)) echo "<p class='alert-error'>$error</p>"; ?>

</div>

<script>
const menuToggle=document.getElementById('menuToggle');
const sidebar=document.getElementById('sidebar');

menuToggle.onclick=()=>sidebar.classList.toggle('collapsed');
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
