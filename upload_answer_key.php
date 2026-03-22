<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");

$teacher_id = $_SESSION['user_id'];

if(isset($_POST['upload'])){

    $quiz_id = (int)$_POST['quiz_id'];

    if(isset($_FILES['answer_key']) && $_FILES['answer_key']['error']==0){

        $dir = "uploads/answer_keys/";

        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }

        $filename = time()."_".basename($_FILES['answer_key']['name']);
        $path = $dir.$filename;

        move_uploaded_file($_FILES['answer_key']['tmp_name'],$path);

        mysqli_query($conn,"
            UPDATE quizzes
            SET answer_key_file='$path'
            WHERE id=$quiz_id
        ");

        $msg = "Answer key uploaded successfully!";
    }
}

$quizRes = mysqli_query($conn,"
SELECT id,title,answer_key_file
FROM quizzes
WHERE teacher_id=$teacher_id
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Upload Answer Key</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

body{
    background:#f0f2f5;
    font-family:'Segoe UI',sans-serif;
    padding:40px;
}

.card{
    background:white;
    max-width:600px;
    margin:auto;
    padding:35px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
    border-left:5px solid #5d9415;
}

h2{
    color:#5A0E24;
    margin-bottom:25px;
}

label{
    font-weight:600;
    display:block;
    margin-top:15px;
}

select,input[type=file]{
    width:100%;
    padding:10px;
    margin-top:6px;
    border-radius:6px;
    border:1px solid #ccc;
}

.btn{
    margin-top:20px;
    background:#5d9415;
    color:white;
    padding:12px 20px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}

.msg{
    background:#e8f5e9;
    padding:12px;
    margin-bottom:20px;
    border-radius:6px;
    color:#2e7d32;
}

.back{
    display:inline-block;
    margin-top:20px;
    text-decoration:none;
    color:white;
    background:#5A0E24;
    padding:10px 18px;
    border-radius:6px;
}

</style>
</head>

<body>

<div class="card">

<h2><i class="fas fa-file-upload"></i> Upload Answer Key</h2>

<?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

<form method="POST" enctype="multipart/form-data">

<label>Select Quiz</label>

<select name="quiz_id" required>

<option value="">Select Quiz</option>

<?php while($q=mysqli_fetch_assoc($quizRes)): ?>

<option value="<?= $q['id'] ?>">
<?= htmlspecialchars($q['title']) ?>
<?= !empty($q['answer_key_file']) ? ' (Uploaded)' : '' ?>
</option>

<?php endwhile; ?>

</select>

<div id="existingFile" style="margin-top:10px; font-size:14px; color:#555;"></div>
<label>Upload Answer Key (PDF)</label>

<input type="file" name="answer_key" accept=".pdf" required>

<button type="submit" name="upload" class="btn">
Upload Answer Key
</button>

</form>

<a href="teacher_dashboard.php" class="back">
← Back to Dashboard
</a>

</div>

<script>
const quizData = <?php
    mysqli_data_seek($quizRes, 0);
    $data = [];
    while($q = mysqli_fetch_assoc($quizRes)){
        $data[$q['id']] = $q['answer_key_file'];
    }
    echo json_encode($data);
?>;

function showExistingFile(){
    const select = document.querySelector("select[name='quiz_id']");
    const fileDiv = document.getElementById("existingFile");

    const quizId = select.value;

    if(quizData[quizId]){
        fileDiv.innerHTML = `
            <strong>Existing File:</strong> 
            <a href="${quizData[quizId]}" target="_blank">View Answer Key</a>
        `;
    } else {
        fileDiv.innerHTML = "";
    }
}

document.querySelector("select[name='quiz_id']")
        .addEventListener("change", showExistingFile);
</script>

</body>
</html>