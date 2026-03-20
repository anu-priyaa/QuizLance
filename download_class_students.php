<?php
session_start();

/* TEACHER PROTECTION */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['class_id'])) {
    die("Class ID missing");
}

/* DATABASE */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];
$class_id   = (int)$_GET['class_id'];

$classRes = mysqli_query(
    $conn,
    "SELECT c.class_name
     FROM Classes c
     LEFT JOIN Class_SubTeachers s
     ON c.id = s.class_id
     WHERE c.id = $class_id
     AND c.status = 'active'
     AND (
         c.teacher_id = $teacher_id
         OR s.teacher_id = $teacher_id
     )"
);

if (mysqli_num_rows($classRes) === 0) {
    die("Unauthorized class access");
}

$class = mysqli_fetch_assoc($classRes);
$class_name = $class['class_name'];

/* FETCH STUDENTS IN THIS CLASS */
$result = mysqli_query(
    $conn,
    "SELECT s.name, s.username, s.admission_id, s.email
     FROM class_students cs
     JOIN Students s ON cs.student_id = s.id
     WHERE cs.class_id = $class_id
     ORDER BY s.name"
);

/* LOAD FPDF */
require('fpdf/fpdf.php');

/* CREATE PDF */
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

/* TITLE */
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Student List - '.$class_name, 0, 1, 'C');
$pdf->Ln(5);

/* TABLE HEADER */
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(40, 10, 'Name', 1);
$pdf->Cell(35, 10, 'Username', 1);
$pdf->Cell(35, 10, 'Admission ID', 1);
$pdf->Cell(60, 10, 'Email', 1);
$pdf->Ln();

/* TABLE DATA */
$pdf->SetFont('Arial', '', 10);

while ($row = mysqli_fetch_assoc($result)) {
    $pdf->Cell(40, 10, $row['name'], 1);
    $pdf->Cell(35, 10, $row['username'], 1);
    $pdf->Cell(35, 10, $row['admission_id'], 1);
    $pdf->Cell(60, 10, $row['email'], 1);
    $pdf->Ln();
}

/* DOWNLOAD */
$pdf->Output('D', 'Student_List_'.$class_name.'.pdf');
exit;
