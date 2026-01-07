<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "QuizLance");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $identifier = trim(mysqli_real_escape_string($conn, $_POST['identifier']));
    $password   = $_POST['password'];

    /* =========================
       ADMIN LOGIN (EMAIL OR USERNAME)
       ========================= */
    $admin_logged_in = false;

    // Try email first
    $admin_res = mysqli_query($conn, "SELECT * FROM Admins WHERE email='$identifier' LIMIT 1");
    if ($admin_res && mysqli_num_rows($admin_res) === 1) {
        $row = mysqli_fetch_assoc($admin_res);
        if (password_verify($password, $row['password'])) {
            $admin_logged_in = true;
        } elseif ($password === $row['password']) {
            // legacy plain-text: re-hash and update
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $safeHash = mysqli_real_escape_string($conn, $newHash);
            mysqli_query($conn, "UPDATE Admins SET password='$safeHash' WHERE id=" . intval($row['id']));
            $admin_logged_in = true;
        }
    }

    // Admin login is email-only. Username lookup intentionally skipped.

    if ($admin_logged_in && isset($row)) {
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['user_name'] = $row['name'];
        $_SESSION['role']      = 'admin';
        header("Location: admin_dashboard.php");
        exit();
    }

    /* =========================
       TEACHER LOGIN (EMAIL OR USERNAME)
       ========================= */
    $teacher_logged_in = false;

    // Try matching by email first
    $teacher_res = mysqli_query($conn, "SELECT * FROM Teachers WHERE email='$identifier' LIMIT 1");
    if ($teacher_res && mysqli_num_rows($teacher_res) === 1) {
        $row = mysqli_fetch_assoc($teacher_res);
        if ($row['status'] === 'active') {
            // verify hashed password or migrate legacy plain-text
            if (password_verify($password, $row['password'])) {
                $teacher_logged_in = true;
            } elseif ($password === $row['password']) {
                // legacy plain-text matched — re-hash and update DB
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $safeHash = mysqli_real_escape_string($conn, $newHash);
                mysqli_query($conn, "UPDATE Teachers SET password='$safeHash' WHERE id=" . intval($row['id']));
                $teacher_logged_in = true;
            }
        } else {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Your account has been deactivated. Please contact admin.'
            ];
            header("Location: login.php");
            exit();
        }
    }

    // If not logged in by email, try username (case-sensitive)
    if (!$teacher_logged_in) {
        $teacher_res = mysqli_query($conn, "SELECT * FROM Teachers WHERE BINARY username='$identifier' LIMIT 1");
        if ($teacher_res && mysqli_num_rows($teacher_res) === 1) {
            $row = mysqli_fetch_assoc($teacher_res);
            if ($row['status'] !== 'active') {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Your account has been deactivated. Please contact admin.'
                ];
                header("Location: login.php");
                exit();
            }
            if (password_verify($password, $row['password'])) {
                $teacher_logged_in = true;
            } elseif ($password === $row['password']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $safeHash = mysqli_real_escape_string($conn, $newHash);
                mysqli_query($conn, "UPDATE Teachers SET password='$safeHash' WHERE id=" . intval($row['id']));
                $teacher_logged_in = true;
            }
        }
    }

    if ($teacher_logged_in && isset($row)) {
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['user_name'] = $row['name'];
        $_SESSION['role']      = 'teacher';
        header("Location: teacher_dashboard.php");
        exit();
    }

    /* =========================
       STUDENT LOGIN (EMAIL)
       ========================= */
    $student_res = mysqli_query($conn, "SELECT * FROM Students WHERE email='$identifier' LIMIT 1");
    if ($student_res && mysqli_num_rows($student_res) === 1) {

        $row = mysqli_fetch_assoc($student_res);

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['role']      = 'student';
            header("Location: student_dashboard.php");
            exit();
        }
    }

    /* =========================
       STUDENT LOGIN (USERNAME)
       ========================= */
    $student_res = mysqli_query(
        $conn,
        "SELECT * FROM Students WHERE BINARY username='$identifier' LIMIT 1"
    );
    if ($student_res && mysqli_num_rows($student_res) === 1) {

        $row = mysqli_fetch_assoc($student_res);

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['role']      = 'student';
            header("Location: student_dashboard.php");
            exit();
        }
    }

    /* =========================
       LOGIN FAILED
       ========================= */
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => 'Invalid login credentials'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizLance - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    /* =====================
   GLOBAL RESET & SAFETY
   ===================== */
* {
    box-sizing: border-box;
}

html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #faf6f3, #f3ece8);
    color: #2b2b2b;
}

/* =====================
   THEME VARIABLES
   ===================== */
:root {
    --primary: #5A0E24;
    --primary-light: #7a1a36;
    --card-bg: #ffffff;
    --text-muted: #6b6b6b;
    --border: #e3d6cf;
}

/* =====================
   NAVBAR (SAFE VERSION)
   ===================== */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;                 /* prevents overflow */
    padding: 16px 32px;
    display: flex;
    justify-content: flex-end;
    z-index: 1000;
    background: transparent;
}

.nav-links a {
    color: var(--primary);
    font-size: 18px;
    text-decoration: none;
    margin-left: 25px;
    font-weight: 600;
}

.nav-links a:hover {
    color: var(--primary-light);
}

/* =====================
   PAGE CENTERING
   ===================== */
.page-wrapper {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding-top: 80px; /* space for navbar */
}

/* =====================
   CARD CONTAINER
   ===================== */
.form-container {
    max-width: 460px;
    width: 92%;
    background: var(--card-bg);
    padding: 40px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.12);
}

/* =====================
   HEADINGS
   ===================== */
.form-container h1 {
    color: var(--primary);
    margin-bottom: 25px;
}

/* =====================
   FORM FIELDS
   ===================== */
.form-group {
    margin-bottom: 22px;
    text-align: left;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
}

.form-group input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid var(--border);
    outline: none;
}

.form-group input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(90, 14, 36, 0.15);
}

/* =====================
   PRIMARY BUTTON
   ===================== */
.submit-btn {
    width: 100%;
    padding: 14px;
    margin-top: 10px;
    border-radius: 30px;
    border: none;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(90, 14, 36, 0.3);
}

.submit-btn:hover {
    transform: translateY(-2px);
}

/* =====================
   GOOGLE BUTTON
   ===================== */
.google-btn {
    background: #fff;
    color: #444;
    border: 1px solid var(--border);
    box-shadow: none;
}

.google-btn i {
    color: #4285F4; /* official Google blue */
    margin-right: 8px;
}

/* =====================
   DIVIDER
   ===================== */
.divider {
    margin: 25px 0;
    border-top: 1px solid var(--border);
    position: relative;
}

.divider span {
    position: absolute;
    top: -12px;
    background: var(--card-bg);
    padding: 0 10px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 13px;
    color: var(--text-muted);
}

/* =====================
   EXTRA TEXT
   ===================== */
.alt-text {
    margin-top: 15px;
    font-size: 14px;
}

.alt-text a {
    color: var(--primary);
    font-weight: 600;
    text-decoration: none;
}

/* =====================
   ALERTS
   ===================== */
.alert {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 600;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
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

    <div class ="page-wrapper">
    <div class="form-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message']['type']; ?>">
                <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
            </div>
        <?php unset($_SESSION['message']); endif; ?>
        <h1>Login to QuizLance</h1>
        <form method="POST" autocomplete="off">
            <!-- Prevent browser autofill: hidden dummy fields (keeps password managers from filling real inputs) -->
            <input type="text" name="fakeusernameremembered" id="fakeusernameremembered" style="position:absolute; left:-9999px; opacity:0;" autocomplete="username">
            <input type="password" name="fakepasswordremembered" id="fakepasswordremembered" style="position:absolute; left:-9999px; opacity:0;" autocomplete="current-password">

            <div class="form-group"><label>Email or Username</label><input type="text" id="identifier" name="identifier" required autocomplete="off" readonly value=""></div>
            <div class="form-group"><label>Password</label><input type="password" id="password" name="password" required autocomplete="new-password" value=""></div>
            <a href="forgot_password.php">Forgot Password?</a> 
            <button type="submit" class="submit-btn">Login</button>
        </form>

        <div class="divider"><span>OR</span></div>

        <a href="google_login.php" class="submit-btn google-btn">
            <i class="fab fa-google"></i> Continue with Google
        </a>

        <p class="alt-text">Don't have an account? <a href="signup.php">Sign Up here</a></p>
    </div>
        </div>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var id = document.getElementById('identifier');
            var pw = document.getElementById('password');
            try { if(id) id.value = ''; } catch(e){}
            try { if(pw) pw.value = ''; } catch(e){}
            if (id) {
                id.addEventListener('focus', function(){
                    this.removeAttribute('readonly');
                    
                    setTimeout(function(){ try { id.value = ''; } catch(e){} }, 50);
                });
                
                setTimeout(function(){ if(id) id.value=''; }, 200);
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