<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "quizlance");

if (isset($_GET['q_id']) && isset($_SESSION['user_id']) && isset($_GET['live_id'])) {
    
    $student_id = $_SESSION['user_id'];
    $question_id = mysqli_real_escape_string($conn, $_GET['q_id']);
    $live_id = mysqli_real_escape_string($conn, $_GET['live_id']);
    $option_id = mysqli_real_escape_string($conn, $_GET['choice']);

    // ✅ Get marks
    $q_query = "SELECT marks FROM questions WHERE id = '$question_id'";
    $q_res = mysqli_query($conn, $q_query);
    $q_data = mysqli_fetch_assoc($q_res);

    if (!$q_data) {
        die(json_encode(['status' => 'error', 'message' => 'Question not found']));
    }

    
$check_correct_query = "SELECT is_correct FROM question_options 
                        WHERE id = '$option_id' 
                        AND question_id = '$question_id'";

$check_correct_res = mysqli_query($conn, $check_correct_query);
$check_correct_data = mysqli_fetch_assoc($check_correct_res);

$is_correct = ($check_correct_data && $check_correct_data['is_correct'] == 1) ? 1 : 0;
    // ✅ Assign marks
    $points_to_add = 0;
    if ($is_correct === 1) {
        $points_to_add = (int)$q_data['marks'];
    }

    // 🚫 Prevent duplicate answers
    $check_query = "SELECT * FROM live_answers 
                    WHERE student_id='$student_id' 
                    AND question_id='$question_id' 
                    AND quiz_id='$live_id'";

    $check_res = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_res) > 0) {
        echo json_encode(['status' => 'already_answered']);
        exit();
    }

    // ✅ Insert answer
    $insert_query = "INSERT INTO live_answers 
        (quiz_id, student_id, question_id, selected_option, is_correct) 
        VALUES ('$live_id', '$student_id', '$question_id', '$option_id', '$is_correct')";
    mysqli_query($conn, $insert_query);

    // ✅ Update score
    if ($points_to_add > 0) {
        mysqli_query($conn, "UPDATE live_participants 
            SET score = score + $points_to_add 
            WHERE student_id = '$student_id'
            ORDER BY id DESC 
            LIMIT 1");
    }

    echo json_encode([
        'status' => 'success', 
        'correct' => (bool)$is_correct,
        'added' => $points_to_add
    ]);
}
?>