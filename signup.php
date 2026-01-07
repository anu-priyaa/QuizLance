<?php
session_start(); 
$conn = mysqli_connect("localhost", "root", "", "QuizLance");

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $admission_id = mysqli_real_escape_string($conn, $_POST['admission_id']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Server-side full name validation: allow letters and spaces only (3-50 chars)
    if (!preg_match('/^[\p{L} ]{3,50}$/u', $name)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Name must contain only letters and spaces (3–50 characters).'];
        header("Location: signup.php");
        exit();
    }

    // Server-side username format validation: 3-20 chars, letters, numbers, underscores
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Username must be 3-20 characters and may contain letters, numbers, and underscores only.'];
        header("Location: signup.php");
        exit();
    }

    // Server-side admission_id validation: digits only
    if (!preg_match('/^[0-9]+$/', $admission_id)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Admission ID must contain digits only.'];
        header("Location: signup.php");
        exit();
    }

    // Server-side email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Please enter a valid email address.'];
        header("Location: signup.php");
        exit();
    }

    // Confirm match
    if ($password !== $confirmPassword) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Passwords do not match!'];
        header("Location: signup.php");
        exit();
    }

    // Server-side password strength validation
    $pwErrors = [];
    if (strlen($password) < 8) { $pwErrors[] = 'at least 8 characters'; }
    if (!preg_match('/[A-Z]/', $password)) { $pwErrors[] = 'one uppercase letter'; }
    if (!preg_match('/[a-z]/', $password)) { $pwErrors[] = 'one lowercase letter'; }
    if (!preg_match('/[0-9]/', $password)) { $pwErrors[] = 'one number'; }
    if (!preg_match('/[!@#$%^&*()_+\-=[\]{};:\"\\|,.<>\/?]/', $password)) { $pwErrors[] = 'one special character'; }

    if (!empty($pwErrors)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Password must contain ' . implode(', ', $pwErrors) . '.'];
        header("Location: signup.php");
        exit();
    }

    // Check username uniqueness
    $check = mysqli_query($conn, "SELECT id FROM Students WHERE username='$username' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Username already taken. Choose another.'];
        header("Location: signup.php");
        exit();
    }

    // Check email uniqueness
    $checkEmail = mysqli_query($conn, "SELECT id FROM Students WHERE email='$email' LIMIT 1");
    if ($checkEmail && mysqli_num_rows($checkEmail) > 0) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'An account with this email already exists.'];
        header("Location: signup.php");
        exit();
    }

    // Check admission_id uniqueness
    $checkAdmission = mysqli_query($conn, "SELECT id FROM Students WHERE admission_id='$admission_id' LIMIT 1");
    if ($checkAdmission && mysqli_num_rows($checkAdmission) > 0) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'An account with this admission ID already exists.'];
        header("Location: signup.php");
        exit();
    }

    // Hash the password before storing
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Standard Registration Logic (store hashed password)
    $sql = "INSERT INTO Students (name, username, admission_id, email, password) 
            VALUES ('$name', '$username', '$admission_id', '$email', '$hashed')";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Registration Successful!!! Please Login.'];
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: ' . mysqli_error($conn)];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizLance - Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

    html, body {
    overflow-x: hidden;
}
    /* BUBBLE POPUP MENU */
.menu-bubble {
    position: relative;
}

.menu-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff;
    font-size: 22px;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(90, 14, 36, 0.35);
}

/* Hidden icons */
.menu-items {
    position: absolute;
    top: 60px;
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    opacity: 0;
    transform: scale(0.85);
    pointer-events: none;
    transition: all 0.3s ease;
}

/* Visible when active */
.menu-items.active {
    opacity: 1;
    transform: scale(1);
    pointer-events: auto;
}

.menu-items a {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    text-decoration: none;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    transition: transform 0.2s ease;
}

.menu-items a:hover {
    transform: scale(1.1);
}


    :root {
        --primary: #5A0E24;
        --primary-light: #7a1a36;
        --bg: #faf6f3;
        --card-bg: #ffffff;
        --text-dark: #2b2b2b;
        --text-muted: #6b6b6b;
        --border: #e3d6cf;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(to right, #faf6f3, #f3ece8);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        color: var(--text-dark);
    }

    /* NAVBAR */
    .navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;              /* 👈 use left + right instead of width */
    padding: 16px 32px;    /* slightly reduced */
    display: flex;
    justify-content: flex-end;
    z-index: 1000;
    box-sizing: border-box;
}


    .nav-links a {
        color: var(--primary);
        font-size: 18px;
        text-decoration: none;
        margin-right: 25px;
        font-weight: 600;
        transition: color 0.3s;
    }

    .nav-links a:hover {
        color: var(--primary-light);
    }

    /* FORM CARD */
    .form-container {
        max-width: 460px;
        width: 92%;
        background: var(--card-bg);
        padding: 35px 42px;
        border-radius: 16px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.12);
        text-align: center;
        margin-top: 60px;
    }

    .form-container h1 {
        color: var(--primary);
        margin-bottom: 25px;
        font-size: 30px;
    }

    .form-group {
    margin-bottom: 0;   /* spacing now handled by .form-row */
    text-align: left;
}


    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        color: var(--text-dark);
    }

    .form-row {
    display: flex;
    gap: 35px;          /* space between left & right fields */
    margin-bottom: 25px; /* space between rows */
}


    .form-row .form-group {
        flex: 1;
    }

    .form-group input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        transition: border 0.3s, box-shadow 0.3s;
    }

    .form-group input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(90, 14, 36, 0.15);
    }

    /* BUTTON */
    .submit-btn {
        width: 100%;
        padding: 14px;
        margin-top: 15px;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        border: none;
        border-radius: 30px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
        box-shadow: 0 10px 25px rgba(90, 14, 36, 0.3);
    }

    .submit-btn:hover:enabled {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(90, 14, 36, 0.4);
    }

    .submit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        box-shadow: none;
    }

    /* ALERTS */
    .alert {
        padding: 14px 20px;
        border-radius: 10px;
        font-weight: 600;
        text-align: center;
        max-width: 420px;
        position: absolute;
        top: 12%;
        left: 50%;
        transform: translateX(-50%);
        z-index: 20;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* TEXT BELOW FORM */
    .alt-text {
        margin-top: 18px;
        font-size: 14px;
        color: var(--text-muted);
    }

    .alt-text a {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
    }

    .alt-text a:hover {
        text-decoration: underline;
    }

    /* HINTS */
    .pw-hint {
        font-size: 13px;
        margin-top: 6px;
        color: #b33;
    }

    .pw-hint.good {
        color: #155724;
    }

    .pw-hint.warn {
        color: #855c00;
    }
</style>

</head>
<body>

    <nav class="navbar">
    <div class="menu-bubble">
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
        <div class="menu-items" id="menuItems">
            <a href="index.html" title="Home">🏠</a>
            <a href="faq.html" title="FAQs">❓</a>
            <a href="contact.html" title="Contact">📩</a>
        </div>
    </div>
</nav>


    <?php 
        if (isset($_SESSION['message'])): 
            $msg = $_SESSION['message'];
            $alert_class = ($msg['type'] == 'success') ? 'alert-success' : 'alert-error';
    ?>
    <div class="alert <?php echo $alert_class; ?>">
        <?php echo htmlspecialchars($msg['text']); ?>
    </div>
    <?php
            unset($_SESSION['message']);
        endif;
    ?>

    <div class="form-container">
        <h1>Create Account</h1>
        
        

        <form action="signup.php" method="POST" autocomplete="off"> 
            <!-- Hidden dummy fields to prevent browser autofill for stored credentials -->
            <input type="text" name="fakeusernameremembered" id="fakeusernameremembered" style="position:absolute; left:-9999px; opacity:0;" autocomplete="off">
            <input type="email" name="fakeemailremembered" id="fakeemailremembered" style="position:absolute; left:-9999px; opacity:0;" autocomplete="email">
            <input type="password" name="fakepasswordremembered" id="fakepasswordremembered" style="position:absolute; left:-9999px; opacity:0;" autocomplete="current-password">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                    <div id="name-status" style="font-size:13px; margin-top:6px; color:#666"></div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off" readonly value="">
                    <div id="username-status" style="font-size:13px; margin-top:6px; color:#666"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="admission_id">Admission ID</label>
                    <input type="text" id="admission_id" name="admission_id" required>
                    <div id="admission-status" style="font-size:13px; margin-top:6px; color:#666"></div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="off" value="" readonly>
                    <div id="email-hint" class="pw-hint" aria-live="polite"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Create Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" value="">
                    <div id="pw-hint" class="pw-hint" aria-live="polite"></div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required autocomplete="new-password" value="">
                </div>
            </div>
            <button type="submit" class="submit-btn" id="submitBtn" name="submit">Sign Up</button>
        </form>
        <p class="alt-text" style="margin-top: 15px;">
            Already have an account? <a href="login.php">Login here</a> 
        </p>
    </div>
</div>

    <script>
        // Clear and temporarily lock username/email to prevent browser autofill
        document.addEventListener('DOMContentLoaded', function(){
            var nameInput = document.getElementById('name');
            var nameStatus = document.getElementById('name-status');
            var u = document.getElementById('username');
            var e = document.getElementById('email');
            var admissionInput = document.getElementById('admission_id');
            var admissionStatus = document.getElementById('admission-status');
            var pw = document.getElementById('password');
            var conf = document.getElementById('confirmPassword');
            var submitBtn = document.getElementById('submitBtn');
            var usernameStatus = document.getElementById('username-status');
            if (u) {
                try { u.value = ''; } catch(err) {}
                u.addEventListener('focus', function(){ this.removeAttribute('readonly'); });
            }
            if (e) {
                try { e.value = ''; } catch(err) {}
                e.addEventListener('focus', function(){ this.removeAttribute('readonly'); });
            }
            // Extra safety: clear values shortly after load in case autofill happens
            setTimeout(function(){ if(u) u.value=''; if(e) e.value=''; }, 250);

            // Client-side password and email checks with inline hints
            var pwHint = document.getElementById('pw-hint');
            var emailHint = document.getElementById('email-hint');
            if (submitBtn) submitBtn.disabled = true;

            // Client-side name validation
            function checkName() {
                if (!nameInput) return true;
                var val = (nameInput.value || '').trim();
                // allow unicode letters and spaces, length 3-50
                var re = /^([\p{L} ]{3,50})$/u;
                if (!val) {
                    nameStatus.textContent = '';
                    return false;
                }
                if (!re.test(val)) {
                    nameStatus.textContent = 'Name must contain only letters and spaces (3–50 chars).';
                    return false;
                }
                nameStatus.textContent = '';
                return true;
            }

            // Client-side admission_id validation: digits only
            function checkAdmission() {
                if (!admissionInput) return true;
                var val = (admissionInput.value || '').trim();
                var re = /^\d+$/;
                if (!val) {
                    admissionStatus.textContent = '';
                    return false;
                }
                if (!re.test(val)) {
                    admissionStatus.textContent = 'Admission ID must contain digits only.';
                    return false;
                }
                admissionStatus.textContent = '';
                return true;
            }

            function checkEmail() {
                var val = e.value || '';
                var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!val) {
                    emailHint.textContent = '';
                    emailHint.className = 'pw-hint';
                    return false;
                }
                if (re.test(val)) {
                    emailHint.textContent = 'Looks like a valid email.';
                    emailHint.className = 'pw-hint good';
                    return true;
                } else {
                    emailHint.textContent = 'Invalid email address format.';
                    emailHint.className = 'pw-hint';
                    return false;
                }
            }

            function checkPassword() {
                var val = pw.value || '';
                var unmet = [];
                if (val.length < 8) unmet.push('8+ characters');
                if (!/[A-Z]/.test(val)) unmet.push('an uppercase letter');
                if (!/[a-z]/.test(val)) unmet.push('a lowercase letter');
                if (!/[0-9]/.test(val)) unmet.push('a number');
                if (!/[!@#$%^&*()_+\-=[\]{};:\"\\|,.<>\/\?]/.test(val)) unmet.push('a special character');

                var match = val.length > 0 && val === (conf.value || '');
                var allCriteria = unmet.length === 0;

                // Update inline hint for password
                if (!val) {
                    pwHint.textContent = '';
                    pwHint.className = 'pw-hint';
                } else if (!allCriteria) {
                    pwHint.textContent = 'Weak — add: ' + unmet.join(', ');
                    pwHint.className = 'pw-hint';
                } else if (allCriteria && !match) {
                    pwHint.textContent = 'Looks strong — please confirm password in the second field.';
                    pwHint.className = 'pw-hint warn';
                } else {
                    pwHint.textContent = 'Strong password — good to go.';
                    pwHint.className = 'pw-hint good';
                }

                var emailOk = checkEmail();
                var nameOk = checkName();
                var admissionOk = checkAdmission();
                var allGood = allCriteria && match && emailOk && nameOk && admissionOk;
                if (submitBtn) submitBtn.disabled = !allGood;
            }

            if (pw && conf) {
                pw.addEventListener('input', checkPassword);
                conf.addEventListener('input', checkPassword);
                checkPassword();
            }
            if (e) {
                e.addEventListener('input', checkPassword);
            }
            if (nameInput) {
                nameInput.addEventListener('input', checkPassword);
                // clear initial value and remove readonly on focus (username handled separately)
                try { nameInput.value = ''; } catch(err) {}
            }

            if (admissionInput) {
                admissionInput.addEventListener('input', checkPassword);
                try { admissionInput.value = ''; } catch(err) {}
            }

            // Optional: simple username-availability check (client-side only, no AJAX)
            // We'll just warn the user to choose another username if it contains spaces or is too short
            if (u) {
                u.addEventListener('input', function(){
                    var v = u.value;
                    if (v.length < 3) {
                        usernameStatus.textContent = 'Username must be at least 3 characters.';
                    } else if (/\s/.test(v)) {
                        usernameStatus.textContent = 'No spaces allowed in username.';
                    } else {
                        usernameStatus.textContent = '';
                    }
                });
            }
        });
    </script>
<script>
function toggleMenu() {
    document.getElementById("menuItems").classList.toggle("active");
}
</script>

</body>
</html>