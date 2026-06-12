<?php
session_start();
include 'db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: login");
    exit();
} elseif ($_SESSION['role'] === 'reviewer') {
    header("Location: reviewer_dashboard");
    exit();
} elseif ($_SESSION['role'] === 'applicant') {
    header("Location: application");
    exit();
}

// Fetch reviewers with email addresses
$reviewers = [];
$res = $conn->query("SELECT id, username, email_address FROM users WHERE role='reviewer'");
while ($row = $res->fetch_assoc()) {
    $reviewers[] = $row;
}

// Handle assignment
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $assignments = $_POST['assign_count'] ?? [];
    $status_to_assign = $_POST['status_to_assign'] ?? 'pending';

    foreach ($assignments as $reviewer_id => $count) {
        $count = (int)$count;
        if ($count > 0) {
            // Get reviewer details
            $reviewer = null;
            foreach ($reviewers as $r) {
                if ($r['id'] == $reviewer_id) {
                    $reviewer = $r;
                    break;
                }
            }

            // Get $count random unassigned applications with the chosen status
            $stmt = $conn->prepare("SELECT a.id, u.username as applicant_name, a.submitted_at 
                                  FROM applications a 
                                  JOIN users u ON a.user_id = u.id 
                                  WHERE (a.assigned_to IS NULL OR a.assigned_to = '') 
                                  AND a.status = ? 
                                  ORDER BY RAND() LIMIT ?");
            $stmt->bind_param("si", $status_to_assign, $count);
            $stmt->execute();
            $result = $stmt->get_result();
            $app_ids = [];
            while ($row = $result->fetch_assoc()) {
                $app_ids[] = $row;
            }
            $stmt->close();

            // Assign each application to the reviewer
            foreach ($app_ids as $app) {
                $update = $conn->prepare("UPDATE applications SET assigned_to = ?, status = 'under_review' WHERE id = ?");
                $update->bind_param("si", $reviewer_id, $app['id']);
                $update->execute();
                $update->close();

        
            }
        }
    }
    $success = "Applications assigned successfully! Reviewers have been notified by email.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Assign Reviewers</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

    <style>
        .bulk-assign-table { width: 100%; border-collapse: collapse; margin-top: 20px;}
        .bulk-assign-table th, .bulk-assign-table td { border: 1px solid #ccc; padding: 8px; text-align: left;}
        .bulk-assign-table th { background: #f5f5f5; }
        .success-msg { color: #388e3c; font-weight: bold; margin: 15px; padding: 10px; border-radius: 10px;border:  3px solid #388e3c;background:rgba(56, 142, 60, 0.17);}
        .error-msg { color: #d32f2f; font-weight: bold; margin-bottom: 15px;}
        input[type="number"] { width: 60px; }
        input, select {padding: 8px;}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Bulk Assign Applications to Reviewers</h2>
            <a href="admin_dashboard">Back to Dashboard</a>
        </div>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements">Manage Announcements</a>
            <a href="assign_reviewers">Assign Applications</a>
        </div>

        <?php if ($success): ?>
            <div class="success-msg"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="form" style="margin-top: 20px;">
            <div class="form-input" style="margin-bottom: 15px;">
                <label for="status_to_assign">Assign from applications with status:</label>
                <select name="status_to_assign" id="status_to_assign">
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                </select>
            </div>
            <table class="bulk-assign-table">
                <thead>
                    <tr>
                        <th style="color: #000">Reviewer</th>
                        <th style="color: #000">Number of Applications to Assign</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviewers as $reviewer): ?>
                        <tr>
                            <td><?= htmlspecialchars($reviewer['username']) ?></td>
                            <td>
                                <input type="number" name="assign_count[<?= $reviewer['id'] ?>]" min="0" max="100" value="0" style="width: 100%;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="assign" class="btn" style="margin-top: 18px;">Assign Applications</button>
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
</body>
</html>