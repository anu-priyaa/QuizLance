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
   CURRENT VIEW
   ========================= */
$view = $_GET['view'] ?? 'all';

/* =========================
   ADD TEACHER (SIGNUP-STYLE POPUPS)
   ========================= */
if (isset($_POST['add_teacher'])) {

    $name     = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    if ($name === '' || $username === '' || $email === '' || $password === '') {
        $_SESSION['message'] = ['type'=>'error','text'=>'All fields are required.'];
        header("Location: manage_teachers.php?view=create"); exit();
    }

    if (!preg_match('/^[\p{L} ]{3,50}$/u', $name)) {
        $_SESSION['message'] = ['type'=>'error','text'=>'Name must contain only letters and spaces (3–50 characters).'];
        header("Location: manage_teachers.php?view=create"); exit();
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $_SESSION['message'] = ['type'=>'error','text'=>'Username must be 3–20 characters and may contain letters, numbers, and underscores only.'];
        header("Location: manage_teachers.php?view=create"); exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = ['type'=>'error','text'=>'Please enter a valid email address.'];
        header("Location: manage_teachers.php?view=create"); exit();
    }

    $pwErrors = [];
    if (strlen($password) < 8) $pwErrors[] = 'at least 8 characters';
    if (!preg_match('/[A-Z]/', $password)) $pwErrors[] = 'one uppercase letter';
    if (!preg_match('/[a-z]/', $password)) $pwErrors[] = 'one lowercase letter';
    if (!preg_match('/[0-9]/', $password)) $pwErrors[] = 'one number';
    if (!preg_match('/[!@#$%^&*()_+\-=[\]{};:\"\\|,.<>\/?]/', $password))
        $pwErrors[] = 'one special character';

    if (!empty($pwErrors)) {
        $_SESSION['message'] = [
            'type'=>'error',
            'text'=>'Password must contain ' . implode(', ', $pwErrors) . '.'
        ];
        header("Location: manage_teachers.php?view=create"); exit();
    }

    $check = mysqli_query(
        $conn,
        "SELECT id FROM Teachers WHERE email='$email' OR username='$username' LIMIT 1"
    );

    if (mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = ['type'=>'error','text'=>'Teacher with this email or username already exists.'];
        header("Location: manage_teachers.php?view=create"); exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    mysqli_query(
        $conn,
        "INSERT INTO Teachers (name, username, email, password, status)
         VALUES ('$name','$username','$email','$hashed','active')"
    );

    $_SESSION['message'] = ['type'=>'success','text'=>'Teacher account created successfully.'];
    header("Location: manage_teachers.php?view=create"); exit();
}

/* =========================
   ACTIVATE / DISABLE
   ========================= */
if (isset($_GET['disable'])) {
    $id = (int) $_GET['disable'];
    mysqli_query($conn, "UPDATE Teachers SET status='inactive' WHERE id=$id");
    header("Location: manage_teachers.php?view=all");
    exit();
}

if (isset($_GET['activate'])) {
    $id = (int) $_GET['activate'];
    mysqli_query($conn, "UPDATE Teachers SET status='active' WHERE id=$id");
    header("Location: manage_teachers.php?view=all");
    exit();
}

/* =========================
   FETCH TEACHERS
   ========================= */
$where = "";
if ($view === 'active')   $where = "WHERE status='active'";
if ($view === 'inactive') $where = "WHERE status='inactive'";

$teachers = mysqli_query(
    $conn,
    "SELECT id, name, username, email, status FROM Teachers $where ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Teachers | QuizLance</title>
    <style>
        body { margin:0; font-family:'Segoe UI', sans-serif; background:#f4f7f6; display:flex; }

        .sidebar {
            width:260px; background:#5A0E24; height:100vh; color:white; padding-top:20px;
        }
        .sidebar a {
            display:block; padding:15px 25px; color:white; text-decoration:none;
        }
        .sidebar a:hover { background:rgba(255,255,255,0.15); }

        .content { flex:1; padding:40px; }

        .card {
            background:white; padding:30px; border-radius:10px;
            max-width:550px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }

        .form-group { margin-bottom:14px; }
        .form-group label { font-weight:bold; display:block; margin-bottom:6px; }
        .form-group input { width:100%; padding:10px; }

        .btn {
            background:#5d9415; color:white; padding:10px 18px;
            border:none; border-radius:6px; font-weight:bold;
        }

        .back-btn {
            background:#5A0E24; color:white; padding:8px 14px;
            border-radius:6px; text-decoration:none; display:inline-block;
            margin-bottom:12px;
        }

        table {
            width:100%; background:white; border-collapse:collapse;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }
        th, td { padding:14px; border-bottom:1px solid #ddd; }
        th { background:#5A0E24; color:white; }

        .active { color:green; font-weight:bold; }
        .inactive { color:red; font-weight:bold; }

        /* POPUP ALERT (SAME AS SIGNUP) */
        .alert {
            padding:14px 20px;
            border-radius:10px;
            font-weight:600;
            text-align:center;
            max-width:420px;
            position:absolute;
            top:12%;
            left:50%;
            transform:translateX(-50%);
            z-index:20;
        }
        .alert-success {
            background:#d4edda; color:#155724; border:1px solid #c3e6cb;
        }
        .alert-error {
            background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<?php
if (isset($_SESSION['message'])):
    $msg = $_SESSION['message'];
    $cls = ($msg['type']=='success') ? 'alert-success' : 'alert-error';
?>
<div class="alert <?= $cls ?>">
    <?= htmlspecialchars($msg['text']) ?>
</div>
<?php unset($_SESSION['message']); endif; ?>

<script>
setTimeout(() => {
    const a = document.querySelector('.alert');
    if (a) {
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    }
}, 3000);
</script>

<div class="sidebar">
    <a href="?view=create">➕ Create Teacher</a>
    <a href="?view=all">📋 All Teachers</a>
    <a href="?view=active">✅ Active Teachers</a>
    <a href="?view=inactive">❌ Inactive Teachers</a>
</div>

<div class="content">

<?php if ($view === 'create'): ?>
<div class="card">
    <a href="admin_dashboard.php" class="back-btn">← Back</a>
    <h2>Create Teacher</h2>
    <form method="POST">
        <div class="form-group"><label>Name</label><input name="name" required></div>
        <div class="form-group"><label>Username</label><input name="username" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <button class="btn" name="add_teacher">Create</button>
    </form>
</div>

<?php else: ?>
<a href="admin_dashboard.php" class="back-btn">← Back</a>
<h2>Teachers List</h2>
<table>
<tr>
    <th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Action</th>
</tr>
<?php while($row = mysqli_fetch_assoc($teachers)): ?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td class="<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
    <td>
        <?php if($row['status']=='active'): ?>
            <a href="?disable=<?= $row['id'] ?>">Deactivate</a>
        <?php else: ?>
            <a href="?activate=<?= $row['id'] ?>">Activate</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php endif; ?>

</div>
</body>
</html>
