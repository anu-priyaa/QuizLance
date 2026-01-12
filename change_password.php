<?php
session_start();

/* =========================
   AUTH CHECK
   ========================= */
if (!isset($_SESSION['role'], $_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$user_id = (int) $_SESSION['user_id'];
$role    = $_SESSION['role'];

/* =========================
   ROLE → TABLE MAPPING
   ========================= */
switch ($role) {
    case 'teacher':
        $table = 'Teachers';
        $redirect = 'login.php';
        break;

    case 'student':
        $table = 'Students';
        $redirect = 'login.php';
        break;

    case 'admin':
        $table = 'Admins';
        $redirect = 'login.php';
        break;

    default:
        die("Invalid role");
}

/* =========================
   FETCH CURRENT PASSWORD
   ========================= */
$res = mysqli_query(
    $conn,
    "SELECT password FROM $table WHERE id = $user_id"
);

$user = mysqli_fetch_assoc($res);

/* =========================
   CHANGE PASSWORD
   ========================= */
if (isset($_POST['change_password'])) {

    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($old_password === '' || $new_password === '' || $confirm_pass === '') {
        $error = "All fields are required";
    }
    elseif (!password_verify($old_password, $user['password'])) {
        $error = "Old password is incorrect";
    }
    elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    }
    elseif ($new_password !== $confirm_pass) {
        $error = "Passwords do not match";
    }
    else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        mysqli_query(
            $conn,
            "UPDATE $table SET password='$hashed' WHERE id=$user_id"
        );

        session_destroy();
        header("Location: $redirect?password_changed=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password | QuizLance</title>

<style>
* { box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body {
    background:#f0f2f5;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card {
    background:white;
    width:420px;
    padding:30px;
    border-radius:15px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
    border-left:6px solid #5d9415;
}

.card h2 {
    text-align:center;
    margin-bottom:20px;
    color:#5A0E24;
}

input {
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border-radius:6px;
    border:1px solid #ccc;
}

button {
    width:100%;
    padding:12px;
    background:#5d9415;
    border:none;
    border-radius:6px;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

button:hover {
    background:#4b7a12;
}

.error {
    color:red;
    margin-bottom:10px;
    text-align:center;
    font-weight:bold;
}

.note {
    text-align:center;
    font-size:13px;
    margin-top:10px;
    color:#666;
}
</style>
</head>

<body>

<div class="card">
    <h2>Change Password</h2>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="password" name="old_password" placeholder="Old Password" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>

        <button name="change_password">Update Password</button>
    </form>

    <div class="note">
        You will be logged out after password change
    </div>
</div>

</body>
</html>
