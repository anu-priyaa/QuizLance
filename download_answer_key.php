<?php
session_start();
// Force PHP to use IST
date_default_timezone_set('Asia/Kolkata'); 

error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'fpdf/fpdf.php';

// --- Auth & Quiz Check ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access");
}

if (!isset($_GET['quiz_id'])) {
    die("Quiz ID missing");
}

$student_id = (int)$_SESSION['user_id'];
$quiz_id    = (int)$_GET['quiz_id'];

/* ===============================
    FETCH INFO
   =============================== */
$infoQuery = "
    SELECT 
        s.name as student_name, 
        q.title, 
        q.start_time
    FROM quizzes q
    CROSS JOIN Students s 
    WHERE q.id = $quiz_id AND s.id = $student_id
    LIMIT 1
";

$infoRes = mysqli_query($conn, $infoQuery);
$infoRow = mysqli_fetch_assoc($infoRes);

if (!$infoRow) { die("Data not found"); }

$student_name = $infoRow['student_name'];
$quiz_title   = $infoRow['title'];

$conduction_date = !empty($infoRow['start_time']) 
    ? date('d M Y, h:i A', strtotime($infoRow['start_time'])) 
    : 'Not Scheduled';

$pdf = new FPDF();
$pdf->AddPage();

/* HEADER */
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(90, 14, 36); 
$pdf->Cell(0, 12, 'OFFICIAL ANSWER KEY', 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetDrawColor(90, 14, 36);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(8);

/* INFO SECTION */
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);

$pdf->Cell(40, 8, 'Student Name:', 0, 0);
$pdf->Cell(0, 8, utf8_decode($student_name), 0, 1);

$pdf->Cell(40, 8, 'Quiz Title:', 0, 0);
$pdf->Cell(0, 8, utf8_decode($quiz_title), 0, 1);

$pdf->Cell(40, 8, 'Quiz Date (IST):', 0, 0);
$pdf->SetFont('Arial', 'B', 11); 
$pdf->Cell(0, 8, $conduction_date, 0, 1); 

$pdf->Ln(8);

/* QUESTIONS LOOP */
$query = "SELECT id, question_text, question_type, answer_explanation, correct_answer_text, media_path 
          FROM questions WHERE quiz_id = $quiz_id ORDER BY id ASC";
$result = mysqli_query($conn, $query);
$qno = 1;

while ($row = mysqli_fetch_assoc($result)) {
    $qid = $row['id'];
    $q_type = $row['question_type'];
    $media = $row['media_path'];
    
    // Check for page break - increased buffer for images
    if($pdf->GetY() > 200) { $pdf->AddPage(); }

    // 1. Render Question Text
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $q_text = utf8_decode(html_entity_decode($row['question_text']));
    $pdf->MultiCell(0, 9, "Q$qno. " . $q_text, 0, 'L', true);
    $pdf->Ln(2);

    // 2. Render Media (Image or QR) UNDER the text
    if (!empty($media)) {
        $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
        $image_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $image_exts)) {
            if (file_exists($media)) {
                // Image(path, x, y, width, height)
                // Center the image slightly
                $pdf->Image($media, 15, $pdf->GetY(), 40); 
                $pdf->Ln(42); // Move Y down after image
            }
        } else {
            // QR Code for Video/Audio
            $full_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/" . $media;
            $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($full_url);
            
            $pdf->Image($qr_api, 15, $pdf->GetY(), 30, 30, 'PNG');
            $pdf->SetX(15);
            $pdf->SetY($pdf->GetY() + 31);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(30, 5, 'Scan to Play', 0, 1, 'C');
            $pdf->Ln(2);
        }
    }

    // 3. Question Metadata
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, "Type: " . strtoupper(str_replace('_', ' ', $q_type)), 0, 1);
    $pdf->Ln(1);

    $correct_display = "";
    if ($q_type === 'mcq') {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, 'Options:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        $optRes = mysqli_query($conn, $optQuery = "SELECT option_text, is_correct FROM question_options WHERE question_id = $qid");
        while ($opt = mysqli_fetch_assoc($optRes)) {
            $pdf->Cell(10); 
            $pdf->Cell(0, 6, "- " . utf8_decode(html_entity_decode($opt['option_text'])), 0, 1);
            if ($opt['is_correct'] == 1) { $correct_display = $opt['option_text']; }
        }
        $pdf->Ln(2);
    } elseif ($q_type === 'descriptive') {
        $correct_display = 'Manual Grading Required';
    } else {
        $correct_display = !empty($row['correct_answer_text']) ? $row['correct_answer_text'] : "Not specified by teacher";
    }

    /* CORRECT ANSWER SECTION */
    $pdf->SetTextColor(46, 125, 50); 
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(45, 8, 'Correct Answer:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 8, utf8_decode(html_entity_decode($correct_display)), 0, 'L');

    if (!empty($row['answer_explanation'])) {
        $pdf->Ln(1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, 'Explanation:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 6, utf8_decode(html_entity_decode($row['answer_explanation'])));
    }

    $pdf->Ln(4);
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(6);
    
    $qno++;
}

/* FOOTER */
$pdf->SetY(-15);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 10, 'Generated by QuizLance | Page ' . $pdf->PageNo(), 0, 0, 'C');

$pdf->Output('D', 'Answer_Key_' . str_replace(' ', '_', $quiz_title) . '.pdf');
exit;