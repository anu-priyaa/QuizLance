<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "quizlance");

if(isset($_POST['quiz_code']) && isset($_SESSION['user_id'])) {
    $code = mysqli_real_escape_string($conn, $_POST['quiz_code']);
    $student_id = $_SESSION['user_id'];

    // Find the live session by code
    $res = mysqli_query($conn, "SELECT id, quiz_id FROM live_quizzes WHERE quiz_code = '$code'");
    $session = mysqli_fetch_assoc($res);

    if($session) {
        $live_id = $session['id'];
        $quiz_id = $session['quiz_id'];

        // Add the student to the participants table with a fresh score of 0
        // Use INSERT IGNORE to prevent errors if they refresh the page
        mysqli_query($conn, "INSERT INTO live_participants (quiz_id, student_id, score)
VALUES ('$quiz_id', '$student_id', 0)
ON DUPLICATE KEY UPDATE score = score;");

        header("Location: student_live_screen.php?live_id=" . $live_id);
    } else {
        echo "Invalid Code or Quiz already started.";
    }
}
?>