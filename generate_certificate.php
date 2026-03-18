<?php

session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");

/* fetch quizzes */
$quizzes = mysqli_query($conn,"SELECT id,title FROM quizzes");

?>

<div class="header-section">
    <div class="button-group">
        <button type="button" class="back-btn" onclick="window.location.href='teacher_dashboard.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div>

    <h2>Generate Certificate</h2>
    <p class="header-subtitle">Create and issue professional certificates to students</p>
</div>

<form method="POST" action="create_certificate.php" enctype="multipart/form-data">

<label>Select Quiz</label>

<select name="quiz_id" id="quizSelect" required>

<option value="">-- Select Quiz --</option>

<?php while($quiz=mysqli_fetch_assoc($quizzes)){ ?>

<option value="<?php echo $quiz['id']; ?>">
<?php echo $quiz['title']; ?>
</option>

<?php } ?>

</select>

<label>Select Student</label>

<select name="student_id" id="studentSelect" required>

<option value="">Select a quiz first</option>

</select>

<h3>Select Certificate Theme</h3>

<div class="theme-selector">

<label class="theme-option">
<input type="radio" name="theme" value="theme1.png" required>
<img src="templates/theme1.png" width="250">
</label>

<label class="theme-option">
<input type="radio" name="theme" value="theme2.png">
<img src="templates/theme2.png" width="250">
</label>

<label class="theme-option">
<input type="radio" name="theme" value="theme3.png">
<img src="templates/theme3.png" width="250">
</label>

</div>

<label>Certificate Title</label>

<input type="text" name="title" placeholder="Certificate of Achievement" required>

<label>Description</label>

<input type="text" name="description" placeholder="For outstanding performance in quiz" required>

<label>Issued By</label>
<input type="text" name="issued_by" required>

<label>Date of Issue</label>
<input type="date" name="issue_date" required>

<label>Instructor Name</label>
<input type="text" name="instructor_name" required>

<label>Upload Instructor Signature</label>
<input type="file" name="signature" accept="image/png">

<button type="submit">Generate Certificate</button>

</form>


<script>

/* When quiz is selected */

document.getElementById("quizSelect").addEventListener("change", function(){

let quiz_id = this.value;

fetch("fetch_quiz_students.php?quiz_id=" + quiz_id)

.then(response => response.text())

.then(data => {

document.getElementById("studentSelect").innerHTML = data;

});

});

</script>


<style>

/* YOUR SAME CSS - unchanged */

:root {
    --primary: #5A0E24;
    --primary-light: #7a1a36;
    --bg: #faf6f3;
    --card-bg: #fff;
    --text-dark: #2b2b2b;
    --text-muted: #6b6b6b;
    --border-color: #e0dcd8;
}

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body{
background:linear-gradient(to right,#faf6f3,#f3ece8);
color:var(--text-dark);
}

.header-section{
text-align:center;
margin-bottom:40px;
padding-top:10px;
}

.button-group{
margin-bottom:20px;
}

.back-btn{
display:inline-flex;
align-items:center;
gap:8px;
background:transparent;
color:var(--primary);
border:2px solid var(--primary);
font-weight:600;
font-size:15px;
padding:11px 24px;
border-radius:8px;
cursor:pointer;
transition:all .3s;
}

.back-btn:hover{
background:var(--primary);
color:#fff;
transform:translateY(-2px);
}

h2{
color:var(--primary);
font-size:36px;
font-weight:700;
margin-bottom:10px;
}

.header-subtitle{
color:var(--text-muted);
font-size:15px;
}

h3{
color:var(--primary);
margin-top:20px;
margin-bottom:15px;
}

form{
max-width:600px;
margin:30px auto;
padding:30px;
background:#fff;
border-radius:12px;
box-shadow:0 2px 12px rgba(0,0,0,.08);
}

label{
display:block;
font-weight:600;
margin-bottom:10px;
font-size:14px;
}

select,input[type="text"]{
width:100%;
padding:12px 15px;
margin-bottom:20px;
border:2px solid var(--border-color);
border-radius:8px;
font-size:14px;
transition:all .3s;
}

select:hover,input[type="text"]:hover{
border-color:var(--primary);
}

select:focus,input[type="text"]:focus{
outline:none;
border-color:var(--primary);
box-shadow:0 0 0 3px rgba(90,14,36,.1);
}

.theme-selector{
display:flex;
gap:25px;
flex-wrap:wrap;
margin-bottom:25px;
}

.theme-option{
display:flex;
flex-direction:column;
align-items:center;
gap:8px;
cursor:pointer;
}

.theme-option img{
border:3px solid var(--border-color);
border-radius:10px;
transition:.3s;
box-shadow:0 2px 8px rgba(0,0,0,.08);
}

input[type="radio"]:checked + img{
border:4px solid var(--primary);
box-shadow:0 6px 20px rgba(90,14,36,.25);
transform:scale(1.02);
}

button{
background:var(--primary);
color:#fff;
padding:14px 40px;
border:none;
border-radius:8px;
font-size:16px;
font-weight:600;
cursor:pointer;
transition:.3s;
box-shadow:0 4px 15px rgba(90,14,36,.2);
}

button:hover{
background:var(--primary-light);
transform:translateY(-2px);
}

</style>