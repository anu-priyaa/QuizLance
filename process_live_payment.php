<?php
ob_start();
session_start();

// Disable error display for the final JSON response
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// 1. Connection - Use the name seen in your phpMyAdmin (quizlance)
$conn = mysqli_connect("localhost", "root", "", "quizlance");

if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit();
}

// 2. Collect Data
$payment_id = $_POST['razorpay_payment_id'] ?? '';
$quiz_id    = intval($_POST['quiz_id'] ?? 0);
$quiz_code  = mysqli_real_escape_string($conn, $_POST['quiz_code'] ?? '');
$teacher_id = $_SESSION['user_id'] ?? 0;

if (!$payment_id || !$quiz_id) {
    echo json_encode(["success" => false, "message" => "Invalid Payment or Quiz ID"]);
    exit();
}

// 3. Insert Query 
// Updated to include 'payment_status' as 'paid' and the new 'payment_id' column
$sql = "INSERT INTO live_quizzes 
        (teacher_id, quiz_id, quiz_code, status, current_question, payment_status, payment_id) 
        VALUES 
        ('$teacher_id', '$quiz_id', '$quiz_code', 'waiting', 0, 'paid', '$payment_id')";

if (mysqli_query($conn, $sql)) {
    $live_id = mysqli_insert_id($conn);
    ob_clean(); 
    echo json_encode([
        "success" => true,
        "live_id" => $live_id
    ]);
} else {
    $error = mysqli_error($conn);
    ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $error
    ]);
}
exit();