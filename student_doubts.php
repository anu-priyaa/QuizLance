<?php
session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

/* DELETE DOUBT LOGIC */
if(isset($_POST['delete_doubt'])){
    $doubt_id = (int)$_POST['doubt_id'];
    
    // Safety check: Only delete if it belongs to this student AND has no answer
    $check = mysqli_query($conn, "SELECT id FROM doubts WHERE id=$doubt_id AND student_id=$student_id AND (answer IS NULL OR answer = '')");
    
    if(mysqli_num_rows($check) > 0){
        mysqli_query($conn, "DELETE FROM doubts WHERE id=$doubt_id");
        header("Location: student_doubts.php?deleted=1");
        exit();
    }
}

/* POST DOUBT */
if(isset($_POST['post_doubt'])){
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    if(!empty(trim($question))){
        $query = "INSERT INTO doubts (student_id, question, status) VALUES ($student_id, '$question', 'pending')";
        mysqli_query($conn, $query);
        header("Location: student_doubts.php?success=1");
        exit();
    }
}

/* FETCH DOUBTS */
$doubts = mysqli_query($conn,"
    SELECT d.*, s.name 
    FROM doubts d 
    JOIN students s ON d.student_id=s.id 
    ORDER BY d.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Class Doubts</title>
    <style>
        body{ font-family: 'Segoe UI', sans-serif; background:#f3f4f6; padding:40px; }
        .container{ max-width:900px; margin:auto; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        .post-box{ background:white; padding:20px; border-radius:10px; margin-bottom:25px; box-shadow:0 3px 10px rgba(0,0,0,0.05); }
        textarea{ width:100%; height:90px; padding:10px; border-radius:6px; border:1px solid #ccc; resize:none; box-sizing: border-box; }
        
        .btn-post { background:#5A0E24; color:white; border:none; padding:10px 18px; margin-top:10px; border-radius:6px; cursor:pointer; }
        
        .doubt-card{ background:white; padding:18px; border-radius:10px; margin-bottom:15px; box-shadow:0 3px 10px rgba(0,0,0,0.05); position: relative; }
        .student{ font-weight:bold; color:#5A0E24; margin-bottom: 5px; }
        .time{ font-size:12px; color:gray; margin-top: 10px; }
        
        /* Delete Button Style */
        .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff4d4d;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
        }
        .delete-btn:hover { background: #cc0000; }

        .answer{ background:#eef7ee; padding:12px; border-left:4px solid #28a745; margin-top:12px; border-radius:6px; color: #155724; }
        .back-btn{ display:inline-block; background:#5A0E24; color:white; padding:8px 16px; border-radius:6px; text-decoration:none; margin-bottom:20px; font-size:14px; }
    </style>
</head>
<body>

<div class="container">
    <a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>
    <h2>Class Doubts</h2>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">✓ Doubt posted successfully!</div>
    <?php endif; ?>

    <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-info">i Doubt deleted successfully.</div>
    <?php endif; ?>

    <div class="post-box">
        <form method="POST">
            <textarea name="question" placeholder="Ask your doubt..." required></textarea>
            <button type="submit" name="post_doubt" class="btn-post">Post Doubt</button>
        </form>
    </div>

    <?php
    while($d = mysqli_fetch_assoc($doubts)){
        $has_answer = !empty($d['answer']);
        $is_mine = ($d['student_id'] == $student_id);

        echo "<div class='doubt-card'>";
            
            // SHOW DELETE BUTTON ONLY IF: It's my doubt AND teacher hasn't answered
            if($is_mine && !$has_answer){
                echo "<form method='POST' onsubmit='return confirm(\"Are you sure you want to delete this doubt?\");' style='margin:0;'>";
                echo "<input type='hidden' name='doubt_id' value='".$d['id']."'>";
                echo "<button type='submit' name='delete_doubt' class='delete-btn'>Delete</button>";
                echo "</form>";
            }

            $displayName = $is_mine ? "You" : htmlspecialchars($d['name']);
            echo "<div class='student'>$displayName</div>";
            echo "<p>" . nl2br(htmlspecialchars($d['question'])) . "</p>";
            echo "<div class='time'>" . date('M d, Y h:i A', strtotime($d['created_at'])) . "</div>";

            if($has_answer){
                echo "<div class='answer'>";
                    echo "<b>Teacher's Response:</b><br>";
                    echo nl2br(htmlspecialchars($d['answer']));
                echo "</div>";
            }
        echo "</div>";
    }
    ?>
</div>

</body>
</html>