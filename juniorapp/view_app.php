<?php
session_start();
include 'db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Fetch user's application
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If no application exists, redirect to application form
if (!$application) {
    header("Location: application");
    exit();
}

$interests = [];
$interest_ratings = [];
if (!empty($application['interest'])) {
    $interests = array_map('trim', explode(',', $application['interest']));
}
if (!empty($application['interest_ratings'])) {
    $interest_ratings = json_decode($application['interest_ratings'], true) ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Application - <?= htmlspecialchars($application['username']) ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/view_application.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>My Application</h2>
            <a href="status">Back to Status</a>
        </div>
        
        <div class="actions">
            <a href="application">Application</a>
            <a href="announcements">Announcements</a>
            <a href="status">Application Status</a>
        </div>

        <div class="application-details">

        <a href="application.php?edit_mode=1">Edit Application</a>
            <!-- Basic Information Card -->
            <div class="detail-card">
                <h3>Basic Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value status-badge status-<?= htmlspecialchars($application['status']) ?>">
                        <?= htmlspecialchars(ucfirst($application['status'])) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Submitted At:</span>
                    <span class="detail-value">
                        <?= date('M d, Y H:i', strtotime($application['submitted_at'])) ?>
                    </span>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="detail-card">
                <h3>Contact Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Full Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['full_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">
                        <a href="mailto:<?= htmlspecialchars($application['email']) ?>">
                            <?= htmlspecialchars($application['email']) ?>
                        </a>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['contact_number']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Country:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['country']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value"><?= htmlspecialchars(ucfirst($application['gender'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value"><?= date('M d, Y', strtotime($application['birth_date'])) ?></span>
                </div>
            </div>

            <!-- Academic Information Card -->
            <div class="detail-card">
                <h3>Academic Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Institution:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['institution']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Grade Year:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['grade_year']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">GPA:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['gpa']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fields of Interest:</span>
                    <span class="detail-value">
                        <?php if ($interests): ?>
                            <ul style="margin:0; padding-left:18px;">
                                <?php foreach ($interests as $interest): ?>
                                    <li>
                                        <?= htmlspecialchars($interest) ?>
                                        <?php if (isset($interest_ratings[$interest])): ?>
                                            <span style="color:#a31313;font-weight:bold;">(<?= (int)$interest_ratings[$interest] ?>/10)</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <em>No interests specified</em>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Previous Grades:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['grade_previous']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Weekly Commitment:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['hours_commitment']) ?> hours</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Available Time Blocks:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['time_blocks']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Heard About YSJ:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['hear_about']) ?></span>
                </div>
            </div>

            <!-- Essay Responses -->
            <div class="detail-card" style="grid-column: span 2;">
                <h3>Essay Responses</h3>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Extracurricular Activities & Achievements</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($application['first_essay'])) ?></div>
                </div>
                <br>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Role of Science & Motivation</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($application['second_essay'])) ?></div>
                </div>
                <br>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Why YSJ?</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($application['third_essay'])) ?></div>
                </div>
                <br>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Previous Research Experience</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($application['fourth_essay'])) ?></div>
                </div>
                <br>
                <div class="essay-response">
                    <h4 style="font-size: 20px;color: #a31313;margin-bottom: 10px;">Additional Information</h4>
                    <div class="essay-content" style="font-size: 18px;"><?= nl2br(htmlspecialchars($application['additional_essay'])) ?></div>
                </div>
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