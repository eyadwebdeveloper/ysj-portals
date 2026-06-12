<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

include 'db.php';

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'reviewer') {
    header("Location: reviewer_dashboard");
    exit();
    }
    if ($_SESSION['role'] == 'admin') {
    header("Location: admin_dashboard");
    exit();
    }
    if ($_SESSION['role'] == 'applicant') {
    header("Location: application");
    exit();
    }
}


$response = ['success' => false, 'message' => '', 'redirect' => ''];

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = "CSRF token validation failed";
        echo json_encode($response);
        exit();
    }

    // Initialize login attempts counter
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_login_attempt'] = time();
    }

    // Check for brute force attempts
    if ($_SESSION['login_attempts'] > 5 && (time() - $_SESSION['last_login_attempt'] < 300)) {
        $response['message'] = "Too many login attempts. Please try again later.";
        echo json_encode($response);
        exit();
    } 
        $email = $_POST['email_address'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT * FROM users WHERE email_address=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                // Reset login attempts on success
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_login_attempt']);

                if ($user['role'] == 'applicant') {
                    $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id=?");
                    $stmt->bind_param("s", $user['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $response['redirect'] = "status";
                    } else {
                        $response['redirect'] = "application";
                    }
                } elseif ($user['role'] == 'admin') {
                    $response['redirect'] = "admin_dashboard";
                } elseif ($user['role'] == 'reviewer') {
                    $response['redirect'] = "reviewer_dashboard";
                }
                $response['success'] = true;
                echo json_encode($response);
                exit();
            } else {
                $response['message'] = "Invalid username or password!";
                $_SESSION['login_attempts']++;
                $_SESSION['last_login_attempt'] = time();
                echo json_encode($response);
                exit();
            } 
        } else {
            $response['message'] = "Invalid username or password!";
            $_SESSION['login_attempts']++;
            $_SESSION['last_login_attempt'] = time();
            echo json_encode($response);
            exit();
        }
        $stmt->close();
}

// If not an AJAX request, show the HTML page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | 2025 YSJ Senior Application</title>
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Login | 2025 YSJ Senior Application</h2>
        </div>
        
        <div class="actions">
            <a href="login">Login</a>
            <a href="register">Create account</a>
        </div>
        
        <form class="form" action="login" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="row">
                <div class="input-group">
                    <i class="fa-duotone fa-solid fa-envelope-open-text"></i>
                    <label for="email_address">Email Address</label>
                    <input class="input" type="email" name="email_address" autofocus required>
                </div>
            </div>
            <div class="row">
                <div class="input-group">
                    <i class="fa-duotone fa-solid fa-lock-keyhole"></i>
                    <label for="password">Password</label>
                    <input class="input" type="password" name="password" required>
                </div>
            </div>
            
            <button class="btn" type="submit" name="login">Login</button>
        </form>
    </div>
    <footer>
        <h2>If you encounter any problem please contact us</h2>
        <div class="social-icons">
            <a href="https://www.facebook.com/YouthScienceJournall" class="social-icon">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
            <a href="https://www.instagram.com/ysciencejournal?igsh=MWR3M3ZwYWxod3My" class="social-icon">
                <i class="fa-brands fa-instagram"></i>
            </a>
            <a href="https://www.linkedin.com/company/ysj/" class="social-icon">
                <i class="fa-brands fa-linkedin-in"></i>
            </a>
            <a href="mailto:ysciencejournal@gmail.com" class="social-icon">
                <i class="fa-duotone fa-solid fa-envelope-open-text"></i>
            </a>
        </div>
        <hr>
        <p>&copy; Youth Science Journal. All rights reserved</p>
    </footer>
    <div id="customMessageBox" style="display:none; position:fixed; top:30px; left:50%; transform:translateX(-50%); padding:15px 25px; background:#ff4444; color:#fff; border-radius:5px; z-index:10000; box-shadow:0 2px 10px rgba(0,0,0,0.2); font-size:16px; min-width:300px; text-align:center;"></div>
        <script src="js/login.min.js"></script>

    <script src="js/common.min.js"></script>
</body>
</html>