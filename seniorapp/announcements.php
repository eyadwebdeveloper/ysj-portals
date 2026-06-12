<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];
$stmt->close();

$stmt = $conn->prepare("SELECT a.*, u.username FROM announcements a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/announcements.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2><?= htmlspecialchars($username) ?> | 2025 YSJ Senior Application</h2>
            <a href="logout">Logout</a>
        </div>
        <div class="actions">
            <a href="application">Application</a>
            <a href="announcements" class="active">Announcements</a>
        </div>
        <?php if ($announcements): ?>
        <h3>Latest Announcements</h3>
        <div class="announcements">
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement">
                    <div>
                        <h4><?= htmlspecialchars($announcement['title']) ?></h4>
                        <h5><?= date('d M, Y', strtotime($announcement['created_at'])) ?> - Posted by YSJ management</h5>
                    </div>
                    <a href="announcement?id=<?= $announcement['id'] ?>">
                        View Announcement <i class="fa-duotone fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <h3>No Announcements yet</h3>
        <?php endif; ?>
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