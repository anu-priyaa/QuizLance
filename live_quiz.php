<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];

/* ===============================
   1. CREATE LIVE QUIZ
================================= */
if(isset($_POST['create_live'])){
    $quiz_id = intval($_POST['quiz_id']);

    // 🔎 CHECK IF QUIZ HAS ONLY MCQ QUESTIONS
    $type_check = mysqli_query($conn,"SELECT DISTINCT question_type FROM questions WHERE quiz_id=$quiz_id");

    while($row = mysqli_fetch_assoc($type_check)){
        if($row['question_type'] != 'mcq'){
            die("<h3 style='color:red; font-family:Segoe UI; padding:30px;'>Live Quiz supports only MCQ questions. <br><a href='live_quiz.php'>Go Back</a></h3>");
        }
    }

    $quiz_code = strtoupper(substr(md5(time()),0,6));

    mysqli_query($conn,"INSERT INTO live_quizzes (teacher_id, quiz_code, status, quiz_id, current_question) VALUES ($teacher_id,'$quiz_code','waiting',$quiz_id,1)");

    header("Location: live_quiz.php");
    exit();
}

/* ===============================
   2. ASSIGN/CHANGE QUIZ
================================= */
if(isset($_POST['assign_quiz'])){
    $live_id = intval($_POST['live_id']);
    $new_quiz_id = intval($_POST['quiz_id']);

    $type_check = mysqli_query($conn,"SELECT DISTINCT question_type FROM questions WHERE quiz_id=$new_quiz_id");
    while($row = mysqli_fetch_assoc($type_check)){
        if($row['question_type'] != 'mcq'){
            die("<h3 style='color:red; font-family:Segoe UI; padding:30px;'>Live Quiz supports only MCQ questions. <br><a href='live_quiz.php'>Go Back</a></h3>");
        }
    }

    mysqli_query($conn, "UPDATE live_quizzes SET quiz_id=$new_quiz_id WHERE id=$live_id");
    header("Location: live_quiz.php");
    exit();
}

/* ===============================
   3. GET ACTIVE QUIZ
================================= */
$quiz_res = mysqli_query($conn,"SELECT * FROM live_quizzes WHERE teacher_id=$teacher_id AND status!='finished' ORDER BY id DESC LIMIT 1");
$live_quiz = mysqli_fetch_assoc($quiz_res);

/* ===============================
   4. GET TEACHER QUIZZES
================================= */
$quiz_list = mysqli_query($conn,"SELECT id, title FROM quizzes WHERE teacher_id=$teacher_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Quiz Control Panel | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; padding:30px; margin:0;}
        .container { max-width: 1000px; margin: auto; }
        
        /* HEADER SECTION */
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-dash { background: #5A0E24; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-dash:hover { background: #450a1c; }

        .card{background:white; padding:25px; border-radius:12px; margin-bottom:20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: 5px solid #5A0E24;}
        
        button{padding:12px 24px; background:#5A0E24; color:white; border:none; border-radius:6px; cursor: pointer; font-weight: bold; transition: 0.3s;}
        button:hover { opacity: 0.9; transform: translateY(-1px); }
        select{padding:10px; width:100%; max-width: 400px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 15px;}
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-waiting { background: #fff3cd; color: #856404; }
        .status-live { background: #d4edda; color: #155724; }
        
        /* GRID FOR REAL-TIME DATA */
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid-container { grid-template-columns: 1fr; } }

        .data-label { font-weight: bold; color: #5A0E24; margin-bottom: 10px; display: block; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2><i class="fas fa-broadcast-tower"></i> Live Quiz Control Panel</h2>
        <a href="teacher_dashboard.php" class="btn-dash"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if(!$live_quiz): ?>
        <div class="card">
            <h3>Create a New Live Session</h3>
            <p style="color: #666; margin-bottom: 20px;">Choose a quiz to start. Note: Only quizzes with MCQ questions are supported.</p>
            <form method="POST">
                <select name="quiz_id" required>
                    <option value="">-- Choose Quiz --</option>
                    <?php 
                    // Reset pointer and loop
                    mysqli_data_seek($quiz_list, 0);
                    while($q = mysqli_fetch_assoc($quiz_list)): 
                    ?>
                        <option value="<?php echo $q['id']; ?>"><?php echo htmlspecialchars($q['title']); ?></option>
                    <?php endwhile; ?>
                </select>
                <br>
                <button name="create_live">Create Live Quiz</button>
            </form>
        </div>

    <?php else: ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0;">Quiz Code: <span style="font-size: 1.5em; color: #5A0E24;"><?php echo $live_quiz['quiz_code']; ?></span></h3>
                    <p style="margin: 5px 0;">Status: <span class="badge status-<?php echo $live_quiz['status']; ?>"><?php echo $live_quiz['status']; ?></span> | Question: <b>#<?php echo $live_quiz['current_question']; ?></b></p>
                </div>
                
                <?php if($live_quiz['payment_status'] == 'paid'): ?>
                    <div style="display: flex; gap: 10px;">
                        <form method="POST" action="next_live_question.php">
                            <input type="hidden" name="quiz_id" value="<?php echo $live_quiz['id']; ?>">
                            <button name="next" style="background: #5d9415;"><i class="fas fa-step-forward"></i> Next Question</button>
                        </form>
                        <form method="POST" action="end_live_quiz.php">
                            <input type="hidden" name="quiz_id" value="<?php echo $live_quiz['id']; ?>">
                            <button style="background:red;"><i class="fas fa-stop-circle"></i> End Quiz</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($live_quiz['payment_status'] != 'paid'): ?>
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                <div style="text-align: center;">
                    <p><b>Live Quiz Activation Fee: ₹50</b></p>
                    <button id="pay-btn" style="background: #10b981;"><i class="fas fa-lock-open"></i> Pay ₹50 & Start Quiz</button>
                </div>

                <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                <script>
                    var options = {
                        "key": "rzp_test_SNqrmJ00rTQCMt",
                        "amount": "5000",
                        "currency": "INR",
                        "name": "QuizLance",
                        "description": "Live Quiz Activation",
                        "prefill": { "name": "Teacher", "email": "teacher@test.com", "contact": "9999999999" },
                        "theme": { "color": "#5A0E24" },
                        "handler": function (response) {
                            window.location.href = "payment_success.php?quiz_id=<?php echo $live_quiz['id']; ?>";
                        }
                    };
                    var rzp = new Razorpay(options);
                    document.getElementById("pay-btn").onclick = function(e){
                        rzp.open();
                        e.preventDefault();
                    }
                </script>
            <?php endif; ?>
        </div>

        <div class="grid-container">
            <div class="card">
                <span class="data-label"><i class="fas fa-question-circle"></i> Current Question (Teacher View)</span>
                <div id="current_question_teacher">Loading question...</div>
            </div>

            <div class="card">
                <span class="data-label"><i class="fas fa-users"></i> Joined Students</span>
                <div id="students" style="max-height: 200px; overflow-y: auto;">Waiting for students...</div>
            </div>
        </div>

        <div class="card">
            <span class="data-label"><i class="fas fa-trophy"></i> Live Leaderboard</span>
            <div id="leaderboard">No scores yet...</div>
        </div>

        <script>
        function updateLiveData() {
            fetch("teacher_live_data.php?quiz_id=<?php echo $live_quiz['id']; ?>")
            .then(res => res.json())
            .then(data => {
                // Question text
                if(data.current_question && data.current_question.question_text){
                    let qHTML = "<strong>Q" + data.current_question.current_question + ": " + data.current_question.question_text + "</strong>";
                    qHTML += "<br><small style='color:#666'>Marks: " + data.current_question.marks + " | Type: " + data.current_question.question_type + "</small>";
                    document.getElementById("current_question_teacher").innerHTML = qHTML;
                }

                // Students List
                let sHTML = "";
                if(data.students && data.students.length > 0){
                    data.students.forEach(s => {
                        sHTML += "<div style='padding:5px; border-bottom:1px solid #eee;'><i class='fas fa-user-circle' style='color:#5A0E24'></i> " + s.name + "</div>";
                    });
                } else { sHTML = "No students joined yet"; }
                document.getElementById("students").innerHTML = sHTML;

                // Leaderboard
                let lbHTML = "<table style='width:100%; border-collapse: collapse;'>";
                if(data.leaderboard && data.leaderboard.length > 0){
                    data.leaderboard.forEach((p, index) => {
                        lbHTML += `<tr style='border-bottom: 1px solid #eee;'>
                                    <td style='padding:8px;'>#${index+1}</td>
                                    <td style='padding:8px;'>${p.name}</td>
                                    <td style='padding:8px; text-align:right;'><b>${p.score}</b></td>
                                   </tr>`;
                    });
                    lbHTML += "</table>";
                } else { lbHTML = "Waiting for answers..."; }
                document.getElementById("leaderboard").innerHTML = lbHTML;
            })
            .catch(err => console.error("Live Data Error:", err));
        }

        // Run immediately then every 2 seconds
        updateLiveData();
        setInterval(updateLiveData, 2000);
        </script>

    <?php endif; ?>
</div>

</body>
</html>