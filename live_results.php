<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "quizlance");
$live_id = mysqli_real_escape_string($conn, $_GET['live_id'] ?? 0);

// Fetch Title
$title_query = "SELECT q.title FROM live_quizzes lq JOIN quizzes q ON lq.quiz_id = q.id WHERE lq.id = '$live_id'";
$title_res = mysqli_query($conn, $title_query);
$quiz_info = mysqli_fetch_assoc($title_res);

$query = "SELECT s.name, SUM(q.marks) AS score
          FROM live_answers la
          INNER JOIN Students s ON la.student_id = s.id
          INNER JOIN questions q ON la.question_id = q.id
          WHERE la.is_correct = 1
          GROUP BY la.student_id
          ORDER BY score DESC LIMIT 10";

$res = mysqli_query($conn, $query);

// Determine Dashboard Link
$dashboard_url = ($_SESSION['role'] === 'teacher') ? 'teacher_dashboard.php' : 'student_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Final Standings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #5A0E24; color: white; text-align: center; padding: 50px; }
        .podium { background: white; color: #333; max-width: 500px; margin: 20px auto; padding: 30px; border-radius: 15px; }
        .rank-item { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #eee; }
        .btn-exit { display: inline-block; margin-top: 25px; padding: 12px 25px; background: #5d9415; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <i class="fas fa-trophy" style="font-size: 70px; color: #d4af37;"></i>
    <h1>Final Standings</h1>
    <p><?= htmlspecialchars($quiz_info['title'] ?? 'Live Quiz') ?></p>
    
    <div class="podium">
        <?php 
        $rank = 1;
        if(mysqli_num_rows($res) > 0):
            while($row = mysqli_fetch_assoc($res)): ?>
                <div class="rank-item">
                    <span>#<?= $rank ?> <?= htmlspecialchars($row['name']) ?></span>
                    <span><?= number_format($row['score']) ?> pts</span>
                </div>
            <?php $rank++; endwhile; 
        else: ?>
            <p>No scores recorded.</p>
        <?php endif; ?>
        
        <a href="<?= $dashboard_url ?>" class="btn-exit">Back to Dashboard</a>
    </div>
</body>
</html>