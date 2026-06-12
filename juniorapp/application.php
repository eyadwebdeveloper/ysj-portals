<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$status_row = $conn->query("SELECT application_status FROM app_action LIMIT 1")->fetch_assoc();
if ($status_row && $status_row['application_status'] === 'closed') {
    include 'closed.php';
    exit();
}

// Display any submission errors
if (isset($_SESSION['error'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

if (isset($_SESSION['application_message'])) {
    $message = $_SESSION['application_message'];
    $isError = !($_SESSION['application_success'] ?? false);
    unset($_SESSION['application_message']);
    unset($_SESSION['application_success']);
    
    // Display the message (style differently for errors/success)
    echo '<div class="'.($isError ? 'error' : 'success').'-message">'.$message.'</div>';
}

// Display success messages if redirected from submission
if (isset($_SESSION['success'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email_address FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id); 
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];
$email = $user['email_address'];


if ($_SESSION['role'] == 'reviewer') {
    header("Location: reviewer_dashboard");
    exit();
}

// Check if user has an existing application
$stmt = $conn->prepare("SELECT * FROM applications WHERE user_id=?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

// Allow editing if in edit mode, otherwise redirect to status if application exists
if ($application && !isset($_GET['edit_mode'])) {
    header("Location: status");
    exit();
}

// Initialize saved data arrays
$saved_data = [];

// Load application data if in edit mode (for editing submitted/final application)
if ($application && isset($_GET['edit_mode'])) {
    // Convert application data to match form field names
    $saved_data = [
        'full_name' => $application['full_name'],
        'email-address' => $application['email'],
        'contact-number' => $application['contact_number'],
        'country' => $application['country'],
        'gender' => $application['gender'],
        'birth-date' => $application['birth_date'],
        'institution' => $application['institution'],
        'grade-year' => $application['grade_year'],
        'gpa' => $application['gpa'],
        'interest' => $application ? explode(',', $application['interest']) : [],
        'interest_ratings' => $application ? json_decode($application['interest_ratings'], true) : [],
        'grade-previous' => $application['grade_previous'],
        'first-essay' => $application['first_essay'],
        'second-essay' => $application['second_essay'],
        'third-essay' => $application['third_essay'],
        'fourth-essay' => $application['fourth_essay'],
        'additional-essay' => $application['additional_essay'],
        'hours_commitment' => $application['hours_commitment'],
        'time_blocks' => $application['time_blocks'],
        'hear_about' => $application['hear_about']
    ];
    

   
    $stmt->close();
    $_SESSION['saved_application'] = $saved_data;
}

// Check for draft data if not in edit mode
if (!$application || !isset($_GET['edit_mode'])) {
    $stmt = $conn->prepare("SELECT data, files FROM application_drafts WHERE user_id=?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($draft_data, $draft_files);
    if ($stmt->fetch()) {
        $saved_data = json_decode($draft_data, true) ?? [];
        $saved_files = json_decode($draft_files, true) ?? [];
        // Keep session in sync with db draft
        $_SESSION['saved_application'] = $saved_data;
        $_SESSION['saved_files'] = $saved_files;
    }
    $stmt->close();
}

// If we have saved data in session, use that (overrides draft data)
if (isset($_SESSION['saved_application'])) {
    $saved_data = $_SESSION['saved_application'];
}

if (isset($_SESSION['saved_files'])) {
    $saved_files = $_SESSION['saved_files'];
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 1) . ' ' . $sizes[$i];
}

$interest_options = [
    "Aerospace Engineering",
    "Architecture",
    "Arts",
    "Biology",
    "Business, Entrepreneurship, & Strategy",
    "Chemistry",
    "Classics",
    "Computer Science",
    "Data Science",
    "Economics",
    "Education",
    "Engineering (not Aerospace Engineering)",
    "Environmental Science",
    "Gender Studies & Inequality",
    "History & Civilizations",
    "International Relations & Government",
    "Linguistics",
    "Mathematics",
    "Medicine & Public Health",
    "Neuroscience & Neurobiology",
    "Philosophy",
    "Physics & Astrophysics",
    "Psychology",
    "Public Policy",
    "Robotics",
    "Sociology & Anthropology"
];

// Get selected interests from saved data if available
$selected_interests = [];
if (isset($saved_data['interest'])) {
    if (is_array($saved_data['interest'])) {
        $selected_interests = $saved_data['interest'];
    } else {
        $selected_interests = explode(',', $saved_data['interest']);
    }
}

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
    <title>Application Form</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/application.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
       <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2><?php echo htmlspecialchars($username); ?> | 2025 YSJ Senior Application</h2>
            <a href="logout">Logout</a>
        </div>
        <div class="actions">
            <a href="application">Application</a>
            <a href="announcements">Annoucements</a>
        </div>

        <form class="form" id="applicationForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">
            <input type="hidden" name="final_submission" id="final_submission" value="0">
               <div class="form-page">
                <h1 style="text-align: center;font-size: 30px;line-height: 30px;margin-bottom: 20px;">Welcome the 2025 YSJ Junior Program Application Portal !!</h1>
                <p style="font-size: 18px;;">Welcome to the Junior Application for the 2025 season of the annual junior research program.  Our mission is to nurture young researchers, and we're thrilled to extend an invitation to all of you to join the new program board. <br><br>In our 10-week FREE program, you conduct research supervised by an experienced mentor and create an original research paper meeting international standards!  <br><br>Benefits include: <br><ul style="padding-left: 20px"><li>Access to exclusive materials to master the research process.</li><li>Building strong connections with peers and experts.</li><li>Standing out in admissions and showcasing your skills.</li><li>Tools to excel in research competitions and conferences.</li></ul> <br><br>Even with no prior experience, you gain the tools needed to be the researcher you always wished to be! <br><br>For any inquiries or concerns, please reach out via email.</p>
               </div>
                <div class="form-page">
                    <h2>Personal Information</h2>
                <br>
                <div class="form-input">
                    <label for="full_name">Full Name (First and Second) <span>*</span></label>
                    <p class="label-description">This name will appear in all communications by the journal, including emails, posts, and websites.</p>
                    <input type="text" name="full_name" placeholder="Your Answer" required  
                           value="<?php echo isset($saved_data['full_name']) ? htmlspecialchars($saved_data['full_name']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="email-address">Personal Email Address <span>*</span></label>
                    <p class="label-description">Please ensure the accuracy of your email address as all subsequent communications regarding the application process will be sent to this email.</p>
                    <input type="email" name="email-address" placeholder="Your Answer" required
                           value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>
                <div class="form-input">
                    <label for="contact-number">Contact Number <span>*</span></label>
                    <input type="tel" name="contact-number" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['contact-number']) ? htmlspecialchars($saved_data['contact-number']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="country">Country of Nationality <span>*</span></label>
                    <input type="text" name="country" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['country']) ? htmlspecialchars($saved_data['country']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="gender">Gender <span>*</span></label>
                    <div class="radio-input">
                        <label class="label">
                            <input type="radio" name="gender" value="male" <?php echo (isset($saved_data['gender']) && $saved_data['gender'] == 'male') ? 'checked' : ''; ?> required>
                            <p class="text">Male</p>
                        </label>
                        <label class="label">
                            <input type="radio" name="gender" value="female" <?php echo (isset($saved_data['gender']) && $saved_data['gender'] == 'female') ? 'checked' : ''; ?> required>
                            <p class="text">Female</p>
                        </label>
                    </div>
                </div>
                <div class="form-input">
                    <label for="birth-date">Date of Birth <span>*</span></label>
                    <input type="date" name="birth-date" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['birth-date']) ? htmlspecialchars($saved_data['birth-date']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="institution">Institution <span>*</span></label>
                    <input type="text" name="institution" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['institution']) ? htmlspecialchars($saved_data['institution']) : ''; ?>">
                </div>
                </div>
               

                <div class="form-page">
                    <h2>Academic Information</h2>
                <br>
                <div class="form-input">
                    <label for="grade-year">Grade Year <span>*</span></label>
                    <input type="text" name="grade-year" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['grade-year']) ? htmlspecialchars($saved_data['grade-year']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="gpa">GPA <span>*</span></label>
                    <p class="label-description">Please include your school's grading scale: Example: 3.7/4.0 or 87/100</p>
                    <input type="text" name="gpa" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['gpa']) ? htmlspecialchars($saved_data['gpa']) : ''; ?>">
                </div>
<div class="form-input">
    <label for="interest">Fields of Interest <span>*</span></label>
    <div class="select-container" id="custom-interest-select">
        <div class="select-btn">
            <span class="btn-text">Select Fields</span>
            <span class="arrow-dwn">
                <i class="fa-solid fa-chevron-down"></i>
            </span>
        </div>
        <ul class="list-items">
            <?php
            foreach ($interest_options as $option) {
                $checked = in_array($option, $selected_interests) ? 'checked' : '';
                echo '<li class="item'.($checked ? ' checked' : '').'" data-value="'.htmlspecialchars($option).'">
                        <span class="checkbox"><i class="fa-solid fa-check check-icon"></i></span>
                        <span class="item-text">'.htmlspecialchars($option).'</span>
                    </li>';
            }
            ?>
        </ul>
    </div>
    <!-- Hidden input to store selected interests as an array -->
    <input type="hidden" name="interest" id="interest-hidden" 
           value="<?php echo isset($saved_data['interest']) ? htmlspecialchars(implode(',', $saved_data['interest'])) : ''; ?>">
    <div id="interest-ratings">
        <?php 
        if (isset($saved_data['interest'])) {
            foreach ($saved_data['interest'] as $interest) {
                $safeId = preg_replace('/[^a-zA-Z0-9]/g', '_', $interest);
                $rating = isset($saved_data['interest_ratings'][$interest]) ? $saved_data['interest_ratings'][$interest] : 5;
                echo '<div class="interest-rating-group" style="margin:10px 0;">
                    <label style="font-weight:500;">
                        '.htmlspecialchars($interest).' self-rating: 
                        <span id="label-'.$safeId.'" style="display:inline-block;margin-left:10px;">'.$rating.'</span>/10
                    </label>
                    <div class="slider-container" style="margin-top:5px;">
                        <input type="range" min="0" max="10" step="1" 
                               name="interest_ratings['.htmlspecialchars($interest).']"
                               id="slider-'.$safeId.'"
                               value="'.$rating.'">
                    </div>
                </div>';
            }
        }
        ?>
    </div>
</div>
                <div class="form-input">
                    <label for="grade-previous">Grade of each respective field in previous academic years (if applicable) <span>*</span></label>
                    <input type="text" name="grade-previous" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['grade-previous']) ? htmlspecialchars($saved_data['grade-previous']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="first-essay" style="font-weight: 500;">What extracurricular activities and achievements have you pursued in your area of academic interest?  <br>These may range from as simple as winning a school competition to more intensive experiences such as Olympiads or internships. <span>*</span></label>
                    <textarea name="first-essay" id="first-essay" required><?php echo isset($saved_data['first-essay']) ? htmlspecialchars($saved_data['first-essay']) : ''; ?></textarea>
                </div>
                </div>

                <div class="form-page">
                    <h2>Essay Questions</h2>
                <br>
                <div class="form-input">
                    <label for="second-essay" style="font-weight: 500;">What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry? <br>Your response should be at least 200 words in length. <span>*</span></label>
                    <textarea name="second-essay" id="second-essay" required><?php echo isset($saved_data['second-essay']) ? htmlspecialchars($saved_data['second-essay']) : ''; ?></textarea>
                    <div class="counter"><span id="second-essay-counter">0</span>/200 <span class="word-count-warning" id="second-essay-warning"></span></div>
                </div>
                <div class="form-input">
                    <label for="third-essay" style="font-weight: 500;">Why do you want to join YSJ? Please explain what aspects of YSJ's mission and values resonate with you. <br>Your response should be at least 150 words in length. <span>*</span></label>
                    <textarea name="third-essay" id="third-essay" required><?php echo isset($saved_data['third-essay']) ? htmlspecialchars($saved_data['third-essay']) : ''; ?></textarea>
                    <div class="counter"><span id="third-essay-counter">0</span>/150 <span class="word-count-warning" id="third-essay-warning"></span></div>
                </div>
                <div class="form-input">
                    <label for="fourth-essay" style="font-weight: 500;">Have you participated in any previous research projects or programs? Please describe your research experience, including the objectives, methodologies used, and any notable outcomes or contributions. <span>*</span></label>
                    <textarea name="fourth-essay" id="fourth-essay" required><?php echo isset($saved_data['fourth-essay']) ? htmlspecialchars($saved_data['fourth-essay']) : ''; ?></textarea>
                </div>
                </div>
              
                <div class="form-page">
                    <h2>Additional Information</h2>
                <br>
<div class="form-input">
                    <label for="hours_commitment">How much time (hours per week) will you be able to commit to YSJ <span>*</span></label>
                    <input type="number" name="hours_commitment" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['hours_commitment']) ? htmlspecialchars($saved_data['hours_commitment']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="time_blocks">List down the most significant time blocks you have between the start of August <span>*</span></label>
                    <input type="text" name="time_blocks" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['time_blocks']) ? htmlspecialchars($saved_data['time_blocks']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="hear_about">How did you hear about the YSJ Annual Junior Program? <span>*</span></label>
                    <input type="text" name="hear_about" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['hear_about']) ? htmlspecialchars($saved_data['hear_about']) : ''; ?>">
                </div>
                <br>
                <div class="form-input">
                    <label for="additional-essay" style="font-weight: 500;">Feel free to add whatever you feel may boost your application even further.</label>
                    <textarea name="additional-essay" id="additional-essay"><?php echo isset($saved_data['additional-essay']) ? htmlspecialchars($saved_data['additional-essay']) : ''; ?></textarea>
                </div>
                </div>
                
                

            <div class="controls" id="page-controls">
        <div style="display:flex;gap: 10px;margin-right: 10px;align-items: center;width: 100%;">
            <button type="button" class="btn prev-btn" style="display:none;width: 100%;">Previous</button>
        <button type="button" class="btn next-btn" style="width: 100%;">Next</button>
        </div>
        <div style="display:flex;gap: 10px;align-items: center;width: 100%;">
            <button type="button" class="btn submit-btn" style="display:none;width: 100%;">Submit</button>
        <button class="btn clear-btn" style="width: 100%;">Clear Form</button>
        <button type="button" class="btn save-btn" style="width: 100%;">Save Progress</button>
        </div>
    </div>

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
        <div id="customMessageBox" style="display:none;position:fixed;top:30px;left:50%;transform:translateX(-50%);padding:18px 28px;background:#ff4444;color:#fff;border-radius:7px;z-index:10000;box-shadow:0 2px 10px rgba(0,0,0,0.2);font-size:18px;min-width:300px;text-align:center;"></div>
        <script>
            const interestOptions = <?php echo json_encode($interest_options); ?>;
            const savedRatings = <?php
                if (isset($saved_data['interest_ratings'])) {
                    echo json_encode($saved_data['interest_ratings']);
                } else {
                    echo '{}';
                }
            ?>;
        </script>
    <script src="js/application.min.js"></script>
    
</body>
</html>