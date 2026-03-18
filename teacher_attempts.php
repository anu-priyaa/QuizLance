<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized");
}

$conn = mysqli_connect("localhost","root","","QuizLance");

$teacher_id = $_SESSION['user_id'];

// Get selected quiz from GET or POST
$selected_quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : null;

// Fetch only quizzes with descriptive questions by this teacher
$quizzes = mysqli_query($conn,"
    SELECT DISTINCT q.id, q.title 
    FROM quizzes q
    INNER JOIN questions que ON q.id = que.quiz_id
    WHERE q.teacher_id = $teacher_id 
    AND que.question_type = 'descriptive'
    ORDER BY q.id DESC
");

// Fetch student attempts with descriptive answers when a quiz is selected
$attempts = null;
if ($selected_quiz_id) {
    $attempts = mysqli_query($conn,"
        SELECT DISTINCT qa.id, qa.submitted_at, qa.student_id,
               q.title,
               s.name as student_name
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        JOIN Students s ON qa.student_id = s.id
        JOIN questions que ON q.id = que.quiz_id
        WHERE q.teacher_id = $teacher_id
        AND q.id = $selected_quiz_id
        AND que.question_type = 'descriptive'
        AND qa.status = 'submitted'
        ORDER BY qa.submitted_at DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Attempts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f0f2f5;
            padding-top: 60px;
        }

        /* ===== TOP BAR ===== */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: #5A0E24;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 1001;
        }

        .topbar h1 {
            font-size: 20px;
            font-weight: 600;
        }

        /* ===== MAIN CONTENT ===== */
        .container {
            max-width: 1200px;
            margin: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h2 {
            color: #5A0E24;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 3px solid #5d9415;
            padding-bottom: 10px;
        }

        /* ===== QUIZ SELECTOR ===== */
        .quiz-selector {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .quiz-selector label {
            display: block;
            margin-bottom: 10px;
            color: #5A0E24;
            font-weight: 600;
        }

        .quiz-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #5A0E24;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            color: #333;
        }

        .quiz-selector select:focus {
            outline: none;
            border-color: #5d9415;
            box-shadow: 0 0 5px rgba(93, 148, 21, 0.3);
        }

        /* ===== STUDENT ATTEMPTS TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead th {
            background: #5A0E24;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border: none;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background-color: #f9f9f9;
        }

        tbody td {
            padding: 15px;
            color: #555;
        }

        tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        a.btn {
            display: inline-block;
            background: #5A0E24;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #5A0E24;
            cursor: pointer;
        }

        a.btn:hover {
            background: #5d9415;
            border-color: #5d9415;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #5A0E24;
            color: white;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5d9415;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 16px;
        }

        /* ===== STUDENT CARD STYLES ===== */
        .student-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .student-header {
            padding: 15px 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            border-bottom: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .student-header:hover {
            background: #efefef;
        }

        .student-header.active {
            background: #5A0E24;
            color: white;
            border-bottom-color: #5d9415;
        }

        .student-info {
            display: flex;
            gap: 20px;
            align-items: center;
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: inherit;
            font-size: 15px;
        }

        .student-date {
            color: inherit;
            font-size: 13px;
            opacity: 0.8;
        }

        .student-header.active .student-date {
            color: #5d9415;
        }

        .toggle-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .student-header.active .toggle-icon {
            transform: rotate(180deg);
        }

        /* ANSWER CONTENT */
        .student-answers {
            display: none;
            padding: 20px;
            background: #f9f9f9;
            border-top: 2px solid #e0e0e0;
        }

        .student-answers.active {
            display: block;
        }

        .answer-item {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #5A0E24;
        }

        .answer-item:last-child {
            margin-bottom: 0;
        }

        .answer-question {
            color: #5A0E24;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .answer-marks {
            display: inline-block;
            background: #5A0E24;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 8px;
            font-weight: 600;
        }

        .answer-text {
            background: white;
            padding: 12px;
            border-radius: 5px;
            color: #555;
            line-height: 1.6;
            font-size: 13px;
            margin-top: 8px;
            border: 1px solid #e0e0e0;
        }

        .evaluate-btn {
            display: inline-block;
            background: #5A0E24;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            margin-top: 10px;
            transition: all 0.3s ease;
            border: 2px solid #5A0E24;
        }

        .evaluate-btn:hover {
            background: #5d9415;
            border-color: #5d9415;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 20px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 15px;
            }

            table {
                font-size: 14px;
            }

            thead th,
            tbody td {
                padding: 10px;
            }

            a.btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- TOP BAR -->
    <div class="topbar">
        <h1>QuizLance</h1>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <a href="teacher_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <h2>Evaluate Student Attempts</h2>

        <!-- QUIZ SELECTOR -->
        <div class="quiz-selector">
            <form method="GET">
                <label for="quiz_id">Select Quiz:</label>
                <select name="quiz_id" id="quiz_id" onchange="this.form.submit()">
                    <option value="">-- Choose a Quiz --</option>
                    <?php 
                    while($quiz = mysqli_fetch_assoc($quizzes)): 
                    ?>
                    <option value="<?= $quiz['id'] ?>" <?= $selected_quiz_id == $quiz['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($quiz['title']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <!-- STUDENT ATTEMPTS CARDS -->
        <?php if ($selected_quiz_id && $attempts): ?>
            <div style="margin-top: 25px;">
                <?php 
                $hasData = false;
                while($row = mysqli_fetch_assoc($attempts)): 
                    $hasData = true;
                    $attempt_id = $row['id'];
                    
                    // Fetch descriptive answers for this student
                    $answersRes = mysqli_query($conn,"
                        SELECT sa.*, q.question_text, q.marks
                        FROM student_answers sa
                        JOIN questions q ON sa.question_id = q.id
                        WHERE sa.attempt_id = $attempt_id
                        AND q.question_type = 'descriptive'
                        ORDER BY q.id ASC
                    ");
                ?>
                <div class="student-card">
                    <div class="student-header" onclick="toggleStudentAnswers(this)">
                        <div class="student-info">
                            <div>
                                <div class="student-name">
                                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($row['student_name']) ?>
                                </div>
                                <div class="student-date">
                                    <i class="fas fa-calendar"></i> <?= date("d M Y, h:i A", strtotime($row['submitted_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>

                    <div class="student-answers">
                        <?php while($answer = mysqli_fetch_assoc($answersRes)): ?>
                            <div class="answer-item">
                                <div class="answer-question">
                                    <?= htmlspecialchars($answer['question_text']) ?>
                                    <span class="answer-marks"><?= $answer['marks'] ?> marks</span>
                                </div>
                                <div class="answer-text">
                                    <?= nl2br(htmlspecialchars($answer['answer_text'])) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <a href="evaluate_attempt.php?attempt_id=<?= $attempt_id ?>" class="evaluate-btn">
                            <i class="fas fa-edit"></i> Grade this Attempt
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>

                <?php if (!$hasData): ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p>No submitted attempts with descriptive questions for this quiz yet.</p>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($selected_quiz_id): ?>
            <div class="no-data">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>No submitted attempts with descriptive questions for this quiz.</p>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-chevron-down" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>Select a descriptive quiz above to view student answers</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
    function toggleStudentAnswers(headerElement) {
        const card = headerElement.closest('.student-card');
        const answersDiv = card.querySelector('.student-answers');
        
        // Toggle active class on header
        headerElement.classList.toggle('active');
        
        // Toggle active class on answers
        answersDiv.classList.toggle('active');
        
        // Smooth scroll into view
        if (answersDiv.classList.contains('active')) {
            setTimeout(() => {
                headerElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
    }
</script>