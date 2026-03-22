<?php
session_start();

// Check teacher role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) {
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kolkata');

/* 1. AUTO UPDATE QUIZ STATUS */
mysqli_query($conn, "UPDATE quizzes SET status='completed' WHERE teacher_id=$teacher_id AND status IN ('scheduled','live') AND end_time < NOW()");
mysqli_query($conn, "UPDATE quizzes SET status='live' WHERE teacher_id=$teacher_id AND status='scheduled' AND start_time <= NOW() AND end_time >= NOW()");

/* 2. DELETE QUIZ LOGIC (FIXED) */
if (isset($_POST['confirm_delete'])) {
    $quiz_id = (int) $_POST['quiz_id'];

    // Verify ownership and status
    $check = mysqli_query($conn, "SELECT status FROM quizzes WHERE id=$quiz_id AND teacher_id=$teacher_id");

    if ($row = mysqli_fetch_assoc($check)) {
        if ($row['status'] === 'draft' || $row['status'] === 'scheduled') {
            
            // Step A: Delete Student Answers (via Attempt ID)
            mysqli_query($conn, "DELETE sa FROM student_answers sa JOIN quiz_attempts qa ON sa.attempt_id = qa.id WHERE qa.quiz_id = $quiz_id");

            // Step B: Delete Quiz Attempts
            mysqli_query($conn, "DELETE FROM quiz_attempts WHERE quiz_id=$quiz_id");

            // --- ADD THIS NEW STEP HERE ---
            // Step B.2: Delete records from results table (Fixes the Foreign Key Error)
            mysqli_query($conn, "DELETE FROM results WHERE quiz_id = $quiz_id");

            // Step C: Delete Question Options & Answers
            mysqli_query($conn, "DELETE qo FROM question_options qo JOIN questions q ON qo.question_id = q.id WHERE q.quiz_id = $quiz_id");
            mysqli_query($conn, "DELETE qa FROM question_answers qa JOIN questions q ON qa.question_id = q.id WHERE q.quiz_id = $quiz_id");

            // Step D: Delete Questions
            mysqli_query($conn, "DELETE FROM questions WHERE quiz_id=$quiz_id");

            // Step E: Delete the Quiz itself
            mysqli_query($conn, "DELETE FROM quizzes WHERE id=$quiz_id");

            $_SESSION['success_msg'] = "Quiz deleted successfully!";
        } else {
            $_SESSION['error_msg'] = "Error: Live or Completed quizzes cannot be deleted.";
        }
    }
    header("Location: scheduled_quizzes.php");
    exit();
}

/* 3. FETCH TEACHER INFO */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Teachers WHERE id=$teacher_id");
$teacher = mysqli_fetch_assoc($res);
$teacher_name = $teacher['name'];
$imgSrc = $teacher['profile_pic'] ? $teacher['profile_pic'] . '?t=' . time() : 'https://via.placeholder.com/85';

/* 4. FETCH ALL QUIZZES */
$quizzes = mysqli_query($conn, "
SELECT DISTINCT q.*, c.class_name, t.name AS created_by
FROM quizzes q
JOIN Classes c ON q.class_id = c.id
JOIN Teachers t ON q.teacher_id = t.id
LEFT JOIN Class_SubTeachers st ON c.id = st.class_id

WHERE
(
    c.teacher_id = $teacher_id   -- class teacher → all quizzes
)
OR
(
    st.teacher_id = $teacher_id  -- sub teacher
    AND q.teacher_id = $teacher_id  -- only their quizzes
)

ORDER BY q.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scheduled Quizzes | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:#f0f2f5; padding-top: 80px;}

        /* TOP BAR */
        .topbar{ position:fixed;top:0;left:0; width:100%;height:60px; background:#5A0E24;color:white; display:flex;align-items:center; padding:0 20px;z-index:1001 }
        .top-profile{ margin-left:auto;display:flex;align-items:center; gap:8px;cursor:pointer;position:relative; }
        .top-profile img{ width:36px;height:36px;border-radius:50%; object-fit:cover;border:2px solid #5d9415; }
        .profile-dropdown{ display:none;position:absolute;right:0;top:55px; background:white;border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.15); min-width:180px; z-index:3000; }
        .profile-dropdown a{ display:flex;align-items:center;gap:10px; padding:12px 15px;text-decoration:none; color:#333;font-size:14px; }
        .profile-dropdown a:hover{background:#f2f2f2;}

        /* MAIN CONTENT */
        .main-content{ max-width: 1100px; margin: auto; padding: 20px; }
        .page-card{ background:white; padding:25px; border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,.05); border-left:5px solid #5d9415; }
        
        /* ALERTS */
        .alert{ padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; animation: fadeOut 5s forwards; }
        .alert-success{ background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error{ background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        @keyframes fadeOut { 0% {opacity:1} 80% {opacity:1} 100% {opacity:0; visibility:hidden} }

        /* TABLE */
        table{width:100%;border-collapse:collapse; margin-top: 20px;}
        th,td{ padding:14px; border-bottom:1px solid #ddd; text-align:left }
        th{background:#5A0E24;color:white}
        .status-draft{color:gray;font-weight:bold}
        .status-scheduled{color:green;font-weight:bold}
        .status-live{color:maroon;font-weight:bold}
        .status-completed{color:#555;font-weight:bold}

        .delete-btn{ background:none; border:none; color:red; font-weight:bold; cursor:pointer }
        .btn-dash { display: inline-block; background: #5A0E24; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-bottom: 20px; }

        /* MODAL */
        .modal{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); justify-content:center; align-items:center; z-index: 9999; }
        .modal-content{ background:white; padding:30px; border-radius:12px; width:350px; text-align:center; }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .confirm{ background:#dc2626; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:bold; }
        .cancel{ background:#eee; color:#333; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:bold; }
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
    <a href="teacher_dashboard.php" class="btn-dash">← Back to Dashboard</a>

    <div class="page-card">
        <h1>Scheduled Quizzes</h1>
        <p>Manage your quizzes below. Only <b>Draft</b> and <b>Scheduled</b> statuses can be deleted.</p>

        <?php if(isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
        <?php endif; ?>

        <table>
            <tr>
                <th>Title</th>
                <th>Class</th>
                <th>Duration</th>
                <th>Status</th>
<th>Created By</th>
<th>Action</th>
            </tr>
            <?php while($q=mysqli_fetch_assoc($quizzes)): ?>
            <tr>
                <td><?= htmlspecialchars($q['title']) ?></td>
                <td><?= htmlspecialchars($q['class_name']) ?></td>
                <td><?= $q['duration'] ?> min</td>
                <td class="status-<?= $q['status'] ?>"><?= ucfirst($q['status']) ?></td>
                <td>
    <?= htmlspecialchars($q['created_by']) ?>

    <?php if($q['teacher_id'] == $teacher_id): ?>
        <span style="color:green;font-size:12px;"> (You)</span>
    <?php endif; ?>
</td>
                <td>
                    <?php if(($q['status']=='draft' || $q['status']=='scheduled') && $q['teacher_id'] == $teacher_id): ?>
                        <button class="delete-btn" onclick="openModal(<?= $q['id'] ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    <?php else: ?>
                        <span style="color: #ccc;">Locked</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<div class="modal" id="deleteModal">
    <div class="modal-content">
        <h3 style="color: #5A0E24;">Delete Quiz?</h3>
        <p style="margin: 10px 0; color: #666;">This will permanently remove the quiz and all its questions. You cannot undo this.</p>
        <form method="POST">
            <input type="hidden" name="quiz_id" id="quiz_id_input">
            <div class="modal-buttons">
                <button type="button" class="cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="confirm_delete" class="confirm">Yes, Delete</button>
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

// Close dropdown on outside click
window.onclick = function(e) {
    if (!e.target.closest('.top-profile')) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
    if (e.target == document.getElementById('deleteModal')) {
        closeModal();
    }
}
</script>

</body>
</html>