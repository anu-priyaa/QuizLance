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
   ACTIVATE / DEACTIVATE STUDENT
   ========================= */
if (isset($_GET['disable'])) {
    $id = (int) $_GET['disable'];
    mysqli_query($conn, "UPDATE Students SET status='inactive' WHERE id=$id");
    header("Location: manage_students.php?view=all");
    exit();
}

if (isset($_GET['activate'])) {
    $id = (int) $_GET['activate'];
    mysqli_query($conn, "UPDATE Students SET status='active' WHERE id=$id");
    header("Location: manage_students.php?view=all");
    exit();
}

/* =========================
   FETCH STUDENTS BASED ON VIEW
   ========================= */
$where = "";
if ($view === 'active')   $where = "WHERE status='active'";
if ($view === 'inactive') $where = "WHERE status='inactive'";

$students = mysqli_query(
    $conn,
    "SELECT id, name, username, admission_id, email, status
     FROM Students $where
     ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Students | QuizLance</title>
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

        th, td { padding:14px; border-bottom:1px solid #ddd; text-align:left; }
        th { background:#5A0E24; color:white; }

        .active { color:green; font-weight:bold; }
        .inactive { color:red; font-weight:bold; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="manage_students.php?view=all">📋 All Students</a>
    <a href="manage_students.php?view=active">✅ Active Students</a>
    <a href="manage_students.php?view=inactive">❌ Inactive Students</a>
</div>

<!-- CONTENT -->
<div class="content">

    <!-- EXACT SAME BACK BUTTON AS manage_teachers.php -->
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>

    <h2>Students List</h2>

    <table>
        <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Admission ID</th>
            <th>Email</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($students)): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['admission_id']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td class="<?= $row['status'] ?>">
                <?= ucfirst($row['status']) ?>
            </td>
            <td>
                <?php if ($row['status'] === 'active'): ?>
                    <a href="?disable=<?= $row['id'] ?>">Deactivate</a>
                <?php else: ?>
                    <a href="?activate=<?= $row['id'] ?>">Activate</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

</div>
</body>
</html>
