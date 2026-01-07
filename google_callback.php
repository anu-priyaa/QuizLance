<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

$conn = mysqli_connect("localhost", "root", "", "QuizLance");

if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error());
}

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            die("Google Auth Error: " . $token['error_description']);
        }

        $client->setAccessToken($token['access_token']);

        $google_oauth = new Google\Service\Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $google_id = $google_account_info->id;
        $full_name = $google_account_info->name;

        // 1. Check Admins and Teachers first
        $roles = ['Admins' => 'admin_dashboard.php', 'Teachers' => 'teacher_dashboard.php'];
        foreach ($roles as $table => $dashboard) {
            $check = mysqli_query($conn, "SELECT * FROM $table WHERE email='$email'");
            if (mysqli_num_rows($check) > 0) {
                $user = mysqli_fetch_assoc($check);
                mysqli_query($conn, "UPDATE $table SET google_id='$google_id' WHERE email='$email'");
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name']; // ADDED: Store Admin/Teacher name
                $_SESSION['role'] = strtolower(rtrim($table, 's'));
                header("Location: $dashboard");
                exit();
            }
        }

        // 2. Check Students Table
        $student_check = mysqli_query($conn, "SELECT * FROM Students WHERE email='$email'");
        if (mysqli_num_rows($student_check) > 0) {
            $user = mysqli_fetch_assoc($student_check);
            mysqli_query($conn, "UPDATE Students SET google_id='$google_id' WHERE email='$email'");
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name']; // ADDED: Store existing student name
            $_SESSION['role'] = 'student';
            header("Location: student_dashboard.php");
            exit();
        } else {
            // 3. NEW USER - Auto-Register as Student with UNIQUE ID
            $username = strstr($email, '@', true); 
            $unique_admission = "G-" . substr($google_id, -5); 

            $sql = "INSERT INTO Students (name, username, email, google_id, admission_id) 
                    VALUES ('$full_name', '$username', '$email', '$google_id', '$unique_admission')";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['user_name'] = $full_name; // ADDED: Store new student name
                $_SESSION['role'] = 'student';
                header("Location: student_dashboard.php");
                exit();
            } else {
                die("Signup Error: " . mysqli_error($conn));
            }
        }

    } catch (Exception $e) {
        die("Exception Error: " . $e->getMessage());
    }
} else {
    header("Location: login.php");
    exit();
}
?>