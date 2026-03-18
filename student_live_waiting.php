<?php
session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");

if(!isset($_SESSION['live_quiz_id'])){
    header("Location: student_dashboard.php");
    exit();
}

$quiz_id = $_SESSION['live_quiz_id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Waiting for Quiz</title>
</head>
<body style="text-align:center;font-family:Segoe UI">

<h2>Waiting for Teacher to Start the Quiz...</h2>

<script>
setInterval(function(){

fetch("check_live_status.php?quiz_id=<?php echo $quiz_id; ?>")
.then(res=>res.json())
.then(data=>{

    if(data.status === "running"){
        window.location.href = "student_live_quiz.php";
    }

    if(data.status === "finished"){
        document.body.innerHTML = "<h2>Quiz Finished</h2>";
    }

});

},2000);
</script>

</body>
</html>