<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
$live_id = $_GET['live_id'];

$res = mysqli_query($conn, "SELECT l.*, q.title FROM live_quizzes l JOIN quizzes q ON l.quiz_id = q.id WHERE l.id = $live_id");
$data = mysqli_fetch_assoc($res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Lobby</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f4f7f6; text-align: center; padding: 50px; }
        .lobby { background: white; padding: 40px; border-radius: 15px; max-width: 700px; margin: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .code { font-size: 50px; color: #5d9415; font-weight: bold; letter-spacing: 10px; margin: 20px 0; }
        .student-pill { display: inline-block; background: #5A0E24; color: white; padding: 10px 20px; border-radius: 25px; margin: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="lobby">
        <h1><?= htmlspecialchars($data['title']) ?></h1>
        <p>Students, join at <b>QuizLance.com/join</b> with code:</p>
        <div class="code"><?= $data['quiz_code'] ?></div>
        
        <div id="studentContainer" style="margin-top: 30px;">
            <h3>Joined Students (<span id="count">0</span>):</h3>
            <div id="studentList">Waiting for students to join...</div>
        </div>

        <form action="start_live.php" method="POST" style="margin-top: 40px;">
            <input type="hidden" name="live_id" value="<?= $live_id ?>">
            <button type="submit" class="btn-submit" style="background:#5d9415; color:white; padding:15px 40px; border:none; border-radius:8px; cursor:pointer; font-size:18px;">START QUIZ</button>
        </form>
    </div>

    <script>
        function updateLobby() {
            fetch(`fetch_participants.php?live_id=<?= $live_id ?>`)
                .then(r => r.json())
                .then(students => {
                    const list = document.getElementById('studentList');
                    document.getElementById('count').innerText = students.length;
                    if(students.length > 0) {
                        list.innerHTML = students.map(s => `<span class="student-pill"><i class="fas fa-user"></i> ${s.name}</span>`).join('');
                    }
                });
        }
        setInterval(updateLobby, 3000);
        updateLobby();
    </script>
</body>
</html>