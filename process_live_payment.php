<?php
ob_start();
session_start();

$conn = mysqli_connect("localhost", "root", "", "QuizLance");

header('Content-Type: application/json');

// 1. Get POST Data
$payment_id = mysqli_real_escape_string($conn, $_POST['razorpay_payment_id'] ?? '');
$quiz_id    = intval($_POST['quiz_id'] ?? 0);
$quiz_code  = mysqli_real_escape_string($conn, $_POST['quiz_code'] ?? '');
$teacher_id = $_SESSION['user_id'] ?? 0;

// 2. Validate
if (empty($payment_id) || $quiz_id === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid payment data received.'
    ]);
    exit();
}

// 3. Insert Live Quiz
$sql = "INSERT INTO live_quizzes 
        (quiz_id, teacher_id, join_code, status, payment_id, current_question) 
        VALUES 
        ('$quiz_id', '$teacher_id', '$quiz_code', 'waiting', '$payment_id', 0)";

if (mysqli_query($conn, $sql)) {
    $live_id = mysqli_insert_id($conn);

    ob_clean(); // clean before output

    echo json_encode([
        'success' => true,
        'live_id' => $live_id
    ]);
} else {
    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => mysqli_error($conn)
    ]);
}

exit();
?>