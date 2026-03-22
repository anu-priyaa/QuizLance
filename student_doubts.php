<?php
session_start();
// Database Connection
$conn = mysqli_connect("localhost", "root", "", "QuizLance");

// Check if connection failed
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

/* MARK DOUBTS AS VIEWED (When student opens this page) */
mysqli_query($conn, "UPDATE doubts SET viewed_by_student = 1 WHERE student_id = $student_id AND answer IS NOT NULL AND viewed_by_student = 0");

/* DELETE DOUBT LOGIC (Only if not answered yet) */
if (isset($_POST['delete_doubt'])) {
    $doubt_id = (int)$_POST['doubt_id'];
    $check = mysqli_query($conn, "SELECT id FROM doubts WHERE id=$doubt_id AND student_id=$student_id AND (answer IS NULL OR answer = '')");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM doubts WHERE id=$doubt_id");
        header("Location: student_doubts.php?deleted=1");
        exit();
    }
}

/* POST DOUBT LOGIC */
if (isset($_POST['post_doubt'])) {
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $quiz_id = (int)$_POST['quiz_id'];
    if (!empty(trim($question))) {
        // Status defaults to 'pending'
        mysqli_query($conn, "INSERT INTO doubts (student_id, quiz_id, question, status, created_at) VALUES ($student_id, $quiz_id, '$question', 'pending', NOW())");
        header("Location: student_doubts.php?success=1");
        exit();
    }
}

/* FETCH ALL DOUBTS FOR STUDENT'S CLASSES */
$doubts = mysqli_query($conn, "
    SELECT d.*, s.name AS student_name, q.title, t.name AS teacher_name
    FROM doubts d
    JOIN students s ON d.student_id = s.id
    JOIN quizzes q ON d.quiz_id = q.id
    JOIN teachers t ON q.teacher_id = t.id
    JOIN class_students cs ON q.class_id = cs.class_id
    WHERE cs.student_id = $student_id
    ORDER BY d.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Doubts | QuizLance</title>
    <style>
        :root { --primary: #5A0E24; --bg: #f3f4f6; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); padding: 20px; color: #1e293b; margin: 0; }
        .container { max-width: 850px; margin: auto; }
        
        /* Header Section */
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .back-btn { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; font-size: 14px; }
        .back-btn:hover { background: #450a1b; transform: translateX(-3px); }

        /* Status Alerts */
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

        /* Post New Doubt Box */
        .post-box { background: white; padding: 25px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .post-box h3 { margin-top: 0; color: var(--primary); font-size: 18px; }
        select, textarea { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 12px; font-family: inherit; box-sizing: border-box; font-size: 14px; }
        textarea { height: 100px; resize: none; }
        .btn-post { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%; transition: 0.2s; }
        .btn-post:hover { background: #450a1b; }

        /* Search and Filter UI */
        .search-container { position: relative; margin-bottom: 15px; }
        .search-container input { width: 100%; padding: 14px 14px 14px 45px; border-radius: 12px; border: 1px solid #e2e8f0; box-sizing: border-box; font-size: 15px; outline: none; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 18px; }

        .filter-nav { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-btn { padding: 10px 20px; border-radius: 20px; border: 1px solid #cbd5e1; background: white; cursor: pointer; font-weight: 600; color: #64748b; transition: 0.2s; }
        .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* Doubt Cards Display */
        .doubt-card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); position: relative; border: 1px solid #e2e8f0; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .student { font-weight: 800; color: var(--primary); font-size: 16px; display: flex; align-items: center; }
        .quiz-tag { font-size: 11px; background: #fff1f2; color: var(--primary); padding: 4px 10px; border-radius: 6px; font-weight: 700; margin-left: 12px; text-transform: uppercase; }
        .time { font-size: 12px; color: #94a3b8; margin-top: 10px; font-weight: 500; }
        
        .answer { background: #f0fdf4; padding: 15px; border-left: 4px solid #22c55e; margin-top: 15px; border-radius: 8px; color: #166534; }
        .delete-btn { position: absolute; top: 20px; right: 20px; background: #fee2e2; color: #ef4444; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .delete-btn:hover { background: #fecaca; }

        .no-doubts { text-align: center; padding: 40px; color: #94a3b8; font-weight: 500; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <h2>Class Doubts</h2>
        <a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">✓ Your doubt has been posted successfully!</div>
    <?php endif; ?>
    <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-info">i Doubt deleted successfully.</div>
    <?php endif; ?>

    <div class="post-box">
        <h3>Ask a New Question</h3>
        <form method="POST">
            <select name="quiz_id" required>
                <option value="">Select the Quiz related to your doubt</option>
                <?php
                $quiz_list = mysqli_query($conn,"SELECT q.id, q.title FROM quizzes q JOIN class_students cs ON q.class_id = cs.class_id WHERE cs.student_id = $student_id");
                while($q = mysqli_fetch_assoc($quiz_list)) {
                    echo "<option value='".$q['id']."'>".htmlspecialchars($q['title'])."</option>";
                }
                ?>
            </select>
            <textarea name="question" placeholder="Describe your doubt clearly..." required></textarea>
            <button type="submit" name="post_doubt" class="btn-post">Post Question to Class</button>
        </form>
    </div>

    <div class="search-container">
        <span class="search-icon">🔍</span>
        <input type="text" id="studentSearch" onkeyup="filterDoubts()" placeholder="Search class questions or topics...">
    </div>

    <div class="filter-nav">
        <button class="filter-btn active" onclick="setFilter('all', this)">All Questions</button>
        <button class="filter-btn" onclick="setFilter('mine', this)">My Doubts</button>
        <button class="filter-btn" onclick="setFilter('answered', this)">Answered</button>
    </div>

    <div id="doubtsList">
    <?php 
    if(mysqli_num_rows($doubts) > 0):
        while($d = mysqli_fetch_assoc($doubts)): 
            $has_answer = !empty($d['answer']);
            $is_mine = ($d['student_id'] == $student_id);
    ?>
        <div class="doubt-card" 
             data-mine="<?php echo $is_mine ? 'yes' : 'no'; ?>" 
             data-answered="<?php echo $has_answer ? 'yes' : 'no'; ?>">
            
            <?php if($is_mine && !$has_answer): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this doubt?');">
                    <input type="hidden" name="doubt_id" value="<?php echo $d['id']; ?>">
                    <button type="submit" name="delete_doubt" class="delete-btn">DELETE</button>
                </form>
            <?php endif; ?>

            <div class="student">
                <?php echo $is_mine ? "You" : htmlspecialchars($d['student_name']); ?>
                <span class="quiz-tag"><?php echo htmlspecialchars($d['title']); ?></span>
            </div>
            
            <p style="margin: 15px 0; line-height: 1.6; color: #334155;"><?php echo nl2br(htmlspecialchars($d['question'])); ?></p>
            <div class="time">Asked on <?php echo date('M d, Y | h:i A', strtotime($d['created_at'])); ?></div>

            <?php if($has_answer): ?>
                <div class="answer">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <b style="font-size: 14px; color: #166534;">
                            <?php echo htmlspecialchars($d['teacher_name']); ?> (Teacher)
                        </b>
                        <span style="font-size: 11px; color: #166534; opacity: 0.7; font-weight: 600;">
                            Replied: <?php echo (!empty($d['answered_at'])) ? date('M d, h:i A', strtotime($d['answered_at'])) : "Just now"; ?>
                        </span>
                    </div>
                    <div style="line-height: 1.5; font-size: 14px;">
                        <?php echo nl2br(htmlspecialchars($d['answer'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php 
        endwhile; 
    else:
        echo "<div class='no-doubts'>No doubts found for your classes yet.</div>";
    endif;
    ?>
    </div>
</div>

<script>
let currentFilter = 'all';

// Function to handle tab switching
function setFilter(filter, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = filter;
    filterDoubts();
}

// Main filter and search function
function filterDoubts() {
    const searchVal = document.getElementById('studentSearch').value.toLowerCase();
    const cards = document.querySelectorAll('.doubt-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const text = card.innerText.toLowerCase();
        const isMine = card.getAttribute('data-mine') === 'yes';
        const isAnswered = card.getAttribute('data-answered') === 'yes';
        
        // Logical checks
        const matchesSearch = text.includes(searchVal);
        let matchesTab = false;

        if (currentFilter === 'all') matchesTab = true;
        else if (currentFilter === 'mine') matchesTab = isMine;
        else if (currentFilter === 'answered') matchesTab = isAnswered;

        // Apply display
        if (matchesSearch && matchesTab) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
</body>
</html>