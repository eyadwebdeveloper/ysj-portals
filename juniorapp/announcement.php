<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: announcements");
    exit();
}

$announcement_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT a.*, u.username FROM announcements a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
$stmt->bind_param("i", $announcement_id);
$stmt->execute();
$result = $stmt->get_result();
$announcement = $result->fetch_assoc();
$stmt->close();

if (!$announcement) {
    header("Location: announcements");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($announcement['title']) ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/announcements.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2><?= htmlspecialchars($username) ?> | YSJ Senior Application</h2>
            <a href="logout">Logout</a>
        </div>
        <div class="actions">
            <a href="application">Application</a>
            <a href="announcements">Announcements</a>
        </div>
        <div class="announcement-content">
            <h3><?= htmlspecialchars($announcement['title']) ?></h3>
            <p class="meta">Posted on <?= date('F j, Y', strtotime($announcement['created_at'])) ?> by YSJ management</p>
            <br>
            <div class="content">
                <?= $announcement['content'] ?>
            </div>
            <br><br><br>
            <a href="announcements" class="back"><i class="fa-duotone fa-solid fa-arrow-left"></i> Back to Announcements</a>
            <br><br><br>
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
</body>
</html>