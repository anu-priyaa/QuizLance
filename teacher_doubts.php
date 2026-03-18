<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost", "root", "", "QuizLance");

/* 1. POST ANSWER LOGIC */
if(isset($_POST['reply_btn'])){
    $doubt_id = mysqli_real_escape_string($conn, $_POST['doubt_id']);
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);

    $query = "UPDATE doubts SET answer='$answer', status='answered' WHERE id=$doubt_id";
    if(mysqli_query($conn, $query)){
        $_SESSION['msg'] = "Reply posted successfully!";
    }

    header("Location: teacher_doubts.php");
    exit();
}

/* 2. DELETE RESPONSE LOGIC */
if(isset($_GET['delete_ans_id'])){
    $did = (int)$_GET['delete_ans_id'];
    mysqli_query($conn, "UPDATE doubts SET answer=NULL, status='pending' WHERE id=$did");
    $_SESSION['msg'] = "Response deleted successfully!";
    header("Location: teacher_doubts.php");
    exit();
}

/* 3. FETCH DOUBTS */
$doubts = mysqli_query($conn, "SELECT d.*, s.name FROM doubts d JOIN students s ON d.student_id = s.id ORDER BY d.created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Doubts</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; padding: 40px; margin: 0; }
        .container { max-width: 900px; margin: auto; }

        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .btn-back { background: #5A0E24; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; }

        /* Success Message Styling */
        .alert-msg {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
            font-weight: bold;
            text-align: center;
            animation: fadeOut 4s forwards;
        }

        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }

        .doubt-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); border-left: 5px solid #5A0E24; }
        .student { font-weight: bold; color: #5A0E24; font-size: 1.1em; }
        .timestamp { font-size: 0.85em; color: #888; margin-bottom: 10px; display: block; }

        textarea { width: 100%; height: 80px; padding: 12px; border-radius: 6px; border: 1px solid #ccc; margin-top: 10px; box-sizing: border-box; font-family: inherit; }
        .reply-btn { background: #5d9415; color: white; border: none; padding: 10px 20px; margin-top: 8px; border-radius: 6px; cursor: pointer; font-weight: bold; }

        .answer-box { background: #f0fdf4; padding: 15px; margin-top: 15px; border-left: 4px solid #16a34a; border-radius: 6px; color: #166534; position: relative; }
        .btn-delete-ans { position: absolute; top: 10px; right: 15px; color: #dc2626; text-decoration: none; font-size: 11px; font-weight: bold; padding: 4px 8px; border: 1px solid #fecaca; border-radius: 4px; background: white; cursor: pointer; }

        /* Custom Modal Styles (Removes "localhost says") */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-box { background: white; padding: 25px; border-radius: 12px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-box h3 { margin-top: 0; color: #5A0E24; }
        .modal-buttons { margin-top: 20px; display: flex; justify-content: center; gap: 10px; }
        .btn-confirm { background: #dc2626; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration: none; }
        .btn-cancel { background: #eee; color: #333; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <h2>Student Doubts</h2>
        <a href="teacher_dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert-msg">
            <?php 
                echo $_SESSION['msg']; 
                unset($_SESSION['msg']); // Remove after showing once
            ?>
        </div>
    <?php endif; ?>

    <?php while($d = mysqli_fetch_assoc($doubts)): ?>
        <div class="doubt-card">
            <div class="student"><?php echo htmlspecialchars($d['name']); ?></div>
            <span class="timestamp">Posted on: <?php echo date('d M Y, h:i A', strtotime($d['created_at'])); ?></span>
            <p style="color: #444; line-height: 1.5;"><?php echo htmlspecialchars($d['question']); ?></p>

            <?php if(!empty($d['answer'])): ?>
                <div class="answer-box">
                    <b>Your Response:</b><br>
                    <?php echo nl2br(htmlspecialchars($d['answer'])); ?>
                    <button type="button" class="btn-delete-ans" onclick="showDeleteModal(<?php echo $d['id']; ?>)">Delete Response</button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="doubt_id" value="<?php echo $d['id']; ?>">
                <textarea name="answer" placeholder="Type your reply here..." required></textarea>
                <button name="reply_btn" class="reply-btn">Submit Reply</button>
            </form>
        </div>
    <?php endwhile; ?>
</div>

<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Confirm Deletion</h3>
        <p>Do you really want to delete this response?</p>
        <div class="modal-buttons">
            <button class="btn-cancel" onclick="hideDeleteModal()">No, Keep it</button>
            <a id="confirmDeleteLink" href="#" class="btn-confirm">Yes, Delete</a>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('deleteModal');
    const deleteLink = document.getElementById('confirmDeleteLink');

    function showDeleteModal(id) {
        deleteLink.href = 'teacher_doubts.php?delete_ans_id=' + id;
        modal.style.display = 'flex';
    }

    function hideDeleteModal() {
        modal.style.display = 'none';
    }

    // Close modal if user clicks outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            hideDeleteModal();
        }
    }
</script>

</body>
</html>