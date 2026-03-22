<?php
session_start();

// Database connection
$conn = mysqli_connect("localhost", "root", "", "QuizLance");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

/* 1. POST ANSWER LOGIC */
if(isset($_POST['reply_btn'])){
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);
    $doubt_id = (int)$_POST['doubt_id'];

    /* 🔒 VERIFY ACCESS */
    $check = mysqli_query($conn,"
        SELECT d.id FROM doubts d
        JOIN quizzes q ON d.quiz_id = q.id
        JOIN Classes c ON q.class_id = c.id
        WHERE d.id = $doubt_id AND (c.teacher_id = $teacher_id OR q.teacher_id = $teacher_id)
    ");

    if(mysqli_num_rows($check) === 0){
        die("Unauthorized reply");
    }

    $query = "UPDATE doubts SET answer='$answer', status='answered', answered_at = NOW(), viewed_by_student = 0 WHERE id=$doubt_id";
    if(mysqli_query($conn, $query)){
        $_SESSION['msg'] = "Reply posted successfully!";
    }

    header("Location: teacher_doubts.php");
    exit();
}

/* 2. DELETE ANSWER LOGIC */
if(isset($_GET['delete_ans_id'])){
    $did = (int)$_GET['delete_ans_id'];

    /* 🔒 VERIFY ACCESS */
    $check = mysqli_query($conn,"
        SELECT d.id FROM doubts d
        JOIN quizzes q ON d.quiz_id = q.id
        JOIN Classes c ON q.class_id = c.id
        WHERE d.id = $did AND d.viewed_by_student = 0 
        AND (c.teacher_id = $teacher_id OR q.teacher_id = $teacher_id)
    ");

    if(mysqli_num_rows($check) === 0){
        die("Unauthorized delete or student already viewed the answer.");
    }

    mysqli_query($conn, "UPDATE doubts SET answer=NULL, status='pending' WHERE id=$did");
    $_SESSION['msg'] = "Response deleted successfully!";
    header("Location: teacher_doubts.php");
    exit();
}

$doubts = mysqli_query($conn, "
    SELECT d.*, s.name AS student_name, q.title
    FROM doubts d
    JOIN students s ON d.student_id = s.id
    JOIN quizzes q ON d.quiz_id = q.id
    JOIN Classes c ON q.class_id = c.id
    WHERE c.teacher_id = $teacher_id OR q.teacher_id = $teacher_id
    ORDER BY d.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Doubts | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #5A0E24; --bg: #f3f4f6; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); padding: 20px; color: #1e293b; margin: 0; }
        .container { max-width: 850px; margin: auto; }
        
        /* Header Section */
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .back-btn { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; font-size: 14px; }
        .back-btn:hover { background: #450a1b; transform: translateX(-3px); }

        /* Success Toast */
        .alert-msg {
            position: fixed; top: 20px; right: 20px; background: #22c55e; color: white;
            padding: 15px 25px; border-radius: 10px; box-shadow: 0 10px 15px rgba(0,0,0,0.1);
            z-index: 2000; font-weight: 500; display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

        /* Search & Filter Bar (Styled like student page) */
        .filter-container { background: white; padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .search-box { position: relative; flex: 2; }
        .search-box input { width: 100%; padding: 12px 12px 12px 40px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 14px; outline: none; box-sizing: border-box; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        
        select { flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 14px; color: #475569; outline: none; background: white; cursor: pointer; }

        /* Doubt Card (Matched to Student UI) */
        .doubt-card { background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; position: relative; }
        
        .student-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .student-name { font-weight: 800; color: var(--primary); font-size: 16px; display: flex; align-items: center; }
        .quiz-tag { font-size: 11px; background: #fff1f2; color: var(--primary); padding: 4px 10px; border-radius: 6px; font-weight: 700; margin-left: 12px; text-transform: uppercase; }
        
        .status-badge { font-size: 11px; padding: 4px 10px; border-radius: 6px; font-weight: 800; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-answered { background: #dcfce7; color: #166534; }

        .question-text { margin: 15px 0; line-height: 1.6; color: #334155; font-size: 15px; background: #f8fafc; padding: 15px; border-radius: 10px; border-left: 4px solid #cbd5e1; }
        .time { font-size: 12px; color: #94a3b8; font-weight: 500; }

        /* Reply Area */
        textarea { width: 100%; height: 100px; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; margin-top: 15px; font-family: inherit; box-sizing: border-box; resize: none; font-size: 14px; }
        .btn-reply { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%; margin-top: 10px; transition: 0.2s; }
        .btn-reply:hover { background: #450a1b; }

        /* Teacher's Existing Response Box */
        .answer-box { background: #f0fdf4; padding: 15px; border-left: 4px solid #22c55e; margin: 15px 0; border-radius: 8px; color: #166534; position: relative; }
        .delete-ans { position: absolute; top: 15px; right: 15px; color: #ef4444; text-decoration: none; font-size: 11px; font-weight: 700; background: white; padding: 5px 10px; border-radius: 5px; border: 1px solid #fee2e2; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 3000; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
        .modal-box { background: white; padding: 30px; border-radius: 15px; max-width: 400px; width: 90%; text-align: center; }
        .btn-confirm { background: #ef4444; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 20px; font-weight: 600; }
        .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; margin-right: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <h2>Student Doubts</h2>
        <a href="teacher_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert-msg">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="filter-container">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" onkeyup="filterDoubts()" placeholder="Search student name or quiz title...">
        </div>
        <select id="statusFilter" onchange="filterDoubts()">
            <option value="all">All Questions</option>
            <option value="pending">Pending</option>
            <option value="answered">Answered</option>
        </select>
    </div>

    <div id="doubtsList">
    <?php if(mysqli_num_rows($doubts) > 0): ?>
        <?php while($d = mysqli_fetch_assoc($doubts)): 
            $is_answered = !empty($d['answer']);
        ?>
            <div class="doubt-card" data-status="<?php echo $is_answered ? 'answered' : 'pending'; ?>">
                <div class="student-header">
                    <div class="student-name">
                        <?php echo htmlspecialchars($d['student_name']); ?>
                        <span class="quiz-tag"><?php echo htmlspecialchars($d['title']); ?></span>
                    </div>
                    <span class="status-badge <?php echo $is_answered ? 'status-answered' : 'status-pending'; ?>">
                        <?php echo $is_answered ? 'ANSWERED' : 'PENDING'; ?>
                    </span>
                </div>

                <div class="question-text">
                    "<?php echo nl2br(htmlspecialchars($d['question'])); ?>"
                </div>

                <div class="time">Asked on <?php echo date('M d, Y | h:i A', strtotime($d['created_at'])); ?></div>

                <?php if($is_answered): ?>
                    <div class="answer-box">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <b style="font-size: 13px;">YOUR RESPONSE</b>
                            <?php if($d['viewed_by_student'] == 0): ?>
                                <a href="javascript:void(0)" onclick="showDeleteModal(<?php echo $d['id']; ?>)" class="delete-ans">DELETE</a>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 14px; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($d['answer'])); ?>
                        </div>
                        <div style="font-size: 11px; margin-top: 8px; opacity: 0.8; font-weight: 600;">
                            REPLIED ON: <?php echo date('M d, h:i A', strtotime($d['answered_at'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="doubt_id" value="<?php echo $d['id']; ?>">
                    <textarea name="answer" placeholder="Type your answer here..." required><?php echo $is_answered ? htmlspecialchars($d['answer']) : ''; ?></textarea>
                    <button type="submit" name="reply_btn" class="btn-reply">
                        <i class="fas fa-paper-plane"></i> <?php echo $is_answered ? 'Update Response' : 'Post Response'; ?>
                    </button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 40px; color: #94a3b8;">No student doubts found.</div>
    <?php endif; ?>
    </div>
</div>

<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Delete Response?</h3>
        <p style="color: #64748b; font-size: 14px;">This will remove your answer and set the doubt back to pending.</p>
        <button class="btn-cancel" onclick="hideDeleteModal()">Cancel</button>
        <a id="confirmDeleteLink" href="#" class="btn-confirm">Yes, Delete</a>
    </div>
</div>

<script>
    function filterDoubts() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const cards = document.querySelectorAll('.doubt-card');

        cards.forEach(card => {
            const text = card.innerText.toLowerCase();
            const cardStatus = card.getAttribute('data-status');
            const matchesSearch = text.includes(search);
            const matchesStatus = (status === 'all' || status === cardStatus);

            card.style.display = (matchesSearch && matchesStatus) ? 'block' : 'none';
        });
    }

    const modal = document.getElementById('deleteModal');
    function showDeleteModal(id) {
        document.getElementById('confirmDeleteLink').href = 'teacher_doubts.php?delete_ans_id=' + id;
        modal.style.display = 'flex';
    }
    function hideDeleteModal() { modal.style.display = 'none'; }
    window.onclick = function(e) { if(e.target == modal) hideDeleteModal(); }

    // Auto-hide success message
    setTimeout(() => {
        const alert = document.querySelector('.alert-msg');
        if(alert) alert.style.opacity = '0';
    }, 3000);
</script>

</body>
</html>