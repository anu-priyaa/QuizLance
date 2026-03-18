<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");
if(!$conn){
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];
$message = "";

/* ===============================
   DELETE CERTIFICATE (OPTIONAL)
   =============================== */
if(isset($_GET['delete'])){

    $cert_id = (int)$_GET['delete'];

    $res = mysqli_query($conn,"
        SELECT file_path FROM certificates
        WHERE id=$cert_id AND teacher_id=$teacher_id
    ");

    if(mysqli_num_rows($res) > 0){
        $row = mysqli_fetch_assoc($res);
        $file = $row['file_path'];

        if(file_exists($file)){
            unlink($file);
        }

        mysqli_query($conn,"
            DELETE FROM certificates
            WHERE id=$cert_id
        ");

        $message = "Certificate deleted successfully.";
    }
}

/* ===============================
   FETCH TEACHER CERTIFICATES
   =============================== */
$certificates = mysqli_query($conn,"
    SELECT c.*, q.title, s.name
    FROM certificates c
    JOIN quizzes q ON c.quiz_id = q.id
    JOIN Students s ON c.student_id = s.id
    WHERE c.teacher_id = $teacher_id
    ORDER BY c.uploaded_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>View Uploaded Certificates</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
body{
    font-family:'Segoe UI';
    background:#f0f2f5;
    padding:40px;
}

.back-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#5A0E24;
    color:white;
    padding:10px 18px;
    border-radius:6px;
    text-decoration:none;
    font-weight:600;
    margin-bottom:20px;
    transition:all 0.3s ease;
}

.back-btn:hover{
    background:#5d9415;
}

.card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

th, td{
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

th{
    background:#5A0E24;
    color:white;
}

a.download{
    color:#5d9415;
    font-weight:bold;
    text-decoration:none;
}

a.delete{
    color:red;
    text-decoration:none;
}

.message{
    font-weight:bold;
    color:green;
}
</style>
</head>

<body>

<a href="teacher_dashboard.php" class="back-btn">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
</a>

<div class="card">
<h2>Uploaded Certificates</h2>

<p class="message"><?= $message ?></p>

<?php if(mysqli_num_rows($certificates) > 0): ?>

<table>
<tr>
    <th>Quiz</th>
    <th>Student</th>
    <th>Uploaded On</th>
    <th>Download</th>
    <th>Delete</th>
</tr>

<?php while($row = mysqli_fetch_assoc($certificates)): ?>

<tr>
    <td><?= htmlspecialchars($row['title']) ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= date("d M Y, h:i A", strtotime($row['uploaded_at'])) ?></td>
    <td>
        <a class="download" href="<?= $row['file_path'] ?>" target="_blank">
            Download
        </a>
    </td>
    <td>
        <a class="delete" 
           href="?delete=<?= $row['id'] ?>"
           onclick="return confirm('Are you sure?')">
           Delete
        </a>
    </td>
</tr>

<?php endwhile; ?>

</table>

<?php else: ?>
<p>No certificates uploaded yet.</p>
<?php endif; ?>

</div>

</body>
</html>