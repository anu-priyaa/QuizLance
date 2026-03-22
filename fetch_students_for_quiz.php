<?php
$conn = mysqli_connect("localhost","root","","QuizLance");

if(!$conn){
    die("Connection failed");
}

$quiz_id = (int)$_GET['quiz_id'];

$res = mysqli_query($conn,"
    SELECT DISTINCT s.id, s.name
    FROM quiz_attempts qa
    JOIN Students s ON qa.student_id = s.id
    WHERE qa.quiz_id = $quiz_id
");

// Default option
echo '<option value="">-- Select Student --</option>';

while($row = mysqli_fetch_assoc($res)){
    echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
}
?>