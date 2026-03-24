<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "quizlance");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $live_id = mysqli_real_escape_string($conn, $_POST['live_id']);

    // 1. Get the Quiz ID associated with this Live Session
    $res = mysqli_query($conn, "SELECT quiz_id FROM live_quizzes WHERE id = '$live_id'");
    $session = mysqli_fetch_assoc($res);
    $quiz_id = $session['quiz_id'];

    // 2. RESET STEP: Delete all previous participants for this Quiz Template
    // This ensures names start at 0 and scores are fresh
    mysqli_query($conn, "DELETE FROM live_participants WHERE quiz_id = '$quiz_id'");

    // 3. Set the quiz to start at question 1 and status 'running'
    mysqli_query($conn, "UPDATE live_quizzes SET status = 'running', current_question = 1 WHERE id = '$live_id'");

    // Redirect to the host view
    header("Location: live_host_view.php?live_id=" . $live_id);
    exit();
}
?>