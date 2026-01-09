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

// Ensure timezone and auto-update quiz statuses based on current time
date_default_timezone_set('Asia/Kolkata');

// 1) Mark quizzes as completed when end_time has passed
mysqli_query($conn,
    "UPDATE quizzes SET status='completed' WHERE teacher_id=$teacher_id AND status IN ('scheduled','live') AND end_time < NOW()"
);

// 2) Mark quizzes as live when within start and end time (only transition from scheduled)
mysqli_query($conn,
    "UPDATE quizzes SET status='live' WHERE teacher_id=$teacher_id AND status='scheduled' AND start_time <= NOW() AND end_time >= NOW()"
);

/* =========================
   ACTIVE SIDEBAR LOGIC
   ========================= */
$currentPage = basename($_SERVER['PHP_SELF']);
$dashboardPages = [
    'teacher_dashboard.php',
    'create_quiz.php',
    'scheduled_quizzes.php',
    'add_questions.php'
];
$isDashboardActive = in_array($currentPage, $dashboardPages);

/* =========================
   FETCH TEACHER INFO
   ========================= */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);

/* =========================
   DELETE QUIZ (SAFE)
   ========================= */
if (isset($_POST['confirm_delete'])) {
    $quiz_id = (int)$_POST['quiz_id'];

    $check = mysqli_query($conn,
        "SELECT status FROM quizzes WHERE id=$quiz_id AND teacher_id=$teacher_id"
    );

    if ($row = mysqli_fetch_assoc($check)) {

        if ($row['status'] === 'draft' || $row['status'] === 'scheduled') {

            // 1️⃣ Delete quiz attempts FIRST (FK constraint)
            mysqli_query($conn,
                "DELETE FROM quiz_attempts WHERE quiz_id=$quiz_id"
            );

            // 2️⃣ Delete student answers
            mysqli_query($conn,
                "DELETE sa FROM student_answers sa
                 JOIN questions q ON sa.question_id = q.id
                 WHERE q.quiz_id=$quiz_id"
            );

            // 3️⃣ Delete question answers
            mysqli_query($conn,
                "DELETE qa FROM question_answers qa
                 JOIN questions q ON qa.question_id = q.id
                 WHERE q.quiz_id=$quiz_id"
            );

            // 4️⃣ Delete question options
            mysqli_query($conn,
                "DELETE qo FROM question_options qo
                 JOIN questions q ON qo.question_id = q.id
                 WHERE q.quiz_id=$quiz_id"
            );

            // 5️⃣ Delete questions
            mysqli_query($conn,
                "DELETE FROM questions WHERE quiz_id=$quiz_id"
            );

            // 6️⃣ Finally delete quiz
            mysqli_query($conn,
                "DELETE FROM quizzes WHERE id=$quiz_id"
            );

            $success = "Quiz deleted successfully";

        } else {
            $error = "Live or completed quizzes cannot be deleted";
        }
    }
}


/* =========================
   FETCH QUIZZES
   ========================= */
$quizzes = mysqli_query($conn,
    "SELECT q.*, c.class_name, c.class_code
     FROM quizzes q
     JOIN Classes c ON q.class_id = c.id
     WHERE q.teacher_id=$teacher_id
     ORDER BY q.created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Scheduled Quizzes | QuizLance</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{display:flex;background:#f0f2f5;min-height:100vh;}

/* ===== SIDEBAR ===== */
.sidebar{
    width:260px;
    background:#5A0E24;
    color:white;
    display:flex;
    flex-direction:column;
    position:fixed;
    height:100vh;
}

.sidebar-profile{
    text-align:center;
    padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
}

.sidebar-profile img{
    width:85px;
    height:85px;
    border-radius:50%;
    border:3px solid #5d9415;
    object-fit:cover;
}

.sidebar-profile h3{
    margin-top:10px;
    font-size:16px;
}

.sidebar a{
    padding:15px 25px;
    text-decoration:none;
    color:#d1d1d1;
    display:flex;
    align-items:center;
}

.sidebar a i{
    margin-right:15px;
    width:20px;
}

.sidebar a:hover,
.sidebar a.active{
    background:#861434;
    color:white;
}

.logout{
    margin-top:auto;
    border-top:1px solid rgba(255,255,255,0.15);
}

/* ===== MAIN CONTENT ===== */
.main-content{
    margin-left:260px;
    padding:40px;
    width:100%;
    text-align:left;
}

.card{
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
    text-align:left;
}

h2{color:#5A0E24;margin-bottom:20px;}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:14px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

th{
    background:#5A0E24;
    color:white;
}

.status-draft{color:gray;font-weight:bold;}
.status-scheduled{color:green;font-weight:bold;}
.status-live{color:maroon;font-weight:bold;}
.status-completed{color:#555;font-weight:bold;}

.btn-delete{
    background:none;
    border:none;
    color:red;
    font-weight:bold;
    cursor:pointer;
}

.alert-success{color:green;font-weight:bold;margin-bottom:15px;}
.alert-error{color:red;font-weight:bold;margin-bottom:15px;}

/* ===== DELETE MODAL ===== */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
}

.modal-content{
    background:white;
    padding:25px;
    border-radius:12px;
    text-align:center;
    width:320px;
}

.modal-content button{
    margin:10px;
    padding:8px 16px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

.confirm{background:#5d9415;color:white;}
.cancel{background:#ccc;}
</style>
</head>

<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">

    <div class="sidebar-profile">
        <img src="<?= $teacher['profile_pic'] ?: 'https://via.placeholder.com/85' ?>">
        <h3><?= htmlspecialchars($teacher['name']) ?></h3>
    </div>

    <a href="teacher_dashboard.php" class="<?= $isDashboardActive ? 'active' : '' ?>">
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a>
    <a href="profile_teacher.php"><i class="fas fa-user-edit"></i> Profile</a>

    <div class="logout">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

<div class="card">
<h2>Scheduled Quizzes</h2>

<?php if(isset($success)) echo "<div class='alert-success'>$success</div>"; ?>
<?php if(isset($error)) echo "<div class='alert-error'>$error</div>"; ?>

<table>
<tr>
<th>Title</th>
<th>Class</th>
<th>Duration</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($q=mysqli_fetch_assoc($quizzes)): ?>
<tr>
<td><?= htmlspecialchars($q['title']) ?></td>
<td><?= htmlspecialchars($q['class_name']) ?> (<?= $q['class_code'] ?>)</td>
<td><?= $q['duration'] ?> min</td>
<td class="status-<?= $q['status'] ?>"><?= ucfirst($q['status']) ?></td>
<td>
<?php if($q['status']=='draft' || $q['status']=='scheduled'): ?>
<button class="btn-delete" onclick="openModal(<?= $q['id'] ?>)">Delete</button>
<?php else: ?>—<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

<!-- DELETE MODAL -->
<div class="modal" id="deleteModal">
<div class="modal-content">
<h3>Delete Quiz?</h3>
<p>This action cannot be undone.</p>
<form method="POST">
<input type="hidden" name="quiz_id" id="quiz_id">
<button class="confirm" name="confirm_delete">Yes, Delete</button>
<button type="button" class="cancel" onclick="closeModal()">Cancel</button>
</form>
</div>
</div>

<script>
function openModal(id){
    document.getElementById('quiz_id').value=id;
    document.getElementById('deleteModal').style.display='flex';
}
function closeModal(){
    document.getElementById('deleteModal').style.display='none';
}
</script>

</body>
</html>
