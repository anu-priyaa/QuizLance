<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Join Live Quiz - QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .join-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 400px; }
        .code-input { width: 100%; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 5px; border: 2px solid #5A0E24; border-radius: 8px; margin: 20px 0; text-transform: uppercase; }
        .btn-join { background: #5d9415; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="join-card">
        <h2 style="color: #5A0E24;">Enter Game Code</h2>
        <p>Ask your teacher for the session code to join.</p>
        <form action="join_live_logic.php" method="POST">
            <input type="text" name="quiz_code" class="code-input" placeholder="000000" required maxlength="6">
            <button type="submit" class="btn-join">JOIN QUIZ</button>
        </form>
        <br>
        <a href="student_dashboard.php" style="color: gray; text-decoration: none;">Cancel</a>
    </div>
</body>
</html>