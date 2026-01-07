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

/* FETCH STUDENT INFO FOR SIDEBAR */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

/* JOIN CLASS */
if (isset($_POST['join_class'])) {

    $class_code = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['class_code'])));

    if ($class_code === '') {
        $error = "Class code is required";
    } else {

        /* CHECK CLASS */
        $classRes = mysqli_query(
            $conn,
            "SELECT id FROM Classes WHERE class_code='$class_code'"
        );

        if (mysqli_num_rows($classRes) === 0) {
            $error = "Invalid class code";
        } else {
            $class = mysqli_fetch_assoc($classRes);
            $class_id = $class['id'];

            /* CHECK IF ALREADY JOINED */
            $check = mysqli_query(
                $conn,
                "SELECT id FROM class_students 
                 WHERE class_id=$class_id AND student_id=$student_id"
            );

            if (mysqli_num_rows($check) > 0) {
                $error = "You have already joined this class";
            } else {

                /* JOIN CLASS */
                mysqli_query(
                    $conn,
                    "INSERT INTO class_students (class_id, student_id)
                     VALUES ($class_id, $student_id)"
                );

                $success = "Successfully joined the class!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Join Class | QuizLance</title>
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
    border:3px solid #5d9415;
    object-fit:cover;
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
    padding:50px;
    border-radius:15px;
    max-width:450px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

.card h2 {
    color:#5A0E24;
    margin-bottom:30px;
}

.form-group {
    margin-bottom:18px;
}

.form-group label {
    font-weight:bold;
    display:block;
    margin-bottom:10px;
}

.form-group input {
    width:100%;
    padding:12px;
    border-radius:5px;
    border:1px solid #ccc;
}

.btn {
    background:#5d9415;
    color:white;
    padding:12px 20px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}

.alert-success {
    color:green;
    font-weight:bold;
    margin-top:10px;
}

.alert-error {
    color:red;
    font-weight:bold;
    margin-top:10px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-profile">
        <img src="<?= $profile_pic ?: 'https://via.placeholder.com/85' ?>">
        <h3><?= htmlspecialchars($student_name) ?></h3>
    </div>

    <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="join_class.php" class="active"><i class="fas fa-users"></i> Join Class</a>
    <a href="my_classes_student.php">
    <i class="fas fa-chalkboard"></i> My Classes</a>
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
        <h2>Join a Class</h2>

        <form method="POST">
            <div class="form-group">
                <label>Class Code</label>
                <input type="text" name="class_code" placeholder="Enter class code" required>
            </div>

            <button class="btn" name="join_class">Join Class</button>
        </form>

        <?php if (isset($success)) echo "<div class='alert-success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert-error'>$error</div>"; ?>
    </div>

</div>

</body>
</html>
