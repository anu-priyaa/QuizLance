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

/* FETCH CERTIFICATES */
$certificates = mysqli_query(
    $conn,
    "SELECT q.title, c.score, c.certificate_path, c.issued_at
     FROM certificates c
     JOIN quizzes q ON q.id = c.quiz_id
     WHERE c.student_id = $student_id
     ORDER BY c.issued_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Certificates | QuizLance</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f0f2f5;}

/* TOP BAR */
.topbar{
    position:fixed;top:0;left:0;width:100%;height:60px;
    background:#5A0E24;color:white;
    display:flex;align-items:center;
    padding:0 20px;z-index:1001;
}
.top-profile{
    margin-left:auto;display:flex;align-items:center;
    gap:8px;cursor:pointer;position:relative;
}
.top-profile img{
    width:36px;height:36px;border-radius:50%;
    object-fit:cover;border:2px solid #5d9415;
}
.profile-dropdown{
    display:none;position:absolute;right:0;top:55px;
    background:white;border-radius:8px;
    box-shadow:0 6px 20px rgba(0,0,0,0.15);
    min-width:180px;overflow:hidden;
}
.profile-dropdown a{
    display:flex;gap:10px;padding:12px 15px;
    text-decoration:none;color:#333;font-size:14px;
}
.profile-dropdown a:hover{background:#f2f2f2;}

/* MAIN */
.main-content{padding:90px 40px 40px;}
.card{
    background:white;padding:30px;border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}
h2{color:#5A0E24;margin-bottom:20px;}

table{width:100%;border-collapse:collapse;}
th,td{padding:14px;border-bottom:1px solid #ddd;text-align:left;}
th{background:#5A0E24;color:white;}

.btn{
    padding:6px 12px;border-radius:5px;
    font-weight:bold;font-size:14px;
    text-decoration:none;
}
.btn-download{background:#5d9415;color:white;}
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
    <a href="student_dashboard.php"
       style="display:inline-block;background:#5A0E24;color:white;
              padding:10px 18px;border-radius:6px;
              text-decoration:none;font-weight:bold;margin-bottom:20px;">
        ← Back to Dashboard
    </a>

    <div class="card">
        <h2>🎓 My Certificates</h2>

        <?php if (mysqli_num_rows($certificates) === 0): ?>
            <p>No certificates issued yet.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Quiz Title</th>
                <th>Score</th>
                <th>Issued On</th>
                <th>Certificate</th>
            </tr>

            <?php while ($c = mysqli_fetch_assoc($certificates)): ?>
            <tr>
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['score']) ?></td>
                <td><?= date("d M Y", strtotime($c['issued_at'])) ?></td>
                <td>
                    <a class="btn btn-download"
                       href="<?= htmlspecialchars($c['certificate_path']) ?>"
                       target="_blank">
                        Download
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
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
</script>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
