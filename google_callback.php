<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error());
}

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit();
}

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        die("Google Auth Error: " . $token['error_description']);
    }

    $client->setAccessToken($token['access_token']);

    $google_oauth = new Google\Service\Oauth2($client);
    $info = $google_oauth->userinfo->get();

    $email     = $info->email;
    $google_id = $info->id;
    $full_name = $info->name;

    /* =========================
       ADMIN & TEACHER CHECK
       ========================= */
    $roles = [
        'admins'   => 'admin_dashboard.php',
        'teachers' => 'teacher_dashboard.php'
    ];

    foreach ($roles as $table => $dashboard) {
        $check = mysqli_query($conn, "SELECT * FROM $table WHERE email='$email' LIMIT 1");
        if (mysqli_num_rows($check) === 1) {
            $user = mysqli_fetch_assoc($check);
            mysqli_query($conn, "UPDATE $table SET google_id='$google_id' WHERE id=" . intval($user['id']));

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = rtrim($table, 's');

            header("Location: $dashboard");
            exit();
        }
    }

    /* =========================
       STUDENT CHECK
       ========================= */
    $student_check = mysqli_query($conn, "SELECT * FROM students WHERE email='$email' LIMIT 1");
    if (mysqli_num_rows($student_check) === 1) {
        $user = mysqli_fetch_assoc($student_check);
        mysqli_query($conn, "UPDATE students SET google_id='$google_id' WHERE id=" . intval($user['id']));

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = 'student';

        header("Location: student_dashboard.php");
        exit();
    }

    /* =========================
       NEW STUDENT AUTO-REGISTER
       ========================= */
    $username = strstr($email, '@', true);
    $admission_id = 'G-' . substr($google_id, -5);

    $insert = mysqli_query(
        $conn,
        "INSERT INTO students (name, username, email, google_id, admission_id)
         VALUES ('$full_name', '$username', '$email', '$google_id', '$admission_id')"
    );

    if (!$insert) {
        die("Signup Error: " . mysqli_error($conn));
    }

    $_SESSION['user_id']   = mysqli_insert_id($conn);
    $_SESSION['user_name'] = $full_name;
    $_SESSION['role']      = 'student';

    header("Location: student_dashboard.php");
    exit();

} catch (Exception $e) {
    die("Exception Error: " . $e->getMessage());
}
