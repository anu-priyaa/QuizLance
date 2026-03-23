
<?php

session_start();

$conn = mysqli_connect("localhost","root","","QuizLance");

/* form data */
$student_id = $_POST['student_id'];
$quiz_id = $_POST['quiz_id'];
$theme = $_POST['theme'];
$title = $_POST['title'];
$description = $_POST['description'];
$issued_by = $_POST['issued_by'] ?? "";
$issue_date = $_POST['issue_date'] ?? "";
$instructor = $_POST['instructor_name'] ?? "";

$teacher_id = $_SESSION['user_id'];

/* ---------------------------
   HANDLE SIGNATURE UPLOAD
----------------------------*/

$signature_path = "";

if(isset($_FILES['signature']) && $_FILES['signature']['error'] == 0){

    $folder = "temp_signatures/";

    if(!is_dir($folder)){
        mkdir($folder,0777,true);
    }

    $filename = time()."_".basename($_FILES['signature']['name']);

    $signature_path = $folder.$filename;

    move_uploaded_file($_FILES['signature']['tmp_name'],$signature_path);
}


/* get student name */
$res = mysqli_query($conn,"SELECT name FROM students WHERE id='$student_id'");
$row = mysqli_fetch_assoc($res);
$student_name = $row['name'];

/* template */
$template = "templates/".$theme;

/* load template image */
$image = imagecreatefrompng($template);

/* image size */
$image_width = imagesx($image);
$image_height = imagesy($image);

/* text color */
$black = imagecolorallocate($image,0,0,0);

/* font */
$font = __DIR__ . "/fonts/arial.ttf";

if(!file_exists($font)){
    die("Font not found: " . $font);
}
/* ---------------------------
   THEME BASED POSITIONS
----------------------------*/

if($theme == "theme1.png"){

$title_size = 32;
$name_size = 40;
$desc_size = 25;
$small_size = 22;

$title_y = 170;
$name_y = 340;
$desc_start_y = 440;

$issued_y = 620;
$instructor_y = 620;   // same level as issued by

$date_y = 680;
$signature_y = 680;    // same level as date

}

elseif($theme == "theme2.png"){

$title_size = 55;
$name_size = 80;
$desc_size = 35;
$small_size = 28;

$title_y = 250;
$name_y = 420;
$desc_start_y = 520;

$issued_y = 760;
$instructor_y = 760;   // same level as issued by

$date_y = 820;
$signature_y = 820;    // same level as date

}

else{ // theme3

$title_size = 60;
$name_size = 85;
$desc_size = 38;
$small_size = 30;

$title_y = 240;
$name_y = 420;
$desc_start_y = 520;

$issued_y = 760;
$date_y = 820;
$instructor_y = 820;

}

/* ---------------------------
   TITLE (centered)
----------------------------*/

$title_bbox = imagettfbbox($title_size,0,$font,$title);
$title_width = $title_bbox[2] - $title_bbox[0];
if($theme == "theme2.png"){
    $white_start = 500;   // start of white area
    $white_width = $image_width - 550;
    $title_x = $white_start + (($white_width - $title_width)/2);
}else{
    $title_x = ($image_width - $title_width)/2;
}

imagettftext($image,$title_size,0,$title_x,$title_y,$black,$font,$title);


/* ---------------------------
   STUDENT NAME (centered)
----------------------------*/

$name_bbox = imagettfbbox($name_size,0,$font,$student_name);
$name_width = $name_bbox[2] - $name_bbox[0];
if($theme == "theme2.png"){
    $white_start = 500;
    $white_width = $image_width - 550;
    $name_x = $white_start + (($white_width - $name_width)/2);
}else{
    $name_x = ($image_width - $name_width)/2;
}

imagettftext($image,$name_size,0,$name_x,$name_y,$black,$font,$student_name);

/* ---------------------------
   DESCRIPTION (wrapped)
----------------------------*/
$font_size = $desc_size;
if($theme == "theme2.png"){
    $max_width = $image_width - 700; // smaller width so text breaks earlier
}else{
    $max_width = $image_width - 200;
}

$words = explode(" ", $description);
$lines = [];
$current_line = "";

foreach($words as $word){

$test_line = $current_line.$word." ";
$box = imagettfbbox($font_size,0,$font,$test_line);
$test_width = $box[2]-$box[0];

if($test_width > $max_width){
$lines[] = trim($current_line);
$current_line = $word." ";
}
else{
$current_line = $test_line;
}

}

$lines[] = trim($current_line);

/* starting Y */
$y = $desc_start_y;

foreach($lines as $line){

$bbox = imagettfbbox($font_size,0,$font,$line);
$line_width = $bbox[2]-$bbox[0];

if($theme == "theme2.png"){
    $white_start = 500;
    $white_width = $image_width - 550;
    $x = $white_start + (($white_width - $line_width)/2);
}else{
    $x = ($image_width - $line_width)/2;
}

imagettftext($image,$font_size,0,$x,$y,$black,$font,$line);

$y += ($font_size + 35);

}

/* ---------------------------
   DATE
----------------------------*/
/* ---------------------------
   POSITION LOGIC FIX
----------------------------*/
// Define a base X position if not already set by theme logic
$issued_x = 200; 

if($theme == "theme2.png"){
    $issued_x = 650;
} elseif($theme == "theme3.png") {
    $issued_x = 250;
}

/* ---------------------------
   DATE
----------------------------*/
$date_text = date("d M Y", strtotime($issue_date));
$date_x = $issued_x; 

imagettftext($image,$small_size,0,$date_x,$date_y,$black,$font,$date_text);

imagettftext($image,$small_size,0,$date_x,$date_y,$black,$font,$date_text);


/* ---------------------------
   ISSUED BY
----------------------------*/

$issued_text = $issued_by;

$bbox = imagettfbbox($small_size,0,$font,$issued_text);
$issued_width = $bbox[2] - $bbox[0];

if($theme == "theme1.png"){

    /* align with date position */
    $issued_x = $date_x;

    /* move above date */
    $issued_y = $date_y - 60;

}
elseif($theme == "theme2.png"){

    /* move away from badge */
    $issued_x = 650;
    $issued_y = $instructor_y;   // same level as instructor

}
else{

    $inst_bbox = imagettfbbox($small_size,0,$font,"Instructor: ".$instructor);
    $inst_width = $inst_bbox[2] - $inst_bbox[0];
    $inst_x = $image_width - $inst_width - 585;

    $issued_x = ($date_x + $inst_x) / 2 - ($issued_width / 2);

}

imagettftext($image,$small_size,0,$issued_x,$issued_y,$black,$font,$issued_text);



/* ---------------------------
   INSTRUCTOR
----------------------------*/

$inst_text = $instructor;

$bbox = imagettfbbox($small_size,0,$font,$inst_text);
$inst_width = $bbox[2]-$bbox[0];

if($theme == "theme1.png"){

    $x = $image_width - $inst_width - 200; // right side

}else{

    $x = $image_width - $inst_width - 200;

}

imagettftext($image,$small_size,0,$x,$instructor_y,$black,$font,$inst_text);

/* ---------------------------
   SIGNATURE BELOW INSTRUCTOR
----------------------------*/

if(!empty($signature_path) && file_exists($signature_path)){

    $ext = strtolower(pathinfo($signature_path, PATHINFO_EXTENSION));

    if($ext == "png"){
        $signature = imagecreatefrompng($signature_path);
    }
    elseif($ext == "jpg" || $ext == "jpeg"){
        $signature = imagecreatefromjpeg($signature_path);
    }
    else{
        $signature = false;
    }

    if($signature){

        $sign_width = imagesx($signature);
        $sign_height = imagesy($signature);

        /* place exactly under instructor name */
        $sign_x = $x + ($inst_width/2) - ($sign_width/2);
        $sign_y = $instructor_y + 15;

        /* resize signature */
$new_width = 150;
$new_height = 60;

/* recenter after resizing */
$sign_x = $x + ($inst_width/2) - ($new_width/2);

imagecopyresampled(
    $image,
    $signature,
    $sign_x,
    $sign_y,
    0,
    0,
    $new_width,
    $new_height,
    $sign_width,
    $sign_height
);

        imagedestroy($signature);
    }
}

/* ---------------------------
   SAVE CERTIFICATE
----------------------------*/

$file = "certificates/cert_".time().".png";

imagepng($image,$file);

imagedestroy($image);



?>

<h2 style="text-align:center;">Certificate Generated Successfully</h2>

<div style="text-align:center;margin-top:30px;">

<img src="<?php echo $file; ?>" style="width:800px;border:4px solid #ccc;border-radius:10px;">

<br><br>

<a href="<?php echo $file; ?>" download
style="padding:12px 25px;background:#5A0E24;color:white;text-decoration:none;border-radius:6px;">
Download Certificate
</a>

<form method="POST" action="upload_certificate.php">

<input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
<input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
<input type="hidden" name="generated_file" value="<?php echo $file; ?>">

<button type="submit" name="upload_generated"
style="padding:12px 25px;background:#28a745;color:white;border:none;border-radius:6px;">
Upload Certificate
</button>

</form>

<br><br>

<a href="generate_certificate.php"
style="padding:10px 20px;border:2px solid #5A0E24;color:#5A0E24;text-decoration:none;border-radius:6px;">
Generate Another Certificate
</a>

</div>

