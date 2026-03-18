<?php
session_start();

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");

function sendCertificateMail($student_email,$student_name,$file_path){

$mail = new PHPMailer(true);

try{

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'anupriyaa245@gmail.com';
$mail->Password = 'xnfplyshkejuehwh';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('yourgmail@gmail.com','QuizLance');

$mail->addAddress($student_email,$student_name);

$mail->addAttachment($file_path);

$mail->isHTML(true);
$mail->Subject = "Your Quiz Certificate";

$mail->Body = "
Hello <b>$student_name</b>,<br><br>
Congratulations! 🎉<br><br>
You have received a certificate for completing the quiz.<br><br>
Your certificate is attached.<br><br>
QuizLance Team
";

$mail->send();

}catch(Exception $e){

}

}

if(!$conn){
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];
$message = "";

/* ===============================
   UPLOAD GENERATED CERTIFICATE
   =============================== */

if(isset($_POST['upload_generated'])){

    $quiz_id = (int)$_POST['quiz_id'];
    $student_id = (int)$_POST['student_id'];
    $path = $_POST['generated_file'];

    $insert = mysqli_query($conn,"
        INSERT INTO certificates
        (quiz_id, student_id, teacher_id, file_path)
        VALUES
        ($quiz_id, $student_id, $teacher_id, '$path')
    ");

    if($insert){

$res = mysqli_query($conn,"
SELECT name,email FROM students
WHERE id=$student_id
");

$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$student_email = $student['email'];

sendCertificateMail($student_email,$student_name,$path);

$message = "✅ Certificate uploaded and emailed to student!";
} else {
        $message = "❌ Certificate already exists!";
    }
}

/* ===============================
   HANDLE UPLOAD
   =============================== */
if(isset($_POST['upload'])){

    $quiz_id = (int)$_POST['quiz_id'];
    $student_id = (int)$_POST['student_id'];

    // Validate file
    if(isset($_FILES['certificate']) && $_FILES['certificate']['error'] === 0){

        $fileType = mime_content_type($_FILES['certificate']['tmp_name']);

        if($fileType !== 'application/pdf' && $fileType !== 'image/png'){
    $message = "❌ Only PDF or PNG files are allowed!";
} else {

            $folder = "certificates/";

            if(!is_dir($folder)){
                mkdir($folder,0777,true);
            }

            $filename = time() . "_" . basename($_FILES['certificate']['name']);
            $path = $folder . $filename;

            if(move_uploaded_file($_FILES['certificate']['tmp_name'], $path)){

                $check = mysqli_query($conn,"
SELECT id FROM certificates
WHERE quiz_id=$quiz_id AND student_id=$student_id
");

if(mysqli_num_rows($check) > 0){

    $message = "❌ Certificate already exists for this student.";

}else{

    $insert = mysqli_query($conn,"
    INSERT INTO certificates
    (quiz_id, student_id, teacher_id, file_path)
    VALUES
    ($quiz_id, $student_id, $teacher_id, '$path')
    ");

    if($insert){

$res = mysqli_query($conn,"
SELECT name,email FROM students
WHERE id=$student_id
");

$student = mysqli_fetch_assoc($res);

$student_name = $student['name'];
$student_email = $student['email'];

sendCertificateMail($student_email,$student_name,$path);

$message = "✅ Certificate uploaded and emailed to student!";
}else{
        $message = "❌ Database error while uploading certificate.";
    }
}

            } else {
                $message = "❌ File upload failed.";
            }
        }

    } else {
        $message = "❌ Please select a file.";
    }
}

/* ===============================
   FETCH TEACHER QUIZZES
   =============================== */
$quizzes = mysqli_query($conn,"
    SELECT id, title
    FROM quizzes
    WHERE teacher_id=$teacher_id
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Upload Certificate</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
body{font-family:'Segoe UI';background:#f0f2f5;padding:40px}
.container{max-width:600px;margin:auto}
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
    padding:30px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
}
label{font-weight:bold}
select,input{
    width:100%;
    padding:10px;
    margin-top:6px;
    margin-bottom:20px;
    border-radius:6px;
    border:1px solid #ccc;
}
button{
    background:#5d9415;
    color:white;
    padding:10px 18px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
.message{font-weight:bold;margin-top:15px}
</style>
</head>

<body>

<div class="container">
    <a href="teacher_dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="card">
        <h2>Upload Certificate</h2>

        <form method="POST" enctype="multipart/form-data">

<label>Select Quiz</label>
<select name="quiz_id" id="quizSelect" onchange="loadStudents()" required>
    <option value="">-- Select Quiz --</option>
    <?php while($q = mysqli_fetch_assoc($quizzes)): ?>
        <option value="<?= $q['id'] ?>">
            <?= htmlspecialchars($q['title']) ?>
        </option>
    <?php endwhile; ?>
</select>

<label>Select Student</label>
<select name="student_id" id="studentSelect" required>
    <option value="">-- Select Student --</option>
</select>

<label>Upload Certificate (PDF or PNG)</label>
<input type="file" name="certificate" accept=".pdf,.png" required>

<button type="submit" name="upload">Upload Certificate</button>

</form>

<p class="message"><?= $message ?></p>

        </div>
    </div>

<script>
function loadStudents(){

    const quizId = document.getElementById("quizSelect").value;

    if(!quizId) return;

    fetch("fetch_students_for_quiz.php?quiz_id=" + quizId)
    .then(response => response.text())
    .then(data => {
        document.getElementById("studentSelect").innerHTML = data;
    });
}
</script>

</body>
</html>