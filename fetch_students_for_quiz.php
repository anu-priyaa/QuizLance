<?php
$conn = mysqli_connect("localhost","root","","QuizLance");

if(!$conn){
    die("Connection failed");
}

// Security: Ensure quiz_id exists before using it
if(isset($_GET['quiz_id']) && !empty($_GET['quiz_id'])) {
    
    $quiz_id = (int)$_GET['quiz_id'];

    // Added qa.status = 'submitted' so only students who finished get a certificate
    $res = mysqli_query($conn,"
        SELECT DISTINCT s.id, s.name
        FROM quiz_attempts qa
        JOIN Students s ON qa.student_id = s.id
        WHERE qa.quiz_id = $quiz_id 
        AND qa.status = 'submitted'
    ");

    echo '<option value="">-- Select Student --</option>';

    if(mysqli_num_rows($res) > 0) {
        while($row = mysqli_fetch_assoc($res)){
            echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['name']).'</option>';
        }
    } else {
        echo '<option value="">No students have submitted this quiz yet</option>';
    }
} else {
    echo '<option value="">-- Select Student --</option>';
}
?>