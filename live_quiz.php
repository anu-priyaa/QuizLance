<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
$teacher_id = $_SESSION['user_id'];

// Fetch only quizzes that have MCQ questions
$quiz_query = "SELECT DISTINCT q.id, q.title 
               FROM quizzes q
               JOIN questions qn ON q.id = qn.quiz_id
               WHERE q.teacher_id = $teacher_id 
               AND qn.question_type = 'mcq'"; 
$quizzes_res = mysqli_query($conn, $quiz_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QuizLance - Live Setup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f0f2f5; padding: 80px 20px; }
        .setup-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #5A0E24; }
        .input-field { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; }
        .code-box { background: #f9f9f9; font-family: monospace; font-size: 24px; text-align: center; color: #5d9415; border: 2px dashed #5d9415; }
        .btn-submit { background: #5d9415; color: white; border: none; padding: 15px; border-radius: 8px; width: 100%; font-size: 18px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="setup-card">
    <h2 style="color: #5A0E24; margin-bottom: 20px;"><i class="fas fa-broadcast-tower"></i> Host Live Quiz</h2>
    
    <div class="form-group">
        <label>Select MCQ Quiz</label>
        <select id="quiz_id" class="input-field" required>
            <option value="">-- Choose Quiz --</option>
            <?php while($row = mysqli_fetch_assoc($quizzes_res)): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Join Code</label>
        <?php $token = strtoupper(substr(md5(time().$teacher_id), 0, 6)); ?>
        <input type="text" id="quiz_code" class="input-field code-box" value="<?= $token ?>" readonly>
    </div>

    <button type="button" onclick="payNow()" class="btn-submit" id="pay-button">Pay ₹50 & Open Lobby</button>
</div>

<script>
function payNow() {
    const quizId = document.getElementById('quiz_id').value;
    const quizCode = document.getElementById('quiz_code').value;

    if(!quizId) { alert("Please select a quiz first!"); return; }

    var options = {
        "key": "rzp_test_SUsxwfo6CUHlII", // Enter your Test/Live Key ID
        "amount": "5000", // Amount is in currency subunits (5000 paise = ₹50)
        "currency": "INR",
        "name": "QuizLance",
        "description": "Live Quiz Hosting Fee",
        "image": "https://your-logo-url.com/logo.png",
        "handler": function (response){
            // This code runs after successful payment
            verifyPayment(response, quizId, quizCode);
        },
        "prefill": {
            "name": "Teacher Name", // You can echo PHP session name here
            "email": "teacher@example.com"
        },
        "theme": { "color": "#5A0E24" }
    };
    var rzp1 = new Razorpay(options);
    rzp1.open();
}

function verifyPayment(razorResponse, quizId, quizCode) {
    const formData = new FormData();
    formData.append('razorpay_payment_id', razorResponse.razorpay_payment_id);
    formData.append('quiz_id', quizId);
    formData.append('quiz_code', quizCode);

    fetch('process_live_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log("RESPONSE:", data); // DEBUG

        if (data.success) {
            // ✅ FORCE redirect (VERY IMPORTANT)
            window.location.replace('live_control_panel.php?live_id=' + data.live_id);
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        alert("Something went wrong!");
    });
}
</script>
</body>
</html>