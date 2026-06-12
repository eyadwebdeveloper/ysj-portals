<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

$response = [
    'success' => false, 
    'message' => '', 
    'redirect' => '',
    'error_type' => ''
];

$status_row = $conn->query("SELECT application_status FROM app_action LIMIT 1")->fetch_assoc();
if ($status_row && $status_row['application_status'] === 'closed') {
    include 'closed.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email_address'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = 'applicant'; 

        // Validate fields
        if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
            $response['message'] = "All fields are required!";
            $response['error_type'] = 'required_fields';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } elseif ($password !== $confirm_password) {
            $response['message'] = "Passwords do not match!";
            $response['error_type'] = 'password_mismatch';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        // Check if username exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$check_stmt) {
            $response['message'] = "Database error: " . $conn->error;
            $response['error_type'] = 'db_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        $check_stmt->bind_param("s", $username);
        if (!$check_stmt->execute()) {
            $response['message'] = "Database error: " . $check_stmt->error;
            $response['error_type'] = 'db_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        $check_result = $check_stmt->get_result();
        if ($check_result === false) {
            $response['message'] = "Database error: " . $check_stmt->error;
            $response['error_type'] = 'db_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        if ($check_result->num_rows > 0) {
            $response['message'] = "Username already exists!";
            $response['error_type'] = 'username_exists';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        // Check if email exists
        $check_stmt_email = $conn->prepare("SELECT id FROM users WHERE email_address = ?");
        if (!$check_stmt_email) {
            $response['message'] = "Database error: " . $conn->error;
            $response['error_type'] = 'db_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        $check_stmt_email->bind_param("s", $email);
        if (!$check_stmt_email->execute()) {
            $response['message'] = "Database error: " . $check_stmt_email->error;
            $response['error_type'] = 'db_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        $check_result_email = $check_stmt_email->get_result();
        if ($check_result_email === false) {
            $response['message'] = "Database error: " . $check_stmt_email->error;
            $response['error_type'] = 'db_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        if ($check_result_email->num_rows > 0) {
            $response['message'] = "Email already registered!";
            $response['error_type'] = 'email_exists';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        // Proceed with registration if all checks pass
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Generate user ID
        $seq_stmt = $conn->query("INSERT INTO user_id_sequence VALUES ()");
        $user_id_num = $conn->insert_id;
        $user_id = 'JYSJ0' . str_pad($user_id_num, 2, '0', STR_PAD_LEFT);

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (id, username, email_address, password, role) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            $response['message'] = "Database error: " . $conn->error;
            $response['error_type'] = 'db_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

        $stmt->bind_param("sssss", $user_id, $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['redirect'] = "login";
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            error_log("Registration SQL Error: " . $stmt->error);
            $response['message'] = "Error: " . $stmt->error;
            $response['error_type'] = 'registration_error';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
        $stmt->close();
        $check_stmt->close();
        $check_stmt_email->close();
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | 2025 YSJ Junior Application</title>
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="stylesheet" href="css/admin.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
    .input-error {
        border: 2px solid #ff4444 !important;
        background-color: #ffeeee !important;
    }
    #customMessageBox {
        display: none;
        position: fixed;
        top: 30px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 25px;
        background: #ff4444;
        color: #fff;
        border-radius: 5px;
        z-index: 10000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        font-size: 16px;
        min-width: 300px;
        text-align: center;
    }
    .warning {
        padding: 10px;
        background:#FEF7D1;
        color: #755118;
        border-radius: 5px;
        font-size: 18px;
    }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Signup | 2025 YSJ Junior Application</h2>
        </div>

        <div class="actions">
            <a href="login">Login</a>
            <a href="register">Create account</a>
        </div>

        <form class="form" id="registerForm" action="register" method="POST">
            <div class="row">
                <div class="input-group">
                    <i class="fa-duotone fa-solid fa-user"></i>
                    <label for="username">Username</label>
                    <input class="input" type="text" name="username" autofocus required>
                </div>
                <div class="input-group">
                    <i class="fa-duotone fa-solid fa-envelope-open-text"></i>
                    <label for="email_address">Email Address</label>
                    <input class="input" type="email" name="email_address" required>
                </div>
            </div>
            <div class="row">
                <div class="input-group">
                    <i class="fa-duotone fa-solid fa-lock-keyhole"></i>
                    <label for="password">Password</label>
                    <input class="input" type="password" name="password" required>
                </div>
                <div class="input-group">
                    <i class="fa-duotone fa-solid fa-check"></i>
                    <label for="confirm_password">Confirm Password</label>
                    <input class="input" type="password" name="confirm_password" required>
                </div>
            </div>
            <div class="warning">
                <p><strong>IMPORTANT: </strong> Make sure to remember your password, as even if you contact us, there is no way to regain access to your account if you forget it.</p>
            </div>
            <button class="btn" type="submit" name="register">Register</button>
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
    <div id="customMessageBox"></div>

    <script src="js/register.min.js"></script>
        <script src="js/common.min.js"></script>

</body>
</html>