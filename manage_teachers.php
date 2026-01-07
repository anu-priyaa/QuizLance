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
   ADD TEACHER
   ========================= */
if (isset($_POST['add_teacher'])) {

    $name     = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    if ($name === '' || $username === '' || $email === '' || $password === '') {
        $error = "All fields are required";
    } else {

        $check = mysqli_query(
            $conn,
            "SELECT id FROM Teachers 
             WHERE email='$email' OR username='$username'"
        );

        if (mysqli_num_rows($check) > 0) {
            $error = "Teacher with this email or username already exists";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query(
                $conn,
                "INSERT INTO Teachers (name, username, email, password, status)
                 VALUES ('$name', '$username', '$email', '$hashedPassword', 'active')"
            );

            $success = "Teacher account created successfully";
        }
    }
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
   FETCH TEACHERS BASED ON VIEW
   ========================= */
$where = "";
if ($view === 'active')   $where = "WHERE status='active'";
if ($view === 'inactive') $where = "WHERE status='inactive'";

$teachers = mysqli_query(
    $conn,
    "SELECT id, name, username, email, status
     FROM Teachers $where
     ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Teachers | QuizLance</title>
    <style>
        body { margin:0; font-family:'Segoe UI', sans-serif; background:#f4f7f6; display:flex; }

        /* Sidebar */
        .sidebar {
            width:260px;
            background:#5A0E24;
            height:100vh;
            color:white;
            padding-top:20px;
        }

        .sidebar a {
            display:block;
            padding:15px 25px;
            color:white;
            text-decoration:none;
        }

        .sidebar a:hover {
            background:rgba(255,255,255,0.15);
        }

        /* Content */
        .content {
            flex:1;
            padding:40px;
        }

        .card {
            background:white;
            padding:30px;
            border-radius:10px;
            max-width:550px;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }

        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-weight:bold; margin-bottom:6px; }
        .form-group input { width:100%; padding:10px; }

        .btn {
            background:#5d9415;
            color:white;
            padding:10px 18px;
            border:none;
            border-radius:6px;
            font-weight:bold;
            cursor:pointer;
        }
        .back-btn {
            background:#5A0E24;
            color:white;
            padding:8px 14px;
            border:none;
            border-radius:6px;
            font-weight:bold;
            cursor:pointer;
            text-decoration:none;
            display:inline-block;
            margin-bottom:12px;
        }

        table {
            width:100%;
            background:white;
            border-collapse:collapse;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }

        th, td { padding:14px; border-bottom:1px solid #ddd; }
        th { background:#5A0E24; color:white; }

        .active { color:green; font-weight:bold; }
        .inactive { color:red; font-weight:bold; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="manage_teachers.php?view=create">➕ Create Teacher</a>
    <a href="manage_teachers.php?view=all">📋 All Teachers</a>
    <a href="manage_teachers.php?view=active">✅ Active Teachers</a>
    <a href="manage_teachers.php?view=inactive">❌ Inactive Teachers</a>
</div>

<!-- CONTENT -->
<div class="content">

<?php if ($view === 'create'): ?>
    <div class="card">
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        <h2>Create Teacher</h2>
        <form method="POST">
            <div class="form-group"><label>Name</label><input name="name" required></div>
            <div class="form-group"><label>Username</label><input name="username" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button class="btn" name="add_teacher">Create</button>
        </form>
        <?php if(isset($success)) echo $success; ?>
        <?php if(isset($error)) echo $error; ?>
    </div>

<?php else: ?>
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
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
            <td class="<?= $row['status'] ?>">
                <?= ucfirst($row['status']) ?>
            </td>
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
