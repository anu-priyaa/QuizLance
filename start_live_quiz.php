<?php
session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");

// Get quiz_id from GET or POST
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : (isset($_POST['quiz_id']) ? $_POST['quiz_id'] : null);

// Validate quiz_id
if (!$quiz_id || !is_numeric($quiz_id)) {
    die("Error: Invalid quiz ID");
}

$quiz_id = intval($quiz_id);

// Update quiz status with prepared statement
$stmt = $conn->prepare("UPDATE live_quizzes SET status='running' WHERE id=?");
$stmt->bind_param("i", $quiz_id);

if ($stmt->execute()) {
    header("Location: live_quiz.php");
} else {
    die("Error updating quiz: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>