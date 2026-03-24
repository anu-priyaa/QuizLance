<?php
// get_answer_stats.php
$conn = mysqli_connect("localhost", "root", "", "quizlance");

// Get the parameters sent by the JavaScript fetch()
$live_id = isset($_GET['live_id']) ? mysqli_real_escape_string($conn, $_GET['live_id']) : 0;
$question_id = isset($_GET['question_id']) ? mysqli_real_escape_string($conn, $_GET['question_id']) : 0;

// Count entries for this specific session and question
$query = "SELECT COUNT(*) as total FROM live_answers 
          WHERE quiz_id = '$live_id' AND question_id = '$question_id'";

$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

header('Content-Type: application/json');
echo json_encode(['count' => (int)$data['total']]);
?>