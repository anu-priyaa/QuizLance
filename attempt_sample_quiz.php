<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");

$quiz_id = (int)$_GET['quiz_id'];

$quiz = mysqli_fetch_assoc(
mysqli_query($conn,"SELECT * FROM sample_quizzes WHERE id=$quiz_id")
);

$questions = mysqli_query($conn,"
SELECT * FROM questions
WHERE quiz_id=$quiz_id
ORDER BY id
");

$results=[];

if(isset($_POST['submit_quiz'])){

foreach($_POST as $key=>$value){

if(strpos($key,"answer_")===0){

$qid=str_replace("answer_","",$key);

$qTypeRes = mysqli_query($conn, "SELECT question_type FROM questions WHERE id=$qid");
$qTypeRow = mysqli_fetch_assoc($qTypeRes);
$question_type = $qTypeRow['question_type'] ?? '';

if ($question_type === 'mcq') {
    $optRes = mysqli_query($conn, "SELECT option_text FROM question_options WHERE question_id=$qid AND is_correct=1 LIMIT 1");
    $optRow = mysqli_fetch_assoc($optRes);
    $correct_text = $optRow['option_text'] ?? '';
    $expRes = mysqli_query($conn, "SELECT explanation FROM question_answers WHERE question_id=$qid");
    $expRow = mysqli_fetch_assoc($expRes);
    $explanation_text = $expRow['explanation'] ?? '';
} else {
    $ansRes = mysqli_query($conn, "SELECT correct_answer, explanation FROM question_answers WHERE question_id=$qid");
    $ansRow = mysqli_fetch_assoc($ansRes);
    $correct_text = $ansRow['correct_answer'] ?? '';
    $explanation_text = $ansRow['explanation'] ?? '';
}

$correct = strtolower(trim($correct_text));
$user = strtolower(trim($value));
$status = ($correct == $user);

$results[$qid] = [
    "user" => $value,
    "correct" => $correct_text,
    "explanation" => $explanation_text,
    "status" => $status
];

}
}

}
?>

<!DOCTYPE html>
<html>
<head>

<title><?= htmlspecialchars($quiz['title']) ?></title>

<style>

body{
font-family:'Segoe UI',sans-serif;
background:#f0f2f5;
padding:40px
}

.card{
background:white;
padding:30px;
border-radius:15px;
box-shadow:0 4px 12px rgba(0,0,0,0.05);
margin-bottom:25px
}

.question{
margin-bottom:20px
}

.options{
margin-top:20px;
}

.option-card{
display:flex;
align-items:center;
gap:15px;
background:#f9fafb;
border:2px solid #e5e7eb;
border-radius:12px;
padding:14px 18px;
margin-bottom:12px;
cursor:pointer;
transition:all .2s;
position:relative;
}

.option-card:hover{
border-color:#5d9415;
background:#f0f7e8;
}

/* selected state (when a student picks an option) */
.option-card.selected{
border-color: #2e7d32;
background: #e8f8ec;
}

.option-card.selected .option-label{
background: #2e7d32;
color: #fff;
}

/* make the whole label clickable while keeping visual styles unchanged */
.option-card input{
position:absolute;
inset:0;
width:100%;
height:100%;
opacity:0;
cursor:pointer;
margin:0;
}
/* ensure the invisible input sits above the visible option content */
.option-card input{z-index:2}

.option-card .option-label,
.option-card .option-text{position:relative;z-index:1}

.option-label{
width:36px;
height:36px;
border-radius:50%;
background:#e5e7eb;
display:flex;
align-items:center;
justify-content:center;
font-weight:bold
}

.option-text{
font-size:16px
}

.correct-option{
border-color:green !important;
background:#e8f8ec !important;
}

.wrong-option{
border-color:red !important;
background:#fdeaea !important;
}

.btn{
background:#5d9415;
color:white;
padding:12px 20px;
border:none;
border-radius:6px;
font-weight:bold;
cursor:pointer
}

.correct{
color:green;
font-weight:bold
}

.wrong{
color:red;
font-weight:bold
}

.text-input{
width:300px;
padding:10px;
border-radius:6px;
border:1px solid #ccc
}

.back-btn{
display:inline-block;
background:#5A0E24;
color:white;
padding:10px 18px;
border-radius:6px;
text-decoration:none;
font-weight:bold;
margin-bottom:20px;
}

.back-btn:hover{
background:#7a1230;
}

</style>
</head>

<body>

<a href="student_dashboard.php" class="back-btn">← Back to Dashboard</a>

<h2><?= htmlspecialchars($quiz['title']) ?></h2>

<form method="POST">

<?php
$i=1;

mysqli_data_seek($questions,0);

while($q=mysqli_fetch_assoc($questions)):
?>

<div class="card">

<div class="question">
<h3>Q<?= $i ?>. <?= htmlspecialchars($q['question_text']) ?></h3>
</div>

<?php if($q['question_type']=="mcq"): ?>

<div class="options">

<?php
$opts=mysqli_query($conn,"
SELECT * FROM question_options
WHERE question_id={$q['id']}
");

$optIndex=0;

while($o=mysqli_fetch_assoc($opts)):

$optIndex++;

$selected = isset($results[$q['id']]) && $results[$q['id']]['user']==$o['option_text'];
$correct  = $o['is_correct']==1;

$style="";

if(isset($_POST['submit_quiz'])){
if($correct) $style="correct-option";
elseif($selected) $style="wrong-option";
}
?>

<label class="option-card <?= $style ?>">

<input type="radio"
name="answer_<?= $q['id'] ?>"
value="<?= htmlspecialchars($o['option_text']) ?>"
<?= $selected ? "checked" : "" ?>
<?= isset($_POST['submit_quiz']) ? "disabled" : "" ?>
>

<span class="option-label"><?= chr(64+$optIndex) ?></span>

<span class="option-text">
<?= htmlspecialchars($o['option_text']) ?>
</span>

</label>

<?php endwhile; ?>

</div>

<?php endif; ?>


<?php if($q['question_type']=="true_false"): ?>

<div class="options">

<label class="option-card">
<input type="radio" name="answer_<?= $q['id'] ?>" value="True"
<?= isset($_POST['submit_quiz']) ? "disabled" : "" ?>>
<span class="option-label">A</span>
<span class="option-text">True</span>
</label>

<label class="option-card">
<input type="radio" name="answer_<?= $q['id'] ?>" value="False"
<?= isset($_POST['submit_quiz']) ? "disabled" : "" ?>>
<span class="option-label">B</span>
<span class="option-text">False</span>
</label>

</div>

<?php endif; ?>


<?php if($q['question_type']=="one_word"): ?>

<input type="text"
name="answer_<?= $q['id'] ?>"
class="text-input"
placeholder="Type your answer"
<?= isset($_POST['submit_quiz']) ? "disabled" : "" ?>

value="<?= isset($results[$q['id']]) ? htmlspecialchars($results[$q['id']]['user']) : "" ?>">

<?php endif; ?>


<?php

if(isset($results[$q['id']])){

$r=$results[$q['id']];

echo "<p class='".($r['status']?"correct":"wrong")."'>";
echo $r['status']?"Correct":"Wrong";
echo "</p>";

echo "<p><b>Correct Answer:</b> ".$r['correct']."</p>";

if($r['explanation'])
echo "<p><b>Explanation:</b> ".$r['explanation']."</p>";

}

?>

</div>

<?php
$i++;
endwhile;
?>

<?php if(!isset($_POST['submit_quiz'])): ?>

<button class="btn" name="submit_quiz">
Submit Quiz
</button>

<?php endif; ?>

</form>

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.options').forEach(function(group){
        group.querySelectorAll('.option-card input[type=radio]').forEach(function(inp){
            // initialize selected class if already checked
            if(inp.checked){
                var p = inp.closest('.option-card');
                if(p) p.classList.add('selected');
            }
            inp.addEventListener('change', function(){
                var parent = this.closest('.options');
                if(!parent) return;
                parent.querySelectorAll('.option-card').forEach(function(card){
                    card.classList.remove('selected');
                });
                var sel = this.closest('.option-card');
                if(sel) sel.classList.add('selected');
            });
        });
    });
});
</script>

</body>
</html>