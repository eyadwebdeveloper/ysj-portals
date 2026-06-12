<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];

$username = "Unknown User";
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($user = $result->fetch_assoc()) {
    $username = htmlspecialchars($user['username'] ?? "Unknown User");
}
$stmt->close();

$status = "pending";
$application = null;
$stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($application = $result->fetch_assoc()) {
    $status = $application['status'] ?? "pending";
}
$stmt->close();
echo '<script>
document.addEventListener("contextmenu", function(e) {
    e.preventDefault();
});

// Optional: Prevent keyboard shortcuts for dev tools
document.addEventListener("keydown", function(e) {
    // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
    if (e.key === "F12" || 
        (e.ctrlKey && e.shiftKey && e.key === "I") || 
        (e.ctrlKey && e.shiftKey && e.key === "J") || 
        (e.ctrlKey && e.shiftKey && e.key === "C") || 
        (e.ctrlKey && e.key === "U")) {
        e.preventDefault();
    }
});
</script>';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $username; ?> | 2025 YSJ Senior Application Status</title>
        <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">

    <link rel="stylesheet" href="css/admin.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

    <style>
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        p a {
            color: #a31313;
        }
        .popup-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 700px;
            width: 80%;
        }
        .close-popup {
            margin-top: 15px;
            padding: 5px 15px;
            cursor: pointer;
        }
        .popup-content p {
            font-size: 18px;
        }
        .popup-content p.accepted {
            font-size: 20px;
        }
        .popup-content h3.accepted {
            color: green;
            text-align: center;
            font-size: 27px;
        }
        .popup-content h3.rejected {
            color: red;
            text-align: center;
            font-size: 27px;
        }
        button {
            color: #fff;
            background: #a31313;
            padding: 10px;
            font-size: 18px;
            border: none;
            width: 100%;
            border-radius: 5px;
            text-align: center;
        }
        .success-msg { color: #388e3c; font-weight: bold; margin: 15px; padding: 10px; border-radius: 10px;border:  3px solid #388e3c;background:rgba(56, 142, 60, 0.17);}
        .error-msg { color: #d32f2f; font-weight: bold; margin-bottom: 15px;}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
        <h2><?php echo htmlspecialchars($username); ?> | 2025 YSJ Senior Application Status</h2>
          <a href="logout">Logout</a>
        </div>
        <div class="actions">
            <a href="status">Status</a>
            <a href="announcements">Announcements</a>
        </div>
        <?php 
        if (isset($_SESSION['error'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

// Display success messages if redirected from submission
if (isset($_SESSION['success'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}
        ?>
        <div class="divs">
            <div class="big-div">
                <h3>Status Updates</h3>
                <?php if ($status == 'under_review' || $status == 'pending' || $status == 'reviewed'): ?>
                    <p>There is no status updates until now</p>
                <?php else: ?>
                    <p>A new status update was posted on <?php echo date('j M, Y'); ?> 
                        <a href="#" onclick="showStatusUpdate('<?php echo $status; ?>')">View update</a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="small-divs">
                <div class="first-small">
                    <h3>Checklist</h3>
                    <div class="checkbox-container">
                      <label class="ios-checkbox red">
                        <input type="checkbox" checked disabled />
                        <div class="checkbox-wrapper">
                          <div class="checkbox-bg"></div>
                          <svg class="checkbox-icon" viewBox="0 0 24 24" fill="none">
                            <path class="check-path" d="M4 12L10 18L20 6" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                          </svg>
                        </div>
                      </label>
                       <p>Registeration</p>
                    </div>
                    <div class="checkbox-container">
                      <label class="ios-checkbox red">
                        <input type="checkbox" <?php echo in_array($status, ['approved', 'rejected']) ? 'checked' : ''; ?> disabled />
                        <div class="checkbox-wrapper">
                          <div class="checkbox-bg"></div>
                          <svg class="checkbox-icon" viewBox="0 0 24 24" fill="none">
                            <path class="check-path" d="M4 12L10 18L20 6" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                          </svg>
                        </div>
                      </label>
                       <p>Results</p>
                    </div>
                </div>
                <div class="second-small">
                    <h3>Admission Status</h3>
                    <p>
                        <?php 
                        if ($status == 'pending') {
                            echo "Your Application is submitted and not reviewed yet, you can view <a href='view_app'>your application here</a>";
                        } elseif ($status == 'under_review') {
                            echo "Your Application is submitted and is under review";
                        } elseif ($status == 'reviewed') {
                            echo "Your Application is reviewed and waiting for decisions";
                        } elseif ($status == 'approved') {
                            echo "Congratulations! Your application has been accepted";
                        } elseif ($status == 'rejected') {
                            echo "Your application has not been accepted this time";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div id="statusPopup" class="popup">
        <div class="popup-content">
            <h3 id="popupTitle">Status Update</h3>
            <div id="popupMessage"></div>
                <button class="close-popup" onclick="hideStatusUpdate()">Close</button>
        </div>
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
        <div id="customMessageBox" style="display:none;position:fixed;top:30px;left:50%;transform:translateX(-50%);padding:18px 28px;background:#ff4444;color:#fff;border-radius:7px;z-index:10000;box-shadow:0 2px 10px rgba(0,0,0,0.2);font-size:18px;min-width:300px;text-align:center;"></div>

    <script>

</script>
    <script src="js/status.min.js"></script>
</body>
</html>