<?php
header('Content-Type: application/json; charset=utf-8');
// Simple availability checker for username/email
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    echo json_encode(['ok'=>false,'error'=>'DB connection failed']);
    exit;
}

$type = isset($_POST['type']) ? $_POST['type'] : '';
$value = isset($_POST['value']) ? $_POST['value'] : '';

if (!$type || !$value) {
    echo json_encode(['ok'=>false,'error'=>'Missing parameters']);
    exit;
}

if ($type === 'username') {
    $val = mysqli_real_escape_string($conn, $value);
    $res = mysqli_query($conn, "SELECT id FROM Students WHERE username='$val' LIMIT 1");
    $taken = ($res && mysqli_num_rows($res) > 0);
    echo json_encode(['ok'=>true,'type'=>'username','available'=>!$taken]);
    exit;
} elseif ($type === 'email') {
    $val = mysqli_real_escape_string($conn, $value);
    $res = mysqli_query($conn, "SELECT id FROM Students WHERE email='$val' LIMIT 1");
    $taken = ($res && mysqli_num_rows($res) > 0);
    echo json_encode(['ok'=>true,'type'=>'email','available'=>!$taken]);
    exit;
} else {
    echo json_encode(['ok'=>false,'error'=>'Invalid type']);
    exit;
}

?>
