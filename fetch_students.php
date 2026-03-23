<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "QuizLance");

$class_id = (int)$_GET['class_id'];
$current_teacher = (int)$_GET['teacher_id'];
$res = mysqli_query($conn,
    "SELECT s.id, s.name, c.teacher_id
     FROM Students s
     JOIN class_students cs ON s.id = cs.student_id
     JOIN Classes c ON cs.class_id = c.id
     WHERE cs.class_id = $class_id"
);

while($row = mysqli_fetch_assoc($res)) {
?>

<div style="display:flex; justify-content:space-between; align-items:center; background:#f1f1f1; padding:10px; border-radius:8px; margin-bottom:10px;">

    <span>
        <i class="fas fa-user" style="color:#5d9415;"></i>
        <?= htmlspecialchars($row['name']) ?>
    </span>
<?php if($row['teacher_id'] == $current_teacher): ?>

<button type="button"
    onclick="confirmDelete(<?= $row['id'] ?>, <?= $class_id ?>)"
    style="background:none; border:none; color:red; cursor:pointer;">
    <i class="fas fa-trash"></i>
</button>

<?php endif; ?>

</div>

<?php } ?>