<?php
session_start();
include 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's application
$stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    // No application found, redirect to application form
    header("Location: application");
    exit();
}

// Fetch files
$stmt = $conn->prepare("SELECT file_path, file_type, original_name FROM application_files WHERE application_id = ?");
$stmt->bind_param("i", $application['id']);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// For editing, load draft if exists
$stmt = $conn->prepare("SELECT data, files FROM application_drafts WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($draft_data, $draft_files);
$saved_data = $saved_files = [];
if ($stmt->fetch()) {
    $saved_data = json_decode($draft_data, true) ?? [];
    $saved_files = json_decode($draft_files, true) ?? [];
}
$stmt->close();

// Merge application data with draft if exists
$display_data = $application;
if (!empty($saved_data)) {
    $display_data = array_merge($application, $saved_data);
}

// Helper for file size
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 1) . ' ' . $sizes[$i];
}

// Handle edit action
if (isset($_GET['edit']) && $_GET['edit'] == 1) {
    // Redirect to application form for editing
    header("Location: application");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View My Application</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/view_application.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .edit-btn {
            background: #a31313;
            color: #fff;
            padding: 10px 22px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-bottom: 18px;
        }
        .edit-btn:hover {
            background: #7e0e0e;
        }
        .file-list { margin: 0; padding-left: 18px; }
        .file-item { margin-bottom: 8px; }
        .file-label { font-weight: bold; color: #a31313; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>My Application</h2>
            <a href="status">Back to Status</a>
        </div>
        <div class="actions">
            <a href="status">Status</a>
            <a href="announcements">Announcements</a>
        </div>
        <div style="margin-left: 80px;margin-top: 30px;margin-bottom: 30px;">
<?php if ($application['status'] === 'pending'): ?>
    <form method="get" action="application" style="display:inline;">
        <input type="hidden" name="edit_mode" value="1">
        <button type="submit" class="edit-btn"><i class="fa-solid fa-pen-to-square"></i> Edit Application</button>
    </form>
<?php endif; ?>
</div>
        <div class="application-details">
            <div class="detail-card">
                <h3>Personal Information</h3>
                <div class="detail-row"><span class="detail-label">Full Name:</span> <span class="detail-value"><?= htmlspecialchars($display_data['full_name'] ?? $display_data['full-name'] ?? '') ?></span></div>
                <div class="detail-row"><span class="detail-label">Email:</span> <span class="detail-value"><?= htmlspecialchars($display_data['email'] ?? $display_data['email-address'] ?? '') ?></span></div>
                <div class="detail-row"><span class="detail-label">Contact Number:</span> <span class="detail-value"><?= htmlspecialchars($display_data['contact_number'] ?? $display_data['contact-number'] ?? '') ?></span></div>
                <div class="detail-row"><span class="detail-label">Country:</span> <span class="detail-value"><?= htmlspecialchars($display_data['country'] ?? '') ?></span></div>
                <div class="detail-row"><span class="detail-label">Gender:</span> <span class="detail-value"><?= htmlspecialchars(ucfirst($display_data['gender'] ?? '')) ?></span></div>
                <div class="detail-row"><span class="detail-label">Date of Birth:</span> <span class="detail-value"><?= htmlspecialchars($display_data['birth_date'] ?? $display_data['birth-date'] ?? '') ?></span></div>
                <div class="detail-row"><span class="detail-label">Institution:</span> <span class="detail-value"><?= htmlspecialchars($display_data['institution'] ?? '') ?></span></div>
            </div>
            <div class="detail-card">
                <h3>Academic Information</h3>
                <div class="detail-row"><span class="detail-label">Grade Year:</span> <span class="detail-value"><?= htmlspecialchars($display_data['grade_year'] ?? $display_data['grade-year'] ?? '') ?></span></div>
                <div class="detail-row"><span class="detail-label">GPA:</span> <span class="detail-value"><?= htmlspecialchars($display_data['gpa'] ?? '') ?></span></div>
                <div class="detail-row">
    <span class="detail-label">Fields of Interest:</span>
    <span class="detail-value">
        <?php
        $interests = $display_data['interest'] ?? [];
        if (!is_array($interests)) {
            $interests = array_filter(array_map('trim', explode(',', $interests)));
        }
        $interest_ratings = $display_data['interest_ratings'] ?? [];
        if (is_string($interest_ratings)) {
            $interest_ratings = json_decode($interest_ratings, true) ?? [];
        }
        if ($interests && count($interests)) {
            echo '<ul style="margin:0; padding-left:18px;">';
            foreach ($interests as $interest) {
                echo '<li>' . htmlspecialchars($interest);
                if (isset($interest_ratings[$interest])) {
                    echo ' <span style="color:#a31313;font-weight:bold;">(' . (int)$interest_ratings[$interest] . '/10)</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<em>No interests specified</em>';
        }
        ?>
    </span>
</div>
                <div class="detail-row"><span class="detail-label">Previous Grades:</span> <span class="detail-value"><?= htmlspecialchars($display_data['grade_previous'] ?? $display_data['grade-previous'] ?? '') ?></span></div>
            </div>
            <div class="detail-card">
                <h3>Essay Responses</h3>
                <br>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Mentorship in Scientific Research</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($display_data['first_essay'] ?? $display_data['first-essay'] ?? '')) ?></div>
                </div>
                <br><br>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Team Motivation Example</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($display_data['second_essay'] ?? $display_data['second-essay'] ?? '')) ?></div>
                </div>
                <br><br>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Research Background</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($display_data['third_essay'] ?? $display_data['third-essay'] ?? '')) ?></div>
                </div>
            </div>
            <div class="detail-card">
                <h3>Files</h3>
                <?php if ($files): ?>
                    <ul class="file-list">
                        <?php foreach ($files as $file): ?>
                            <li class="file-item">
                                <span class="file-label"><?= htmlspecialchars(ucfirst($file['file_type'])) ?>:</span>
                                <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank">
                                    <i class="fa-solid fa-file"></i> <?= htmlspecialchars($file['original_name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No files uploaded.</p>
                <?php endif; ?>
            </div>
            <div class="detail-card">
                <h3>Additional Information</h3>
                <div class="detail-row"><span class="detail-label">Weekly Commitment:</span> <span class="detail-value"><?= htmlspecialchars($display_data['hours_commitment'] ?? '') ?> hours</span></div>
                <div class="detail-row"><span class="detail-label">Available Time Blocks:</span> <span class="detail-value"><?= htmlspecialchars($display_data['time_blocks'] ?? '') ?></span></div>
                <div class="detail-row"><span class="detail-label">Heard About YSJ:</span> <span class="detail-value"><?= htmlspecialchars($display_data['hear_about'] ?? '') ?></span></div>
            </div>
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