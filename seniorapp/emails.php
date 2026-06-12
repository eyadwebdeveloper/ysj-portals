<?php
// filepath: c:\phprunner\xampp\htdocs\portal\emails.php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login");
    exit();
}

$result = $conn->query("SELECT email_address FROM users ORDER BY email_address ASC");
$emails = [];
while ($row = $result->fetch_assoc()) {
    $emails[] = $row['email_address'];
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .textt {
            width: 90%;
        }
        textarea {
            padding: 8px;
            width: 100%;
        }
        textarea, ul {
            margin: 50px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
          <h2>Seniors Emails</h2>
          <a href="admin_dashboard" style="cursor: pointer;">Back to dashboard</a>
        </div>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements" class="active">Manage Announcements</a>
            <a href="emails" class="active">Seniors Emails</a>
        </div>

        <div class="textt">
            <textarea readonly><?php echo htmlspecialchars(implode(", ", $emails)); ?></textarea>
        </div>
        <ul>
            <?php foreach ($emails as $email): ?>
                <li><?= htmlspecialchars($email) ?></li>
            <?php endforeach; ?>
        </ul>
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
    <div id="customConfirmBox" style="display:none;position:fixed;top:30px;left:50%;transform:translateX(-50%);padding:18px 28px;background:#ff9800;color:#fff;border-radius:7px;z-index:10001;box-shadow:0 2px 10px rgba(0,0,0,0.2);font-size:18px;min-width:320px;text-align:center;"></div>


</body>
</html>