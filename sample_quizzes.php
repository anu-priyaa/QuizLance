<?php
session_start();

/* =========================
   ROLE PROTECTION
   ========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) { die("Database connection failed"); }

$teacher_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kolkata');

/* FETCH TEACHER INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);
$teacher_name = $teacher['name'];
$imgSrc = $teacher['profile_pic'] ? $teacher['profile_pic'] . '?t=' . time() : "https://via.placeholder.com/85";

/* =========================
   DELETE SAMPLE QUIZ LOGIC
   ========================= */
if (isset($_POST['confirm_delete'])) {
    $quiz_id = (int)$_POST['quiz_id'];

    // SECURITY CHECK: Ensure the person deleting is actually the creator
    $check_owner = mysqli_query($conn, "SELECT id FROM sample_quizzes WHERE id=$quiz_id AND teacher_id=$teacher_id");
    
    if (mysqli_num_rows($check_owner) > 0) {
        // Only delete if they own the quiz
        mysqli_query($conn, "DELETE FROM sample_question_options WHERE question_id IN (SELECT id FROM sample_questions WHERE quiz_id=$quiz_id)");
        mysqli_query($conn, "DELETE FROM sample_questions WHERE quiz_id=$quiz_id");
        mysqli_query($conn, "DELETE FROM sample_quizzes WHERE id=$quiz_id");
        $success = "Sample quiz deleted successfully";
    } else {
        $error = "You do not have permission to delete this quiz.";
    }
}

/* =========================
   FETCH QUIZZES LOGIC
   ========================= */
$quizzes = mysqli_query($conn, "
    SELECT DISTINCT q.*, c.class_name, t.name AS created_by
    FROM sample_quizzes q
    JOIN Classes c ON q.class_id = c.id
    JOIN Teachers t ON q.teacher_id = t.id
    LEFT JOIN Class_SubTeachers st ON c.id = st.class_id
    WHERE 
        (c.teacher_id = $teacher_id) -- Case 1: Class Teacher sees EVERYTHING in their class
        OR 
        (st.teacher_id = $teacher_id AND q.teacher_id = $teacher_id) -- Case 2: Sub-teacher sees ONLY their own
    ORDER BY q.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Quizzes | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* [Existing Styles Kept for Brevity] */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
        body { background:#f0f2f5; }
        .topbar { position:fixed; top:0; left:0; width:100%; height:60px; background:#5A0E24; color:white; display:flex; align-items:center; padding:0 20px; z-index:1001; }
        .top-profile { margin-left:auto; display:flex; align-items:center; gap:8px; cursor:pointer; position:relative; }
        .top-profile img { width:36px; height:36px; border-radius:50%; border:2px solid #5d9415; }
        .profile-dropdown { display:none; position:absolute; right:0; top:55px; background:white; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.15); min-width:180px; z-index:3000; }
        .profile-dropdown a { display:flex; align-items:center; gap:10px; padding:12px 15px; text-decoration:none; color:#333; }
        .main-content { padding:80px 40px 40px; }
        .page-card { background:white; padding:20px; border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,.05); border-left:5px solid #5d9415; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { padding:14px; border-bottom:1px solid #ddd; text-align:left; }
        th { background:#5A0E24; color:white; }
        .create-btn { background:#5d9415; color:white; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold; margin-bottom:20px; display:inline-block; }
        .delete-btn { background:none; border:none; color:#e53e3e; font-weight:bold; cursor:pointer; font-size: 14px; }
        .delete-btn:hover { text-decoration: underline; }
        .lock-icon { color: #cbd5e0; font-size: 14px; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px); justify-content: center; align-items: center; z-index: 2000; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; width: 90%; max-width: 400px; text-align: center; }
        .modal-footer { display: flex; gap: 12px; justify-content: center; margin-top: 20px; }
        .btn-modal { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; flex: 1; }
        .btn-confirm { background: #e53e3e; color: white; }
        .btn-cancel { background: #edf2f7; color: #4a5568; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($teacher_name) ?></span>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_teacher.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="main-content">
    <a href="teacher_dashboard.php" class="create-btn" style="background:#5A0E24;">← Back to Dashboard</a>

    <div class="page-card">
        <h1>Sample Quizzes</h1>

        <a href="create_sample_quiz.php" class="create-btn"><i class="fas fa-plus"></i> Create Sample Quiz</a>

        <?php if(isset($success)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Class</th>
                    <th>Created</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($q = mysqli_fetch_assoc($quizzes)): ?>
                <tr>
                    <td>
                        <a href="add_sample_questions.php?quiz_id=<?= $q['id'] ?>" style="text-decoration:none; color:#5A0E24; font-weight:600;">
                            <?= htmlspecialchars($q['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($q['class_name']) ?></td>
                    <td><?= date("d M Y", strtotime($q['created_at'])) ?></td>
                    <td>
                        <?= htmlspecialchars($q['created_by']) ?>
                        <?php if($q['teacher_id'] == $teacher_id): ?>
                            <span style="color:green; font-size:12px;"> (You)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($q['teacher_id'] == $teacher_id): ?>
                            <button class="delete-btn" onclick="openModal(<?= $q['id'] ?>)">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        <?php else: ?>
                            <span class="lock-icon" title="Only the creator can delete"><i class="fas fa-lock"></i> View Only</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div style="color: #e53e3e; font-size: 40px; margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Confirm Deletion</h3>
        <p>This will permanently remove the quiz and all associated questions. Proceed?</p>
        <form method="POST">
            <input type="hidden" name="quiz_id" id="quiz_id_input">
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="confirm_delete" class="btn-modal btn-confirm">Delete Forever</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleProfileMenu(){
        const m = document.getElementById('profileDropdown');
        m.style.display = m.style.display === 'block' ? 'none' : 'block';
    }
    function openModal(id){
        document.getElementById('quiz_id_input').value = id;
        document.getElementById('deleteModal').style.display = 'flex';
    }
    function closeModal(){
        document.getElementById('deleteModal').style.display = 'none';
    }
</script>

</body>
</html>