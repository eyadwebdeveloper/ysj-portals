<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role'])) {
    header("Location: login");
    exit();
}

if ($_SESSION['role'] != 'admin') {
    header("Location: " . ($_SESSION['role'] == 'reviewer' ? "reviewer_dashboard" : "application"));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $app_id = $_POST['app_id'] ?? '';
    $reviewer_id = $_POST['reviewer_id'] ?? '';
    if (empty($app_id)) {
        die("Application ID is required");
    }
    if (empty($reviewer_id)) {
        die("Reviewer selection is required");
    }

    // Get reviewer details
    $stmt = $conn->prepare("SELECT id, username, email_address FROM users WHERE id = ? AND role = 'reviewer'");
    $stmt->bind_param("s", $reviewer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Error: Selected reviewer does not exist or is not a reviewer");
    }
    $reviewer = $result->fetch_assoc();
    $stmt->close();

    // Get application details for email
    $stmt = $conn->prepare("SELECT a.*, u.username as applicant_name 
                           FROM applications a 
                           JOIN users u ON a.user_id = u.id 
                           WHERE a.id = ?");
    $stmt->bind_param("s", $app_id);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Update application
    $stmt = $conn->prepare("UPDATE applications SET assigned_to = ?, status = 'under_review' WHERE id = ?");
    $stmt->bind_param("ss", $reviewer_id, $app_id);

    if (!$stmt->execute()) {
        die("Error updating application: " . $stmt->error);
    }
    $stmt->close();

    // Log status change
    if ($conn->query("SHOW TABLES LIKE 'application_status_log'")->num_rows > 0) {
        $log_stmt = $conn->prepare("INSERT INTO application_status_log 
                                  (application_id, old_status, new_status, changed_by, notes) 
                                  VALUES (?, ?, ?, ?, ?)");
        $notes = 'Assigned to reviewer';
        $old_status = 'pending';
        $new_status = 'under_review';
        $changed_by = $_SESSION['user_id'];

        $log_stmt->bind_param("issss", $app_id, $old_status, $new_status, $changed_by, $notes);
        $log_stmt->execute();
        $log_stmt->close();
    }

    

    header("Location: applications");
    exit();
}

$app_id = $_GET['id'] ?? '';
if (empty($app_id)) {
    die("Invalid application ID");
}

$stmt = $conn->prepare("SELECT id, username FROM users WHERE role='reviewer'");
$stmt->execute();
$reviewers = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Reviewer</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

    <style>
        select {
            padding: 8px;
            width: 100%;
            max-width: 400px;
            margin-bottom: 15px;
        }
        .assign-form {
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Assign reviewer</h2>
            <a href="applications">Back to Applications</a>
        </div>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements">Manage Announcements</a>
            <a href="assign_reviewers">Assign Applications</a>
        </div>

        <div class="assign-form">
            <form action="assign_reviewer" class="form" method="POST">
                <input type="hidden" name="app_id" value="<?= htmlspecialchars($app_id) ?>">

                <div class="form-group">
                    <label for="reviewer_id">Select Reviewer:</label>
                    <select name="reviewer_id" id="reviewer_id" required>
                        <option value="">-- Select Reviewer --</option>
                        <?php while ($reviewer = $reviewers->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($reviewer['id']) ?>">
                            <?= htmlspecialchars($reviewer['username']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn">Assign Reviewer</button>
            </form>
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