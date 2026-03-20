<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$student_id = $_SESSION['user_id'];

/* STUDENT INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];

$imgSrc = $profile_pic
    ? htmlspecialchars($profile_pic) . '?t=' . time()
    : 'https://via.placeholder.com/85';

/* FETCH SCHEDULED QUIZZES */
$quizzes = mysqli_query(
    $conn,
    "SELECT q.id, q.title, q.start_time, q.end_time, c.class_name, t.name AS teacher_name
     FROM quizzes q
     JOIN Classes c ON q.class_id = c.id
     JOIN class_students cs ON cs.class_id = c.id
     JOIN Teachers t ON q.teacher_id = t.id
     WHERE cs.student_id = $student_id
     ORDER BY q.start_time ASC"
);

$now = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scheduled Quizzes | QuizLance</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

/* ===== TOP BAR ===== */
.topbar{
    position:fixed;top:0;left:0;width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;
    padding:0 20px;z-index:1001;
}
.topbar i{font-size:24px;cursor:pointer;}

/* ===== TOP PROFILE ===== */
.top-profile{
    margin-left:auto;display:flex;align-items:center;
    gap:8px;cursor:pointer;position:relative;
}
.top-profile img{
    width:36px;height:36px;border-radius:50%;
    object-fit:cover;border:2px solid #5d9415;
}
.top-profile span{font-size:14px;font-weight:500;}

/* DROPDOWN */
.profile-dropdown{
    display:none;position:absolute;right:0;top:55px;
    background:white;border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
    min-width:180px;overflow:hidden;z-index:3000;
}
.profile-dropdown a{
    display:flex;align-items:center;gap:10px;
    padding:12px 15px;text-decoration:none;
    color:#333;font-size:14px;
}
.profile-dropdown a:hover{background:#f2f2f2;}

/* ===== MAIN ===== */
.main-content{
    padding:90px 20px 40px;
    max-width: 100%;
}
.card{
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
    width:100%;
}

h2{color:#5A0E24;margin-bottom:20px;}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    table-layout: auto;
}
th,td{padding:14px;border-bottom:1px solid #ddd;text-align:left;}
th{background:#5A0E24;color:white;}

.btn{
    padding:6px 12px;border-radius:5px;
    font-weight:bold;font-size:14px;
}
.btn-live{background:#5d9415;color:white;}
.btn-upcoming{background:#999;color:white;}
.btn-expired{background:#ccc;color:#333;}

td:last-child {
    white-space: nowrap;
}

/* PROFILE POPUP */
.profile-popup{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,0.5);
    z-index:2000;justify-content:center;align-items:center;
}
.profile-popup-content{
    background:white;padding:30px;border-radius:15px;
    text-align:center;position:relative;
}
.profile-popup-content img{
    width:200px;height:200px;border-radius:50%;
    border:4px solid #5d9415;object-fit:cover;
}
.close-btn{
    position:absolute;top:10px;right:14px;
    font-size:22px;cursor:pointer;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar">
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($student_name) ?></span>

        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_student.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- MAIN -->
<div class="main-content">
    <a href="student_dashboard.php" style="display: inline-block; background: #5A0E24; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px;">← Back to Dashboard</a>
    <div class="card">
        <h2>Scheduled Quizzes</h2>

        <table>
            <tr>
                <th>Quiz Title</th>
                <th>Class</th>
<th>Created By</th>
<th>Start Time</th>
                <th>End Time</th>
                <th>Action</th>
            </tr>

            <?php while ($q = mysqli_fetch_assoc($quizzes)):
            $attemptRes = mysqli_query(
    $conn,
    "SELECT id, status 
     FROM quiz_attempts 
     WHERE quiz_id={$q['id']} 
     AND student_id=$student_id
     LIMIT 1"
);

$attempt = mysqli_fetch_assoc($attemptRes);

                $start = strtotime($q['start_time']);
                $end   = strtotime($q['end_time']);
            ?>
            <tr>
                <td><?= htmlspecialchars($q['title']) ?></td>
                <td><?= htmlspecialchars($q['class_name']) ?></td>
<td><?= htmlspecialchars($q['teacher_name']) ?></td>
<td><?= date("d M Y, h:i A", $start) ?></td>
                <td><?= date("d M Y, h:i A", $end) ?></td>
                <td>
<?php
if ($attempt) {

    if ($attempt['status'] === 'submitted') {

        // View Score button
        echo '<a href="quiz_result.php?attempt_id='.$attempt['id'].'" 
                 class="btn btn-live" style="margin-right:6px;">
                View Score
              </a>';

        // Download Answer Key button
        echo '<a href="download_answer_key.php?quiz_id='.$q['id'].'" 
                 class="btn btn-live">
                Download Answer Key
              </a>';

    } else {
        // 🕒 Started but not submitted
        echo '<a href="attempt_quiz.php?quiz_id='.$q['id'].'" class="btn btn-live">
                Continue
              </a>';
    }

}

/* CASE 2: Not attempted yet */
else {

    if ($now >= $start && $now <= $end) {
        echo '<a href="attempt_quiz.php?quiz_id='.$q['id'].'" class="btn btn-live">
                Attempt
              </a>';
    } elseif ($now < $start) {
        echo '<span class="btn btn-upcoming">Upcoming</span>';
    } else {
        echo '<span class="btn btn-expired">Expired</span>';
    }

}
?>
</td>

            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<!-- PROFILE POPUP -->
<div id="profilePopup" class="profile-popup">
    <div class="profile-popup-content">
        <span class="close-btn" onclick="closeProfilePopup()">&times;</span>
        <img src="<?= $imgSrc ?>">
        <h2><?= htmlspecialchars($student_name) ?></h2>
    </div>
</div>

<script>
function toggleProfileMenu(){
    const m=document.getElementById('profileDropdown');
    m.style.display=m.style.display==='block'?'none':'block';
}
document.addEventListener('click',e=>{
    const p=document.querySelector('.top-profile');
    if(p && !p.contains(e.target))
        document.getElementById('profileDropdown').style.display='none';
});
function openProfilePopup(){document.getElementById('profilePopup').style.display='flex';}
function closeProfilePopup(){document.getElementById('profilePopup').style.display='none';}
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
