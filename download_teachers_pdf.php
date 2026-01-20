<?php
session_start();

/* ADMIN PROTECTION */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* DATABASE */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

/* LOAD FPDF (you already have it) */
require('fpdf/fpdf.php');

/* FETCH TEACHERS */
$result = mysqli_query(
    $conn,
    "SELECT name, username, email, status FROM Teachers ORDER BY id DESC"
);

/* CREATE PDF */
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

/* TITLE */
$pdf->Cell(0, 10, 'Teachers List - QuizLance', 0, 1, 'C');
$pdf->Ln(5);

/* TABLE HEADER */
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(45, 10, 'Name', 1);
$pdf->Cell(45, 10, 'Username', 1);
$pdf->Cell(60, 10, 'Email', 1);
$pdf->Cell(30, 10, 'Status', 1);
$pdf->Ln();

/* TABLE DATA */
$pdf->SetFont('Arial', '', 10);

while ($row = mysqli_fetch_assoc($result)) {
    $pdf->Cell(45, 10, $row['name'], 1);
    $pdf->Cell(45, 10, $row['username'], 1);
    $pdf->Cell(60, 10, $row['email'], 1);
    $pdf->Cell(30, 10, ucfirst($row['status']), 1);
    $pdf->Ln();
}

$pdf->Output();

