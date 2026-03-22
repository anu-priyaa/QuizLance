<?php
session_start();

/* ROLE CHECK */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

/* DB */
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
if (!$conn) die("DB Error");

$teacher_id = $_SESSION['user_id'];

$class_ids = [];

// Sub-teacher classes
$res1 = mysqli_query($conn,"
    SELECT class_id FROM class_subteachers
    WHERE teacher_id = $teacher_id
");

while($r = mysqli_fetch_assoc($res1)){
    $class_ids[] = $r['class_id'];
}

// Class teacher classes
$res2 = mysqli_query($conn,"
    SELECT id FROM classes
    WHERE teacher_id = $teacher_id
");

while($r = mysqli_fetch_assoc($res2)){
    $class_ids[] = $r['id'];
}

if(!empty($class_ids)){
    $ids = implode(",", $class_ids);

    $classes = mysqli_query($conn,"
        SELECT id, class_name FROM classes
        WHERE id IN ($ids)
    ");
}else{
    $classes = mysqli_query($conn,"SELECT id, class_name FROM classes WHERE 1=0");
}

/* ADD ANNOUNCEMENT */
if (isset($_POST['publish'])) {

    $class_id = (int)$_POST['class_id'];
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);

    mysqli_query($conn,
        "INSERT INTO announcements (teacher_id, class_id, title, message)
         VALUES ($teacher_id, $class_id, '$title', '$message')"
    );

    $success = "Announcement published successfully!";
}

if(!empty($class_ids)){
    $ids = implode(",", $class_ids);

    $myAnn = mysqli_query($conn,"
        SELECT a.*, c.class_name, t.name AS teacher_name
        FROM announcements a
        JOIN classes c ON a.class_id = c.id
        JOIN teachers t ON a.teacher_id = t.id
        WHERE a.class_id IN ($ids)
        ORDER BY a.created_at DESC
    ");
}else{
    $myAnn = mysqli_query($conn,"SELECT * FROM announcements WHERE 1=0");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher Announcements | QuizLance</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f0f2f5;
    padding: 40px;
}

.container {
    max-width: 900px;
    margin: auto;
}

.card {
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    margin-bottom: 30px;
}

h2, h3 {
    color: #5A0E24;
    margin-bottom: 15px;
}

.success {
    color: green;
    font-weight: bold;
    margin-bottom: 15px;
}

form select,
form input,
form textarea {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

button {
    background: #5A0E24;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    opacity: 0.9;
}

.announcement {
    border-left: 5px solid #5d9415;
    background: #fafafa;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.announcement strong {
    font-size: 16px;
    color: #333;
}

.announcement small {
    display: block;
    margin-top: 6px;
    color: #777;
}
</style>
</head>

<body>

<div class="container">

    <a href="teacher_dashboard.php"
       style="display:inline-block;background:#5A0E24;color:white;
              padding:10px 18px;border-radius:6px;text-decoration:none;
              font-weight:bold;margin-bottom:20px;">
        ← Back to Dashboard
    </a>

    <div class="card">
        <h2>📢 Publish Announcement</h2>

        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Class</label>
            <select name="class_id" required>
                <option value="">-- Select Class --</option>
                <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['class_name'] ?></option>
                <?php endwhile; ?>
            </select><br><br>

            <label>Title</label>
            <input type="text" name="title" placeholder="Announcement title" required><br><br>

            <label>Message</label>
            <textarea name="message" placeholder="Announcement message" required></textarea><br><br>

            <button name="publish">Publish Announcement</button>
        </form>
    </div>

    <div class="card">
        <h3>📋 My Announcements</h3>

        <?php while ($a = mysqli_fetch_assoc($myAnn)): ?>
            <div class="announcement">
                <strong><?= htmlspecialchars($a['title']) ?></strong>
                <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>
                <small>
                    Class: <?= htmlspecialchars($a['class_name']) ?> |
By: <?= htmlspecialchars($a['teacher_name']) ?>|
                    Posted: <?= date('d M Y, h:i A', strtotime($a['created_at'])) ?>
                </small>
            </div>
        <?php endwhile; ?>
    </div>

</div>

<?php include 'includes/auto_logout.php'; ?>

</body>
</html>
