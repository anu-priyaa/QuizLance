<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");

$student_id = $_SESSION['user_id'];

if(isset($_POST['join'])){

    $quiz_code = mysqli_real_escape_string($conn,$_POST['quiz_code']);

    $check = mysqli_query($conn,"
        SELECT * FROM live_quizzes
        WHERE quiz_code='$quiz_code'
        AND status!='finished'
    ");

    if(mysqli_num_rows($check) > 0){

        $quiz = mysqli_fetch_assoc($check);
        $quiz_id = $quiz['id'];

        /* Prevent duplicate join */
        $already = mysqli_query($conn,"
            SELECT * FROM live_participants
            WHERE quiz_id=$quiz_id
            AND student_id=$student_id
        ");

        if(mysqli_num_rows($already) == 0){
            mysqli_query($conn,"
                INSERT INTO live_participants (quiz_id, student_id)
                VALUES ($quiz_id,$student_id)
            ");
        }

        $_SESSION['live_quiz_id'] = $quiz_id;

        header("Location: student_live_waiting.php");

    }else{
        $error = "Invalid or Finished Quiz Code!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Join Live Quiz</title>
<style>
body{font-family:Segoe UI;background:#f5f5f5;padding:40px;text-align:center;}
.card{background:white;padding:30px;border-radius:10px;display:inline-block;}
button{padding:10px 20px;background:#5A0E24;color:white;border:none;border-radius:5px;}
</style>
</head>
<body>

<div class="card">
<h2>Join Live Quiz</h2>

<form method="POST">
    <input type="text" name="quiz_code" placeholder="Enter Quiz Code" required>
    <br><br>
    <button name="join">Join</button>
</form>

<?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>

</div>

</body>
</html>