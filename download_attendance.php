<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access");
}

if (!isset($_GET['quiz_id']) || empty($_GET['quiz_id'])) {
    die("Invalid quiz selected");
}

$quiz_id = intval($_GET['quiz_id']);
$teacher_id = $_SESSION['user_id'];

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

/* VERIFY QUIZ BELONGS TO THIS TEACHER */
$quiz_check = mysqli_query(
    $conn,
    "SELECT title FROM quizzes 
     WHERE id=$quiz_id AND teacher_id=$teacher_id"
);

if (mysqli_num_rows($quiz_check) === 0) {
    die("You are not allowed to download this attendance");
}

$quiz = mysqli_fetch_assoc($quiz_check);
$quiz_title = $quiz['title'];

/* FETCH ATTENDANCE DATA */
$query = "
SELECT 
    s.admission_id,
    s.name,
    s.email
FROM quiz_attempts qa
JOIN students s ON qa.student_id = s.id
WHERE qa.quiz_id = $quiz_id
ORDER BY s.name
";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    die("No students attempted this quiz");
}

/* LOAD FPDF */
require('fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();

/* TITLE */
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Quiz Attendance Report', 0, 1, 'C');

$pdf->Ln(4);

/* QUIZ INFO */
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Quiz Title: ' . $quiz_title, 0, 1);
$pdf->Cell(0, 8, 'Generated On: ' . date("d-m-Y"), 0, 1);

$pdf->Ln(5);

/* TABLE HEADER */
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(45, 10, 'Admission ID', 1);
$pdf->Cell(60, 10, 'Student Name', 1);
$pdf->Cell(80, 10, 'Email', 1);
$pdf->Ln();

/* TABLE BODY */
$pdf->SetFont('Arial', '', 11);

while ($row = mysqli_fetch_assoc($result)) {
    $pdf->Cell(45, 10, $row['admission_id'], 1);
    $pdf->Cell(60, 10, $row['name'], 1);
    $pdf->Cell(80, 10, $row['email'], 1);
    $pdf->Ln();
}

/* OUTPUT PDF */
$fileName = "Attendance_Quiz_" . $quiz_id . ".pdf";
$pdf->Output('D', $fileName);
exit;
