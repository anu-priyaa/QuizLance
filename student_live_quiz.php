<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if(!isset($_SESSION['live_quiz_id'])){
    header("Location: student_dashboard.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","QuizLance");

$quiz_id = $_SESSION['live_quiz_id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Live Quiz</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #faf6f3, #f3ece8);
    padding: 40px 20px;
    color: #2b2b2b;
}

#question_area {
    max-width: 700px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

h2 {
    color: #5A0E24;
    margin-bottom: 30px;
    font-size: 28px;
}

h3 {
    color: #5A0E24;
    margin-bottom: 25px;
    font-size: 20px;
    font-weight: 600;
}

.option {
    margin: 15px 0;
    padding: 15px;
    text-align: left;
    background: #f9f7f4;
    border-radius: 8px;
    border: 2px solid #e0dcd8;
    transition: all 0.3s ease;
    cursor: pointer;
}

.option:hover {
    background: #f5f1ee;
    border-color: #5A0E24;
}

input[type="radio"],
input[type="checkbox"] {
    margin-right: 12px;
    cursor: pointer;
    width: 18px;
    height: 18px;
    accent-color: #5A0E24;
}

input[type="text"],
textarea {
    padding: 12px;
    font-family: 'Segoe UI', sans-serif;
    border: 2px solid #e0dcd8;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
}

input[type="text"]:focus,
textarea:focus {
    outline: none;
    border-color: #5A0E24;
    box-shadow: 0 0 0 3px rgba(90, 14, 36, 0.1);
}

button {
    padding: 12px 30px;
    background: #5A0E24;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(90, 14, 36, 0.2);
    margin-top: 20px;
}

button:hover {
    background: #7a1a36;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(90, 14, 36, 0.3);
}

button:active {
    transform: translateY(0);
}

form {
    width: 100%;
}

.no-options {
    color: #d32f2f;
    padding: 15px;
    background: #ffebee;
    border-radius: 8px;
    margin: 20px 0;
}
</style>
</head>
<body>

<div id="question_area">
    Loading question...
</div>

<script>

let currentQuestionId = null;

function loadQuestion(){
    fetch("get_current_question.php?quiz_id=<?php echo $quiz_id; ?>")
    .then(res => res.json())
    .then(q => {
        console.log("Question data:", q);

        if(q.finished){
            document.getElementById("question_area").innerHTML =
            "<h2>Quiz Finished!</h2><p>Thank you for participating.</p>";
            return;
        }

        // Avoid reloading same question repeatedly
        if(currentQuestionId === q.question_id) return;

        currentQuestionId = q.question_id;

        let html = "<h3>" + (q.question_text || "Question") + "</h3>";
        html += "<form onsubmit='submitAnswer(event,"+q.question_id+")'>";

        // Check if options exist
        if(!q.options || q.options.length === 0){
            html += "<p class='no-options'>No options available for this question. (Type: " + q.question_type + ", Options: " + (q.options ? q.options.length : 0) + ")</p>";
        } else {
            // MCQ Questions
            if(q.question_type === 'mcq'){
                q.options.forEach(function(opt, index){
                    html += `
                        <div class="option">
                            <input type="radio" id="opt_${opt.id}" name="option" value="${opt.id}" required>
                            <label for="opt_${opt.id}" style="cursor: pointer; flex: 1;">${opt.option_text || 'Option ' + (index + 1)}</label>
                        </div>
                    `;
                });
            }
            // True/False Questions
            else if(q.question_type === 'true_false'){
                html += `
                    <div class="option">
                        <input type="radio" id="opt_true" name="option" value="True" required>
                        <label for="opt_true" style="cursor: pointer;">True</label>
                    </div>
                    <div class="option">
                        <input type="radio" id="opt_false" name="option" value="False" required>
                        <label for="opt_false" style="cursor: pointer;">False</label>
                    </div>
                `;
            }
        }
        // One Word / Fill in the Blank Questions
        if(q.question_type === 'one_word' || q.question_type === 'fill_blank'){
            html += `
                <div class="option" style="background: #fff;">
                    <input type="text" name="option" placeholder="Enter your answer" required style="padding:12px;width:100%;">
                </div>
            `;
        }
        // Descriptive Questions
        else if(q.question_type === 'descriptive'){
            html += `
                <div class="option" style="background: #fff;">
                    <textarea name="option" placeholder="Enter your answer" required style="padding:12px;width:100%;height:150px;"></textarea>
                </div>
            `;
        }

        html += "<br><button type='submit'>Submit Answer</button></form>";

        document.getElementById("question_area").innerHTML = html;

    })
    .catch(err => {
        console.error("Error loading question:", err);
        document.getElementById("question_area").innerHTML = "<p class='no-options'>Error loading question. Retrying...</p>";
    });
}

// Load question immediately on page load
loadQuestion();

// Keep polling for new questions every 2 seconds
setInterval(loadQuestion, 2000);


function submitAnswer(e, question_id){
    e.preventDefault();

    let selected;
    
    // Check for radio input (MCQ/True-False)
    let radioInput = document.querySelector('input[name="option"]:checked');
    if(radioInput){
        selected = radioInput.value;
    } else {
        // Check for text input (one_word/fill_blank)
        let textInput = document.querySelector('input[type="text"][name="option"]');
        if(textInput){
            selected = textInput.value;
        } else {
            // Check for textarea (descriptive)
            let textarea = document.querySelector('textarea[name="option"]');
            if(textarea){
                selected = textarea.value;
            }
        }
    }

    if(!selected){
        alert("Please select or enter an answer!");
        return;
    }

    fetch("submit_live_answer.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`quiz_id=<?php echo $quiz_id; ?>&question_id=${question_id}&option_id=${selected}`
    })
    .then(res => res.text())
    .then(data => {
        alert("Answer Submitted!");
    });
}

</script>

</body>
</html>