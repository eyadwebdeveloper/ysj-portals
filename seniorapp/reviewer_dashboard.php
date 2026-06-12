<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

if ($_SESSION['role'] != 'reviewer') {
    if ($_SESSION['role'] != 'admin') {
        header("Location: admin_dashboard");
        exit();
    }
    if ($_SESSION['role'] != 'applicant') {
        header("Location: application");
        exit();
    }
}

$reviewer_id = (int)$_SESSION['user_id'];

$check_column = $conn->query("SHOW COLUMNS FROM applications LIKE 'assigned_to'");
if ($check_column->num_rows == 0) {

    $conn->query("ALTER TABLE applications ADD COLUMN assigned_to INT NULL AFTER status");
}

$stmt = $conn->prepare("SELECT applications.*, users.username 
                       FROM applications 
                       JOIN users ON applications.user_id = users.id 
                       WHERE assigned_to = ?");
$stmt->bind_param("i", $reviewer_id);
$stmt->execute();
$applications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
        <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

    <style>
        .status-pending { color: #FFA500; }
        .status-accepted { color: #4CAF50; }
        .status-rejected { color: #F44336; }

        .view-btn {
            color: #2196F3;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .view-btn:hover {
            background-color: #e3f2fd;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>My Applications</h2>
            <a href="logout">Logout</a>
        </div>
        <div class="actions">
            <a href="reviewer_dashboard" class="active">My Applications</a>
        </div>

        <?php if ($applications->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Applicant</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($app = $applications->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['id']) ?></td>
                        <td><?= htmlspecialchars($app['username']) ?></td>
                        <td class="status-<?= htmlspecialchars($app['status']) ?>">
    <?= htmlspecialchars(ucfirst($app['status'])) ?>
    <?php if ($app['status'] == 'reviewed'): ?>
        <br><small>Score: <?= $app['total_score'] ?>/100 (<?= $app['overall_rating'] ?>)</small>
    <?php endif; ?>
</td>

                        <td><?= date('M d, Y', strtotime($app['submitted_at'])) ?></td>
                        <td>
    <?php if ($app['status'] != 'reviewed'): ?>
        <a href="view_application?id=<?= $app['id'] ?>" class="view-btn">Review</a>
    <?php else: ?>
        <span>Reviewed</span>
    <?php endif; ?>
</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-size: 20px;margin: 50px;">No applications have been assigned to you yet.</p>
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