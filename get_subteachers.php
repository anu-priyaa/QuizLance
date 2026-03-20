<?php
session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");
if (!$conn) {
    die("DB error");
}

/* =========================
   ROLE CHECK
   ========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    exit("Unauthorized");
}

$teacher_id = $_SESSION['user_id'];
$class_id = (int)$_GET['class_id'];

/* =========================
   CHECK CLASS ACCESS
   ========================= */
$access = mysqli_query($conn,"
    SELECT c.teacher_id 
    FROM Classes c
    LEFT JOIN class_subteachers s
    ON c.id = s.class_id
    WHERE c.id = $class_id
    AND (
        c.teacher_id = $teacher_id
        OR s.teacher_id = $teacher_id
    )
");

if (mysqli_num_rows($access) == 0) {
    exit("Access denied");
}

$classData = mysqli_fetch_assoc($access);
$isClassTeacher = ($classData['teacher_id'] == $teacher_id);

/* =========================
   FETCH SUB TEACHERS
   ========================= */
$res = mysqli_query($conn,"
    SELECT t.id, t.name 
    FROM class_subteachers cs
    JOIN Teachers t ON cs.teacher_id = t.id
    WHERE cs.class_id = $class_id
");

if(mysqli_num_rows($res) == 0){
    echo "<p style='text-align:center;color:#777;'>No sub teachers assigned</p>";
}else{
    while($row = mysqli_fetch_assoc($res)){

        echo "
        <div class='sub-item'>
            <div style='display:flex;justify-content:space-between;width:100%;align-items:center'>
                
                <span>
                    <i class='fas fa-user'></i> 
                    ".htmlspecialchars($row['name'])."
                </span>
        ";

        /* 🔒 ONLY CLASS TEACHER CAN DELETE */
        if($isClassTeacher){
            echo "
                <i class='fas fa-trash'
                   style='color:red;cursor:pointer;'
                   onclick='removeSubTeacher(".$row['id'].", ".$class_id.")'>
                </i>
            ";
        }

        echo "
            </div>
        </div>
        ";
    }
}
?>