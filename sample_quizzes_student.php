<?php
session_start();

/* =========================
   ROLE PROTECTION
   ========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) { die("Database connection failed"); }

$student_id = $_SESSION['user_id'];

/* =========================
   FETCH STUDENT INFO 
   ========================= */
$res = mysqli_query($conn, "SELECT name, profile_pic FROM Students WHERE id=$student_id");
$student = mysqli_fetch_assoc($res);
$student_name = $student['name'];
$profile_pic  = $student['profile_pic'];
$imgSrc = $profile_pic ? $profile_pic . '?t=' . time() : "https://via.placeholder.com/85";

/* =========================
   FETCH SAMPLE QUIZZES 
   (Updated to include question count)
   ========================= */
$query = "
    SELECT sq.*, t.name AS teacher_name, 
    (SELECT COUNT(*) FROM sample_questions WHERE quiz_id = sq.id) AS q_count
    FROM sample_quizzes sq
    JOIN Teachers t ON sq.teacher_id = t.id
    WHERE sq.status='posted'
    ORDER BY sq.id DESC
";
$quizzes = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Quizzes | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
        body { background:#f0f2f5; color: #333; }

        /* TOPBAR */
        .topbar { position:fixed; top:0; left:0; width:100%; height:60px; background:#5A0E24; color:white; display:flex; align-items:center; padding:0 20px; z-index:1001; }
        .top-profile { margin-left:auto; display:flex; align-items:center; gap:8px; cursor:pointer; position:relative; }
        .top-profile img { width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #5d9415; }
        .profile-dropdown { display:none; position:absolute; right:0; top:55px; background:white; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.15); min-width:180px; z-index: 1002; }
        .profile-dropdown a { display:flex; align-items:center; gap:10px; padding:12px 15px; text-decoration:none; color:#333; transition: 0.2s; }
        .profile-dropdown a:hover { background:#f8f9fa; color: #5d9415; }

        /* MAIN CONTENT */
        .main-content { padding:100px 40px 40px; max-width: 1100px; margin: auto; }
        
        /* BACK BUTTON */
        .back-link { display:inline-block; background:#5A0E24; color:white; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold; margin-bottom:25px; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .back-link:hover { background: #450a1b; transform: translateX(-3px); }

        /* CARD */
        .card { background:white; padding:30px; border-radius:15px; box-shadow:0 4px 12px rgba(0,0,0,.05); border-top: 5px solid #5d9415; }
        .card h2 { color:#5A0E24; margin-bottom:25px; display: flex; align-items: center; gap: 10px; }

        /* TABLE */
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; color:#5A0E24; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; }
        th, td { padding:16px; border-bottom:1px solid #eee; text-align: left; }
        tr:hover { background-color: #fcfdfd; }

        /* BADGES */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #e3f2fd; color: #0d47a1; }
        .q-count { background: #f1f8e9; color: #33691e; }

        /* ATTEMPT BUTTON */
        .attempt-btn { background:#5d9415; color:white; padding:10px 20px; border-radius:50px; text-decoration:none; font-weight:bold; font-size: 14px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .attempt-btn:hover { background: #4a7711; transform: scale(1.05); box-shadow: 0 4px 10px rgba(93, 148, 21, 0.3); }
        .attempt-btn i { font-size: 12px; }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 40px; color: #888; }
        .empty-state i { font-size: 50px; margin-bottom: 15px; color: #ddd; }

    </style>
</head>
<body>

<div class="topbar">
    <div style="font-size: 20px; font-weight: bold; letter-spacing: 1px;">QuizLance</div>
    <div class="top-profile" onclick="toggleProfileMenu()">
        <img src="<?= $imgSrc ?>">
        <span><?= htmlspecialchars($student_name) ?></span>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="profile_student.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="main-content">
    <a href="student_dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="card">
        <h2><i class="fas fa-book-open"></i> Available Sample Quizzes</h2>

        <table>
            <thead>
                <tr>
                    <th>Quiz Title</th>
                    <th>Created By</th>
                    <th>Questions</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($quizzes) == 0): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <p>No sample quizzes have been posted yet. Check back later!</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while($q = mysqli_fetch_assoc($quizzes)): ?>
                        <tr>
                            <td style="font-weight: 600; color: #5A0E24;">
                                <?= htmlspecialchars($q['title']) ?>
                            </td>
                            <td>
                                <i class="fas fa-user-tie" style="color: #888; margin-right: 5px;"></i>
                                <?= htmlspecialchars($q['teacher_name']) ?>
                            </td>
                            <td>
                                <span class="badge q-count">
                                    <?= $q['q_count'] ?> Questions
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a class="attempt-btn" href="attempt_sample_quiz.php?quiz_id=<?= $q['id'] ?>">
                                    Attempt <i class="fas fa-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleProfileMenu() {
    const m = document.getElementById('profileDropdown');
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}

// Close dropdown on outside click
document.addEventListener('click', e => {
    const p = document.querySelector('.top-profile');
    if (p && !p.contains(e.target)) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
});
</script>

<?php if(file_exists('includes/auto_logout.php')) include 'includes/auto_logout.php'; ?>

</body>
</html>