<?php
$conn = mysqli_connect("localhost", "root", "", "QuizLance");
mysqli_set_charset($conn, "utf8mb4");

if (!isset($_GET['token']) || !isset($_GET['email'])) {
    die("Invalid reset link.");
}

$token = mysqli_real_escape_string($conn, $_GET['token']);
$email = mysqli_real_escape_string($conn, $_GET['email']);


$res = mysqli_query($conn,
    "SELECT * FROM Students
     WHERE email='$email'
     AND reset_token='$token'
     AND token_expiry > NOW()"
);


if (mysqli_num_rows($res) != 1) {
    die("Reset link expired or invalid.");
}

$user  = mysqli_fetch_assoc($res);
$email = $user['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Server-side validation: ensure strong password
    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        $pwErrors = [];
        if (strlen($password) < 8) {
            $pwErrors[] = 'at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $pwErrors[] = 'one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $pwErrors[] = 'one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $pwErrors[] = 'one number';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=[\]{};:\"\\|,.<>\/?]/', $password)) {
            $pwErrors[] = 'one special character (e.g. !@#$%)';
        }

        if (!empty($pwErrors)) {
            $error = 'Password must contain ' . implode(', ', $pwErrors) . '.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query($conn,
                "UPDATE Students
                 SET password='$hashed',
                     reset_token=NULL,
                     token_expiry=NULL
                 WHERE email='$email'"
            );

            echo "<script>alert('Password updated successfully. Please login.');
                  window.location='login.php';</script>";
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<style>
body { font-family:'Segoe UI'; background:#628141; display:flex; justify-content:center; align-items:center; min-height:100vh; }
.form-container { background:#e9e5d9; padding:40px; border-radius:12px; max-width:400px; width:90%; text-align:center; }
input, button { width:100%; padding:12px; margin-top:10px; }
button { background:#5d9415; color:#fff; font-weight:bold; border:none; cursor:pointer; }
.error { color:red; font-weight:bold; }
</style>
</head>
<body>

<div class="form-container">
<h2>Reset Password</h2>

<?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

<form method="POST" id="resetForm">
    <input type="password" id="password" name="password" placeholder="New Password" required>
    <input type="password" id="confirm" name="confirm" placeholder="Confirm Password" required>
    <div id="pw-requirements" style="text-align:left; margin-top:8px; font-size:13px;">
        <strong>Password must include:</strong>
        <ul style="margin:6px 0 0 18px; padding:0;">
            <li id="req-length">At least 8 characters</li>
            <li id="req-upper">One uppercase letter</li>
            <li id="req-lower">One lowercase letter</li>
            <li id="req-number">One number</li>
            <li id="req-special">One special character (e.g. !@#$%)</li>
            <li id="req-match">Passwords match</li>
        </ul>
    </div>
    <button type="submit" id="submitBtn">Update Password</button>
</form>

<script>
    const pw = document.getElementById('password');
    const conf = document.getElementById('confirm');
    const submitBtn = document.getElementById('submitBtn');

    const reqLength = document.getElementById('req-length');
    const reqUpper = document.getElementById('req-upper');
    const reqLower = document.getElementById('req-lower');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');
    const reqMatch = document.getElementById('req-match');

    function checkPassword() {
        const val = pw.value;
        const checks = {
            length: val.length >= 8,
            upper: /[A-Z]/.test(val),
            lower: /[a-z]/.test(val),
            number: /[0-9]/.test(val),
            special: /[!@#$%^&*()_+\-=[\]{};:\"\\|,.<>\/?]/.test(val)
        };

        reqLength.style.color = checks.length ? 'green' : 'red';
        reqUpper.style.color = checks.upper ? 'green' : 'red';
        reqLower.style.color = checks.lower ? 'green' : 'red';
        reqNumber.style.color = checks.number ? 'green' : 'red';
        reqSpecial.style.color = checks.special ? 'green' : 'red';

        const match = val === conf.value && val.length > 0;
        reqMatch.style.color = match ? 'green' : 'red';

        const allGood = checks.length && checks.upper && checks.lower && checks.number && checks.special && match;
        submitBtn.disabled = !allGood;
    }

    pw.addEventListener('input', checkPassword);
    conf.addEventListener('input', checkPassword);

    // prevent submit if client-side bypassed
    document.getElementById('resetForm').addEventListener('submit', function(e){
        if (submitBtn.disabled) {
            e.preventDefault();
            alert('Please meet the password requirements before submitting.');
        }
    });
</script>
</div>

</body>
</html>
