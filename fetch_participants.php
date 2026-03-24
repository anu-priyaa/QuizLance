<?php
$conn = mysqli_connect("localhost", "root", "", "quizlance");
$live_id = mysqli_real_escape_string($conn, $_GET['live_id']);

/**
 * We join live_participants with Students to get names, 
 * but we only pull participants for the quiz_id linked to this live_id.
 */
$query = "SELECT s.name 
          FROM live_participants lp
          JOIN Students s ON lp.student_id = s.id
          WHERE lp.quiz_id = (SELECT quiz_id FROM live_quizzes WHERE id = '$live_id')";

$res = mysqli_query($conn, $query);
$students = [];

while($row = mysqli_fetch_assoc($res)) {
    $students[] = $row;
}

header('Content-Type: application/json');
echo json_encode($students);
?>