<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
mysqli_set_charset($conn, "utf8mb4");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $res = mysqli_query($conn, "SELECT * FROM Students WHERE email='$email'");

    if (mysqli_num_rows($res) == 1) {

        // Generate token
        $token  = bin2hex(random_bytes(32));

        // Save token
        mysqli_query($conn,
            "UPDATE Students
             SET reset_token='$token', token_expiry = NOW() + INTERVAL 1 HOUR
             WHERE email='$email'"
        );

        // Ensure token is saved
        if (mysqli_affected_rows($conn) != 1) {
            die("Token could not be stored.");
        }

        // Encode token for URL safety
        $safe_token = urlencode($token);
        $safe_email = urlencode($email);
$reset_link = "http://localhost/QuizLance/reset_password.php?token=$safe_token&email=$safe_email";


        // Send email
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'anupriyaa245@gmail.com';
            $mail->Password = 'xnfplyshkejuehwh'; // app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('anupriyaa245@gmail.com', 'QuizLance');
            $mail->addAddress($email);

            $mail->Subject = 'Reset Password - QuizLance';
            $mail->Body =
                "Hello,\n\n".
                "Click the link below to reset your password:\n\n".
                "$reset_link\n\n".
                "This link is valid for 1 hour.";

            $mail->send();

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Password reset link has been sent to your email.'
            ];

        } catch (Exception $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Email could not be sent.'
            ];
        }

    } else {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Email address not found.'
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QuizLance - Forgot Password</title>
    <style>
        /* RESET */
* {
    box-sizing: border-box;
}

html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #faf6f3, #f3ece8);
    color: #2b2b2b;
}

/* THEME */
:root {
    --primary: #5A0E24;
    --primary-light: #7a1a36;
    --card-bg: #ffffff;
    --text-muted: #6b6b6b;
    --border: #e3d6cf;
}

/* CENTER PAGE */
body {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* CARD */
.form-container {
    max-width: 420px;
    width: 92%;
    background: var(--card-bg);
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.12);
    text-align: center;
}

/* HEADING */
h2 {
    color: var(--primary);
    margin-bottom: 25px;
}

/* INPUT */
input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid var(--border);
    outline: none;
    margin-bottom: 20px;
    font-size: 14px;
}

input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(90, 14, 36, 0.15);
}

/* BUTTON */
button {
    width: 100%;
    padding: 14px;
    border-radius: 30px;
    border: none;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(90, 14, 36, 0.3);
}

button:hover {
    transform: translateY(-2px);
}

/* ALERTS */
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
}

    </style>
</head>
<body>

<div class="form-container">
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert-<?php echo $_SESSION['message']['type']; ?>">
        <?php echo $_SESSION['message']['text']; ?>
    </div>
<?php unset($_SESSION['message']); endif; ?>

<h2>Forgot Password</h2>
<form method="POST">
    <input type="email" name="email" placeholder="Enter registered email" required>
    <button type="submit">Send Reset Link</button>
</form>
</div>

</body>
</html>
