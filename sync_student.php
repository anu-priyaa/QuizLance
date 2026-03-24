<?php
$conn = mysqli_connect("localhost", "root", "", "quizlance");
$live_id = mysqli_real_escape_string($conn, $_GET['live_id']);

$res = mysqli_query($conn, "SELECT * FROM live_quizzes WHERE id = $live_id");
$session = mysqli_fetch_assoc($res);

$data = [
    'status' => $session['status'],
    'current_question' => (int)$session['current_question'],
    'question_data' => null
];

if ($session['status'] === 'running') {
    $offset = $session['current_question'] - 1;
    $quiz_id = $session['quiz_id'];
    
    // Fetch the question using the correct table 'questions'
    $q_res = mysqli_query($conn, "SELECT id, question_text FROM questions WHERE quiz_id = $quiz_id LIMIT 1 OFFSET $offset");
    $question = mysqli_fetch_assoc($q_res);
    
    if ($question) {
        $q_id = $question['id'];
       
        $opt_res = mysqli_query($conn, "
    SELECT id, option_text 
    FROM question_options 
    WHERE question_id = $q_id 
    ORDER BY id ASC 
    LIMIT 4
");
        
        $options = [];
while($row = mysqli_fetch_assoc($opt_res)){
    $options[] = [
        'id' => $row['id'],
        'option_text' => $row['option_text']
    ];
}
        $question['options'] = $options;
        $data['question_data'] = $question;
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>