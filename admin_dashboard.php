<?php
session_start();

/* =========================
   ROLE PROTECTION (ADMIN)
   ========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   DATABASE CONNECTION
   ========================= */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

/* =========================
   FETCH DASHBOARD DATA
   ========================= */
$teacher_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM Teachers")
)['total'];

$student_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM Students")
)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* ===== GLOBAL LAYOUT ===== */
        body.admin-layout {
            display: flex;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f7f6;
        }

        /* ===== SIDEBAR ===== */
        .admin-sidebar {
            width: 260px;
            height: 100vh;
            background-color: #5A0E24;
            color: white;
            position: fixed;
        }

        .sidebar-title {
            text-align: center;
            padding: 22px;
            font-size: 22px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-link {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            font-size: 15px;
        }

        .sidebar-link i {
            margin-right: 10px;
        }

        .sidebar-link:hover {
            background-color: rgba(255,255,255,0.15);
        }

        .sidebar-logout {
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        /* ===== MAIN CONTENT ===== */
        .admin-content {
            margin-left: 260px;
            padding: 40px;
            width: 100%;
        }

        .dashboard-heading {
            font-size: 26px;
            margin-bottom: 30px;
            color: #333;
        }

        /* ===== STATS CARDS ===== */
        .stats-container {
            display: flex;
            gap: 20px;
        }

        .stats-card {
            background-color: white;
            flex: 1;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .stats-icon {
            font-size: 40px;
            color: #5A0E24;
        }

        .stats-title {
            margin-top: 15px;
            font-size: 16px;
            color: #555;
        }

        .stats-value {
            margin-top: 10px;
            font-size: 32px;
            font-weight: bold;
            color: #5d9415;
        }
    </style>
</head>

<body class="admin-layout">

    <!-- ===== SIDEBAR ===== -->
    <div class="admin-sidebar">
        <div class="sidebar-title">Admin Panel</div>

        <a href="admin_dashboard.php" class="sidebar-link">
            <i class="fas fa-chart-pie"></i> Overview
        </a>

        <a href="manage_teachers.php" class="sidebar-link">
            <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
        </a>

        <a href="manage_students.php" class="sidebar-link">
            <i class="fas fa-user-graduate"></i> Manage Students
        </a>

        <a href="logout.php" class="sidebar-link sidebar-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="admin-content">
        <div class="dashboard-heading">
            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </div>

        <div class="stats-container">

            <div class="stats-card">
                <i class="fas fa-chalkboard-teacher stats-icon"></i>
                <div class="stats-title">Total Teachers</div>
                <div class="stats-value"><?php echo $teacher_count; ?></div>
            </div>

            <div class="stats-card">
                <i class="fas fa-user-graduate stats-icon"></i>
                <div class="stats-title">Total Students</div>
                <div class="stats-value"><?php echo $student_count; ?></div>
            </div>

        </div>
    </div>

</body>
</html>
