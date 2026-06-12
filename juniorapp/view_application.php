<?php
session_start();
include 'db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Validate application ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ['min_range' => 1])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'applications' : 'reviewer_dashboard'));
    exit();
}

$app_id = (int)$_GET['id'];

// Fetch application data with prepared statement
$stmt = $conn->prepare("SELECT applications.*, users.username, users.email_address as user_email 
                       FROM applications 
                       JOIN users ON applications.user_id = users.id 
                       WHERE applications.id = ?");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'applications' : 'reviewer_dashboard'));
    exit();
}

if ($application['status'] == 'reviewed' && $_SESSION['role'] == 'reviewer') {
    header("Location: reviewer_dashboard");
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid request";
        header("Location: view_application?id=$app_id");
        exit();
    }

    // Reviewer evaluation
    if ($_SESSION['role'] == 'reviewer' && isset($_POST['suggestion'])) {
        handleReviewerSubmission($conn, $app_id, $_POST);
    } 
    // Admin decision
    elseif ($_SESSION['role'] == 'admin' && isset($_POST['action'])) {
        handleAdminDecision($conn, $app_id, $_POST['action'], $application['user_email'], $application['username']);
    }
    
    header("Location: view_application?id=$app_id");
    exit();
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function handleReviewerSubmission($conn, $app_id, $post_data) {
    $suggestion = in_array($post_data['suggestion'], ['accept', 'reject']) ? $post_data['suggestion'] : null;
    
    // Validate and sanitize scores with new max values
    $scores = [
        'ta_tm_c' => min(max((int)($post_data['ta_tm_c'] ?? 0), 0), 15),
        'academic_writing' => min(max((int)($post_data['academic_writing'] ?? 0), 0), 15),
        'personal_quality' => min(max((int)($post_data['personal_quality'] ?? 0), 0), 15),
        'demonstrated_interest' => min(max((int)($post_data['demonstrated_interest'] ?? 0), 0), 10),
        'research_background' => min(max((int)($post_data['research_background'] ?? 0), 0), 10),
        'analytic_skills' => min(max((int)($post_data['analytic_skills'] ?? 0), 0), 10),
        'academics' => min(max((int)($post_data['academics'] ?? 0), 0), 5)
    ];

    $total = array_sum($scores);
    $overall_rating = calculateOverallRating($total);
    $notes = !empty($post_data['notes']) ? trim($post_data['notes']) : null;

    $update_stmt = $conn->prepare("UPDATE applications SET 
        status = 'reviewed',
        reviewer_suggestion = ?,
        ta_tm_c = ?,
        academic_writing = ?,
        personal_quality = ?,
        demonstrated_interest = ?,
        research_background = ?,
        analytic_skills = ?,
        academics = ?,
        total_score = ?,
        overall_rating = ?,
        notes = ?
        WHERE id = ?");

    $update_stmt->bind_param("siiiiiiiissi", 
        $suggestion,
        $scores['ta_tm_c'],
        $scores['academic_writing'],
        $scores['personal_quality'],
        $scores['demonstrated_interest'],
        $scores['research_background'],
        $scores['analytic_skills'],
        $scores['academics'],
        $total,
        $overall_rating,
        $notes,
        $app_id);

    $update_stmt->execute();
    $update_stmt->close();
    header("Location: reviewer_dashboard");
}
// Function to handle admin decision
function handleAdminDecision($conn, $app_id, $action, $user_email, $username) {
    if (!in_array($action, ['approved', 'rejected'])) {
        return;
    }

    $update_stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
    $update_stmt->bind_param("si", $action, $app_id);
    $update_stmt->execute();
    $update_stmt->close();
    
}

// Calculate overall rating based on total score
function calculateOverallRating($total) {
    if ($total >= 65) return 'Excellent';
    if ($total >= 45) return 'Good';
    if ($total >= 25) return 'Mid';
    return 'Weak';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - <?= htmlspecialchars($application['username']) ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/view_application.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Application Details</h2>
            <a href="<?= $_SESSION['role'] == 'admin' ? 'applications' : 'reviewer_dashboard' ?>">Back</a>
        </div>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements">Manage Announcements</a>
            <a href="assign_reviewers">Assign Applications</a>
        </div>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'reviewer'): ?>
        <div class="actions">
            <a href="reviewer_dashboard">My Applications</a>
        </div>
        <?php endif; ?>

        <div class="application-details">
            <!-- Basic Information Card -->
            <div class="detail-card">
                <h3>Basic Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Applicant:</span>
                    <span class="detail-value"><?= htmlspecialchars($application['username']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value status-badge status-<?= htmlspecialchars($application['status']) ?>">
                        <?= htmlspecialchars(ucfirst($application['status'])) ?>
                    </span>
                </div>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <div class="detail-row">
                    <span class="detail-label">Reviewer Suggestion:</span>
                    <span class="detail-value suggestion-badge suggestion-<?= !empty($application['reviewer_suggestion']) ? htmlspecialchars($application['reviewer_suggestion']) : 'none' ?>">
                        <?= !empty($application['reviewer_suggestion']) ? 
                            htmlspecialchars(ucfirst($application['reviewer_suggestion'])) : 'No suggestion yet' ?>
                    </span>
                </div>
                <?php endif; ?>
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

            <!-- Reviewer Evaluation Form -->
            <?php if ($_SESSION['role'] == 'reviewer'): ?>
            <div class="action-card">
                <h3>Evaluation Form</h3>
                <form action="view_application?id=<?= $app_id ?>" method="POST" class="evaluation-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <?php 
                    $criteria = [
                        ['name' => 'ta_tm_c', 'label' => 'TA, TM & C', 'max' => 15],
                        ['name' => 'academic_writing', 'label' => 'Academic Writing', 'max' => 15],
                        ['name' => 'personal_quality', 'label' => 'Personal Quality', 'max' => 15],
                        ['name' => 'demonstrated_interest', 'label' => 'Demonstrated Interest', 'max' => 10],
                        ['name' => 'research_background', 'label' => 'Research Background', 'max' => 10],
                        ['name' => 'analytic_skills', 'label' => 'Analytic Skills', 'max' => 10],
                        ['name' => 'academics', 'label' => 'Academics', 'max' => 5]
                    ];
                    
                    foreach ($criteria as $criterion): ?>
                    <div class="criteria-row">
                        <label><?= htmlspecialchars($criterion['label']) ?> (0-<?= $criterion['max'] ?>):</label>
                            <input type="number" name="<?= $criterion['name'] ?>" 
                                   min="0" max="<?= $criterion['max'] ?>" 
                                   value="<?= isset($application[$criterion['name']]) ? $application[$criterion['name']] : '' ?>"
                                   class="score-input" data-max="<?= $criterion['max'] ?>">
                            <span class="rating-display"></span>
                    </div>
                    <?php endforeach; ?>

                    <div class="total-score">
    <strong>Total Score:</strong>
    <span id="total-score"><?= isset($application['total_score']) ? $application['total_score'] : 0 ?></span>/80
    <span id="overall-rating" class="rating-badge <?= isset($application['overall_rating']) ? strtolower($application['overall_rating']) : '' ?>">
        <?= isset($application['overall_rating']) ? $application['overall_rating'] : 'Weak' ?>
    </span>
</div>

                    <div class="notes-section">
                        <h4>Review Notes</h4>
                        <textarea name="notes" rows="5" placeholder="Add your evaluation notes..."><?= isset($application['notes']) ? htmlspecialchars($application['notes']) : '' ?></textarea>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="suggestion" value="accept" class="accept-btn">
                            <i class="fas fa-check"></i> Suggest Acceptance
                        </button>
                        <button type="submit" name="suggestion" value="reject" class="reject-btn">
                            <i class="fas fa-times"></i> Suggest Rejection
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Reviewer Evaluation Summary -->
            <?php if ($_SESSION['role'] == 'admin' && $application['status'] == 'reviewed'): ?>
            <div class="detail-card" style="grid-column: span 2;">
                <h3>Reviewer Evaluation</h3>

                <?php if (!empty($application['reviewer_suggestion'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Reviewer Suggestion:</span>
                        <span class="detail-value suggestion-badge suggestion-<?= htmlspecialchars($application['reviewer_suggestion']) ?>">
                            <?= htmlspecialchars(ucfirst($application['reviewer_suggestion'])) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($application['total_score'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Total Score:</span>
                        <span class="detail-value">
                            <?= htmlspecialchars($application['total_score']) ?>/100
                            <span class="rating-badge <?= strtolower($application['overall_rating']) ?>">
                                <?= htmlspecialchars($application['overall_rating']) ?>
                            </span>
                        </span>
                    </div>
                <?php endif; ?>

                    <div class="detail-row">
                        <span class="detail-label">TA, TM & C:</span>
                        <span class="detail-value"><?= htmlspecialchars($application['ta_tm_c'] ?? 'N/A') ?>/15</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Academic Writing:</span>
                        <span class="detail-value"><?= htmlspecialchars($application['academic_writing'] ?? 'N/A') ?>/15</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Personal Quality:</span>
                        <span class="detail-value"><?= htmlspecialchars($application['personal_quality'] ?? 'N/A') ?>/15</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Demonstrated Interest:</span>
                        <span class="detail-value"><?= htmlspecialchars($application['demonstrated_interest'] ?? 'N/A') ?>/10</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Research Background:</span>
                        <span class="detail-value"><?= htmlspecialchars($application['research_background'] ?? 'N/A') ?>/10</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Analytic Skills:</span>
                        <span class="detail-value"><?= htmlspecialchars($application['analytic_skills'] ?? 'N/A') ?>/10</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Academics:</span>
                        <span class="detail-value"><?= htmlspecialchars($application['academics'] ?? 'N/A') ?>/5</span>
                    </div>

                <?php if (!empty($application['notes'])): ?>
                    <div class="review-notes">
                        <h4 style="font-size: 20px;color: #a31313;">Reviewer Notes:</h4>
                        <div class="notes-content"><?= nl2br(htmlspecialchars($application['notes'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Admin Decision Form -->
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <div class="action-card">
                <h3>Admin Decision</h3>
                <form action="view_application?id=<?= $app_id ?>" method="POST" class="action-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="button-group">
                        <button type="submit" name="action" value="approved" class="accept-btn" <?= $application['status'] == 'approved' ? 'disabled' : '' ?>>
                            <i class="fas fa-check-circle"></i> Accept Application
                        </button>
                        <button type="submit" name="action" value="rejected" class="reject-btn" <?= $application['status'] == 'rejected' ? 'disabled' : '' ?>>
                            <i class="fas fa-times-circle"></i> Reject Application
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
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

    <div id="customConfirmBox" style="display:none"></div>

    <script src="js/view_application.min.js"></script>
    
</body>
</html>