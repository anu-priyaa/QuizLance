<?php
$conn = mysqli_connect("localhost", "root", "", "quizlance");
$live_id = mysqli_real_escape_string($conn, $_POST['live_id']);

// Increment the question pointer
mysqli_query($conn, "UPDATE live_quizzes SET current_question = current_question + 1 WHERE id = $live_id");

header("Location: live_host_view.php?live_id=" . $live_id);
exit();
?>