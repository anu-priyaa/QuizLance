<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];

/* FETCH TEACHER INFO */
$res = mysqli_query(
    $conn,
    "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id"
);
$teacher = mysqli_fetch_assoc($res);

$teacher_name = $teacher['name'];
$profile_pic  = $teacher['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH CLASSES */
$class_res = mysqli_query(
    $conn,
    "SELECT id, class_name FROM Classes
     WHERE teacher_id=$teacher_id AND status='active'"
);

/* ADD STUDENT MANUALLY */
if (isset($_POST['add_student'])) {

    $class_id = (int) $_POST['class_id'];
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));

    if ($email === '') {
        $error = "Student email is required";
    } else {

        $stu = mysqli_query($conn, "SELECT id FROM Students WHERE email='$email'");

        if (mysqli_num_rows($stu) === 0) {
            $error = "Student not found";
        } else {
            $student = mysqli_fetch_assoc($stu);
            $student_id = $student['id'];

            $check = mysqli_query(
                $conn,
                "SELECT id FROM class_students
                 WHERE class_id=$class_id AND student_id=$student_id"
            );

            if (mysqli_num_rows($check) > 0) {
                $error = "Student already added to this class";
            } else {
                mysqli_query(
                    $conn,
                    "INSERT INTO class_students (class_id, student_id)
                     VALUES ($class_id, $student_id)"
                );
                $success = "Student added successfully";
            }
        }
    }
}

/* CSV UPLOAD */
if (isset($_POST['upload_csv'])) {

    $class_id = (int) $_POST['class_id'];

    if ($_FILES['csv_file']['error'] !== 0) {
        $error = "CSV upload failed";
    } else {

        $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
        $count = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {

            $email = trim($data[0]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            $stu = mysqli_query($conn, "SELECT id FROM Students WHERE email='$email'");
            if (mysqli_num_rows($stu) === 0) continue;

            $student = mysqli_fetch_assoc($stu);
            $student_id = $student['id'];

            $check = mysqli_query(
                $conn,
                "SELECT id FROM class_students
                 WHERE class_id=$class_id AND student_id=$student_id"
            );

            if (mysqli_num_rows($check) === 0) {
                mysqli_query(
                    $conn,
                    "INSERT INTO class_students (class_id, student_id)
                     VALUES ($class_id, $student_id)"
                );
                $count++;
            }
        }

        fclose($handle);
        $success = "$count students added successfully via CSV";
    }
}

/* ===== ADD SUB TEACHER ===== */
if (isset($_POST['add_sub_teacher'])) {

    $class_id = (int) $_POST['class_id'];
    $sub_teacher_id = (int) $_POST['sub_teacher_id'];

    // Verify logged-in teacher owns this class
    $verify = mysqli_query(
        $conn,
        "SELECT id FROM Classes 
         WHERE id=$class_id AND teacher_id=$teacher_id"
    );

    if (mysqli_num_rows($verify) === 0) {
        $error = "You are not authorized to modify this class";
    } else {

        $check = mysqli_query(
            $conn,
            "SELECT id FROM Class_SubTeachers
             WHERE class_id=$class_id AND teacher_id=$sub_teacher_id"
        );

        if (mysqli_num_rows($check) > 0) {
            $error = "Teacher already added to this class";
        } else {
            mysqli_query(
                $conn,
                "INSERT INTO Class_SubTeachers (class_id, teacher_id)
                 VALUES ($class_id, $sub_teacher_id)"
            );
            $success = "Sub Teacher added successfully!";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Classes | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { background:#f0f2f5; }

/* ===== TOP BAR ===== */
.topbar {
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:60px;
    background:#5A0E24;
    color:white;
    display:flex;
    align-items:center;
    padding:0 20px;
    z-index:1001;
}

/* ===== TOP PROFILE MENU ===== */
.top-profile {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    position: relative;
}

.top-profile img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #5d9415;
}

.top-profile span {
    font-size: 14px;
    font-weight: 500;
}

/* DROPDOWN */
.profile-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 55px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    min-width: 180px;
    overflow: hidden;
    z-index: 3000;
}

.profile-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}

.profile-dropdown a:hover {
    background: #f2f2f2;
}

.topbar i { font-size:24px; cursor:pointer; }

/* ===== SIDEBAR ===== */
.sidebar {
    width:260px;
    background:#5A0E24;
    color:white;
    display:flex;
    flex-direction:column;
    position:fixed;
    top:60px;
    left:0;
    height:calc(100vh - 60px);
    transition:0.3s ease;
    z-index:1000;
}
.sidebar.collapsed { transform:translateX(-100%); }
.sidebar.no-transition { transition:none !important; }

.sidebar-profile {
    text-align:center;
    padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    cursor:pointer;
}
.sidebar-profile img {
    width:85px;
    height:85px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #5d9415;
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

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left:260px;
    padding:90px 40px 40px;
    transition:0.3s ease;
}
.main-content.full { margin-left:0; }

/* ===== PAGE CARD ===== */
.page-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
    max-width:520px;
    margin-bottom:30px;
}

/* ===== PAGE GRID ===== */
.page-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
    gap: 30px;
}

.page-card h1 {
    color:#5A0E24;
    margin-bottom:10px;
}
.page-card p {
    margin-bottom:25px;
    color:#555;
}

/* FORM */
select, input {
    width:100%;
    padding:12px;
    border-radius:6px;
    border:1px solid #ccc;
    margin-bottom:20px;
}
button {
    background:#5d9415;
    color:white;
    padding:12px 18px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}
button:hover {
    background:#4e7d12;
    transform:translateY(-2px);
}

.alert-success { color:green; font-weight:bold; }
.alert-error { color:red; font-weight:bold; }

/* PROFILE POPUP */
.profile-popup {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    z-index:2000;
    justify-content:center;
    align-items:center;
}
.profile-popup-content {
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    position:relative;
}
.profile-popup-content img {
    width:200px;
    height:200px;
    border-radius:50%;
    border:4px solid #5d9415;

    object-fit: cover;      /* 🔥 MOST IMPORTANT */
    object-position: center;
    display: block;
}
.close-btn {
    position:absolute;
    top:10px;
    right:14px;
    font-size:22px;
    cursor:pointer;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <i class="fas fa-bars" id="menuToggle"></i>

    <!-- PROFILE ICON (TOP RIGHT) -->
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $profile_pic ? $profile_pic . '?t=' . time() : 'https://via.placeholder.com/36' ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_teacher.php">
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
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>

    <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="manage_classes.php" class="active"><i class="fas fa-users"></i> Manage Class</a>
    <a href="archived_classes.php">
        <i class="fas fa-archive"></i> Archived Classes
    </a>
    <a href="view_students.php"><i class="fas fa-eye"></i> View Students</a>
    <a href="view_attendance_teacher.php"><i class="fas fa-clipboard-list"></i> Attendance</a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content full" id="mainContent">

    <div class="page-grid">

    <div class="page-card">
        <h1>Add Student Manually</h1>
        <p>Add an existing student to a class using email.</p>

        <form method="POST">
            <select name="class_id" required>
                <option value="">-- Select Class --</option>
                <?php while ($c = mysqli_fetch_assoc($class_res)): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['class_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <input type="email" name="email" placeholder="Student email" required>
            <button name="add_student">Add Student</button>
        </form>
    </div>

    <div class="page-card">
        <h1>Upload Students via CSV</h1>
        <p>Bulk add students to a class using CSV file.</p>

        <form method="POST" enctype="multipart/form-data">
            <select name="class_id" required>
                <option value="">-- Select Class --</option>
                <?php
                mysqli_data_seek($class_res, 0);
                while ($c = mysqli_fetch_assoc($class_res)):
                ?>
                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['class_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <input type="file" name="csv_file" accept=".csv" required>
            <button name="upload_csv">Upload CSV</button>
        </form>
    </div>

    <div class="page-card">
    <h1>Add Sub Teacher</h1>
    <p>Assign another teacher to manage your class.</p>

    <form method="POST">
        <select name="class_id" required>
            <option value="">-- Select Class --</option>
            <?php
            mysqli_data_seek($class_res, 0);
            while ($c = mysqli_fetch_assoc($class_res)):
            ?>
                <option value="<?= $c['id'] ?>">
                    <?= htmlspecialchars($c['class_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="sub_teacher_id" required>
            <option value="">-- Select Teacher --</option>
            <?php
            $teachers = mysqli_query(
                $conn,
                "SELECT id, name FROM Teachers 
                 WHERE id != $teacher_id"
            );
            while ($t = mysqli_fetch_assoc($teachers)):
            ?>
                <option value="<?= $t['id'] ?>">
                    <?= htmlspecialchars($t['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button name="add_sub_teacher">Add Sub Teacher</button>
    </form>
</div>


</div>


    <?php if (isset($success)) echo "<p class='alert-success'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='alert-error'>$error</p>"; ?>

</div>

<!-- PROFILE POPUP -->
<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($teacher_name) ?></h2>
    </div>
</div>

<script>
const menuToggle  = document.getElementById('menuToggle');
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

/* restore sidebar state */
window.addEventListener('DOMContentLoaded', () => {
    const state = sessionStorage.getItem('sidebar');
    if (state === 'open') {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('full');
    }
    setTimeout(() => sidebar.classList.remove('no-transition'), 50);
});

/* toggle sidebar */
menuToggle.onclick = () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('full');

    sessionStorage.setItem(
        'sidebar',
        sidebar.classList.contains('collapsed') ? 'closed' : 'open'
    );
};

/* PROFILE POPUP */
function openProfilePopup() {
    document.getElementById('profilePopup').style.display = 'flex';
}
function closeProfilePopup() {
    document.getElementById('profilePopup').style.display = 'none';
}
</script>

<script>
function toggleProfileMenu() {
    const menu = document.getElementById('profileDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

/* Close dropdown when clicking outside */
document.addEventListener('click', function (e) {
    const profile = document.querySelector('.top-profile');
    if (profile && !profile.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>