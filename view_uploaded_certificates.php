<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");
if(!$conn){
    die("Database connection failed");
}

$teacher_id = $_SESSION['user_id'];
$message = "";

// 🔍 Search inputs (PHP Server-side)
$search_student = isset($_GET['student']) ? trim($_GET['student']) : "";
$search_quiz = isset($_GET['quiz']) ? trim($_GET['quiz']) : "";

// Check if teacher is class teacher
$check = mysqli_query($conn, "SELECT * FROM class_subteachers WHERE teacher_id = $teacher_id");
$is_class_teacher = (mysqli_num_rows($check) == 0);

// Fetch students list
$students_list = mysqli_query($conn, "SELECT id, name FROM Students");

// Fetch quizzes list
if($is_class_teacher){
    $quizzes_list = mysqli_query($conn, "SELECT id, title FROM quizzes");
}else{
    $quizzes_list = mysqli_query($conn, "SELECT id, title FROM quizzes WHERE teacher_id = $teacher_id");
}

/* ===============================
    DELETE LOGIC
   =============================== */
if(isset($_GET['delete'])){
    $cert_id = (int)$_GET['delete'];
    $res = mysqli_query($conn,"SELECT file_path FROM certificates WHERE id=$cert_id AND teacher_id=$teacher_id");

    if(mysqli_num_rows($res) > 0){
        $row = mysqli_fetch_assoc($res);
        $file = $row['file_path'];
        if(file_exists($file)){ unlink($file); }
        mysqli_query($conn,"DELETE FROM certificates WHERE id=$cert_id");
        $message = "Certificate deleted successfully.";
    }
}

// SQL Query building
$query = "
    SELECT c.*, q.title, s.name AS student_name, t.name AS teacher_name
    FROM certificates c
    JOIN quizzes q ON c.quiz_id = q.id
    JOIN Students s ON c.student_id = s.id
    JOIN teachers t ON c.teacher_id = t.id
    WHERE 1
";

if(!$is_class_teacher){ $query .= " AND c.teacher_id = $teacher_id"; }

if(!empty($search_student)){
    $search_student_sql = mysqli_real_escape_string($conn, $search_student);
    $query .= " AND s.name = '$search_student_sql'";
}
if(!empty($search_quiz)){
    $search_quiz_sql = mysqli_real_escape_string($conn, $search_quiz);
    $query .= " AND q.title = '$search_quiz_sql'";
}

$query .= " ORDER BY c.uploaded_at DESC";
$certificates = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates | QuizLance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #5A0E24;
            --secondary: #5d9415;
            --danger: #e74c3c;
            --bg: #f8f9fa;
            --white: #ffffff;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg); 
            padding: 40px 20px; 
            color: #333; 
            margin: 0;
        }

        select {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    background: #fff;
    font-size: 14px;
    outline: none;
    transition: 0.3s;
}

select:focus {
    border-color: #5d9415;
}

        .container { max-width: 1100px; margin: 0 auto; }

        /* Search Bar & Button Styling */
        .search-bar {
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            align-items: center;
        }

        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            transition: border 0.3s;
        }

        .search-bar input:focus { border-color: var(--primary); }

        .search-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .search-btn:hover { background: #4a7a11; }

        /* Table Card */
        .card { background: var(--white); border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); overflow: hidden; }
        
        table { width: 100%; border-collapse: collapse; }
        
        th { 
            background: #fafafa; 
            padding: 18px; 
            text-align: left; 
            font-size: 12px; 
            color: #888; 
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #eee; 
        }

        td { padding: 18px; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; }
        tr:hover { background: #fcfcfc; }

        /* Action Buttons */
        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-view { background: #ebf5e0; color: var(--secondary); border: 1px solid #d4e7bc; }
        .btn-view:hover { background: var(--secondary); color: white; }
        
        
        .btn-delete { 
            color: #bbb; 
            margin-left: 15px; 
            text-decoration: none;
            transition: color 0.2s;
        }
        .btn-delete:hover { color: var(--danger); }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #28a745;
        }

        .back-link { 
            color: var(--primary); 
            text-decoration: none; 
            font-weight: bold; 
            margin-bottom: 20px; 
            display: inline-block; 
        }
        .back-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .search-bar { flex-direction: column; }
            .search-btn { width: 100%; justify-content: center; }
        }

        .back-btn {
    display: inline-block;
    background: #5A0E24;
    color: white;
    padding: 10px 18px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    margin-bottom: 20px;
}

.back-btn:hover {
    background: #4a0c1d;
}
    </style>
</head>
<body>

<div class="container">
    <a href="teacher_dashboard.php" class="back-btn">
    ← Back to Dashboard
</a>

    <?php if($message): ?>
        <div class="message"><i class="fas fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>

    <form method="GET" class="search-bar">
        <select name="student">
    <option value="">-- Choose Student --</option>
    <?php while($s = mysqli_fetch_assoc($students_list)): ?>
        <option value="<?= $s['name'] ?>"
            <?= ($search_student == $s['name']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?>
        </option>
    <?php endwhile; ?>
</select>

<select name="quiz">
    <option value="">-- Choose a Quiz --</option>
    <?php while($q = mysqli_fetch_assoc($quizzes_list)): ?>
        <option value="<?= $q['title'] ?>"
            <?= ($search_quiz == $q['title']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($q['title']) ?>
        </option>
    <?php endwhile; ?>
</select>
        
        <button type="submit" class="search-btn">
            <i class="fas fa-search"></i> Search
        </button>
    </form>

    <div class="card">
        <table id="certTable">
            <thead>
                <tr>
    <th>Quiz Name</th>
    <th>Student Name</th>

    <?php if($is_class_teacher): ?>
        <th>Teacher</th>
    <?php endif; ?>

    <th>Upload Date</th>
    <th>Action</th>
</tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($certificates) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($certificates)): ?>
                    <tr class="data-row">
                       <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
<td class="student-col"><?= htmlspecialchars($row['student_name']) ?></td>
<?php if($is_class_teacher): ?>
    <td><?= htmlspecialchars($row['teacher_name']) ?></td>
<?php endif; ?>
                        <td style="color:#888">
                            <i class="far fa-calendar-alt"></i> <?= date("M d, Y", strtotime($row['uploaded_at'])) ?>
                        </td>
                        <td>
                            <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="action-btn btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>

                            <a href="?delete=<?= $row['id'] ?>" class="btn-delete" 
                               onclick="return confirm('Delete this certificate permanently?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:50px; color:#999;">
                            <i class="fas fa-folder-open fa-3x" style="display:block; margin-bottom:10px;"></i>
                            No certificates found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>



</body>
</html>