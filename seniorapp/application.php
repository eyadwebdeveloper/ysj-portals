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

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id); 
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];

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
$saved_files = [];

// Load application data if in edit mode (for editing submitted/final application)
if ($application && isset($_GET['edit_mode'])) {
    // Convert application data to match form field names
    $saved_data = [
        'full-name' => $application['full_name'],
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
        'hours_commitment' => $application['hours_commitment'],
        'time_blocks' => $application['time_blocks'],
        'hear_about' => $application['hear_about']
    ];
    
    // Load files
    $stmt = $conn->prepare("SELECT file_type, original_name, file_path FROM application_files WHERE application_id = ?");
    $stmt->bind_param("i", $application['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $saved_files = [
        'research_files' => [],
        'commented_files' => [],
        'additional_files' => []
    ];
    
while ($file = $result->fetch_assoc()) {
    $file_type = $file['file_type'] . '_files';
    if (isset($saved_files[$file_type]) && file_exists($file['file_path'])) {
        $saved_files[$file_type][] = [
            'name' => $file['original_name'],
            'path' => $file['file_path'],
            'size' => filesize($file['file_path']),
            'type' => mime_content_type($file['file_path'])
        ];
    }
}
    $stmt->close();
    $_SESSION['saved_application'] = $saved_data;
    $_SESSION['saved_files'] = $saved_files;
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

        <form class="form" id="applicationForm" action="save_application" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="final_submission" id="final_submission" value="0">
               <div class="form-page">
                <h1 style="text-align: center;font-size: 30px;line-height: 30px;margin-bottom: 20px;">Welcome to Ysj Senior Application for the 2025 Season !</h1>
                <p style="font-size: 18px;;">Welcome to the the Senior Application for the 2025 season of the junior program.  Our mission is to nurture young researchers, and we're thrilled to extend an invitation to all of you to join the new program board. <br><br> As a senior researcher, your role will include supervising two junior teams, conducting weekly sessions for each, assigning and reviewing tasks, and monitoring their progress in research paper writing. <br><br>Notice that previous participation in YSJ is not required. Whether you're a returning YSJ member or a newcomer, we encourage you to apply! The shortlised applicants will be invited to an interview, which is the final phase of the application. <br><br>For any inquiries or concerns, please reach out via email.</p>
               </div>
                <div class="form-page">
                    <h2>Personal Information</h2>
                <br>
                <div class="form-input">
                    <label for="full-name">Full Name (First and Second) <span>*</span></label>
                    <p class="label-description">This name will appear in all communications by the journal, including emails, posts, and websites.</p>
                    <input type="text" name="full-name" placeholder="Your Answer" required  
                           value="<?php echo isset($saved_data['full-name']) ? htmlspecialchars($saved_data['full-name']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="email-address">Personal Email Address <span>*</span></label>
                    <p class="label-description">Please ensure the accuracy of your email address as all subsequent communications regarding the application process will be sent to this email.</p>
                    <input type="email" name="email-address" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['email-address']) ? htmlspecialchars($saved_data['email-address']) : ''; ?>">
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
    <!-- In the interest ratings section, fix the syntax: -->
<div id="interest-ratings">
    <?php 
    if (isset($saved_data['interest'])) {  // <-- Remove the extra closing parenthesis
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
                </div>

                <div class="form-page">
                    <h2>Essay Questions</h2>
                <br>
                <div class="form-input">
                    <label for="first-essay" style="font-weight: 500;">Mentorship in scientific research requires a comprehensive understanding of the concepts and the ability to motivate and direct students as they go through their difficulties. Analyze this quote and discuss how your experiences and knowledge in scientific research have prepared you enough to fit yourself into YSJ's high standards. <br><br>In your response, state your academic and mentoring goals, explaining how some aspects of YSJ would help you direct young researchers toward their potential. <br><br>Furthermore, give examples of situations where you have helped learners wade through complex problems to aid their growth and excitement for science. <span>*</span></label>
                    <textarea name="first-essay" id="first-essay" required><?php echo isset($saved_data['first-essay']) ? htmlspecialchars($saved_data['first-essay']) : ''; ?></textarea>
                    <div class="counter"><span id="first-essay-counter">0</span>/200-400 <span class="word-count-warning" id="first-essay-warning"></span></div>
                </div>
                <div class="form-input">
                    <label for="second-essay" style="font-weight: 500;">Consider you're a YSJ senior researcher. One of your teams, perhaps team 123, has failed to stick to the Research idea proposal deadline. Write a message you'd send them via discord to push them to finish their task <span>*</span></label>
                    <textarea name="second-essay" id="second-essay" required><?php echo isset($saved_data['second-essay']) ? htmlspecialchars($saved_data['second-essay']) : ''; ?></textarea>
                </div>
                </div>
                <div class="form-page">
                    <h2>Research Experience</h2>
                <br>
                <div class="form-input">
                    <label for="third-essay" style="font-weight: 500;">Please describe your research background, including your areas of expertise, the research projects (e.g., papers, projects, and/or patents) you have been involved in, and/or any publications or presentations resulting from your research. (Word Limit: 150-300 words)  <span>*</span></label>
                    <textarea name="third-essay" id="third-essay" required><?php echo isset($saved_data['third-essay']) ? htmlspecialchars($saved_data['third-essay']) : ''; ?></textarea>
                    <div class="counter"><span id="third-essay-counter">0</span>/150-300 <span class="word-count-warning" id="third-essay-warning"></span></div>
                </div>
                <br>
                <label for="research-files" style="font-weight: 500;">Please provide us with any published papers, projects, or relevant materials. This will help us gain insight into your academic and professional contributions.</label>        
                    <div class="container">
    <label for="research-files-upload" class="dropzone">
        <div class="dropzone-content">
            <svg class="icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
            </svg>
            <p class="text"><span class="bold">Click to upload</span> or drag and drop</p>
            <p class="subtext">PDF, DOC, DOCX, JPG, PNG (MAX. 10MB Each, Max 10 files)</p>
        </div>
        <input id="research-files-upload" type="file" name="research_files[]" class="file-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"/>
    </label>
</div>
<div class="files" id="research-preview">
    <?php if (isset($saved_files['research_files'])): ?>
        <?php foreach ($saved_files['research_files'] as $index => $file): ?>
            <div class="uploaded-file">
                <p><span><?php echo formatFileSize($file['size']); ?></span> <?php echo htmlspecialchars($file['name']); ?></p>
                <button type="button" class="remove" onclick="removeSavedFile('research_files', <?php echo $index; ?>)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
                    <br>
                    <label for="commented-files" style="font-weight: 500;">Based on your areas of expertise and primary research experience (either literature reviews or analytical papers), please choose one of the following two papers and proceed with the instructions. <br><br><a href="https://docs.google.com/document/d/1OKcnxHR2Sj0KbBKXneJwqUiNdqXqbY-U/edit?usp=sharing&ouid=102876351236856328033&rtpof=true&sd=true">Comparing piston, turboprop, turbojet, and turbofan engines in terms of mechanism, fuel consumption, optimal cruising conditions, and opportunities for future development</a><br><br><a href="https://docs.google.com/document/d/1PVyNtN6p6iafdXu8btU7550FP6So6OfK/edit?usp=sharing&ouid=102876351236856328033&rtpof=true&sd=true">A Comparative Study of the Perceived Stress Levels and Sources of Stress</a><br><br>For the selected paper, assume it is a draft submitted by one of your assigned junior researchers. Your task is to comprehensively review the draft, identify all errors, and highlight them clearly. These errors may include, but are not limited to: grammatical and punctuation mistakes, structural issues, content gaps, and citation inconsistencies. <br><br>For each identified error, you are required to pinpoint it, highlight it within the text, and provide specific comments detailing the mistake. Upon completion of the review, please upload the commented file.</label>        
<div class="container">
    <label for="commented-files-upload" class="dropzone">
        <div class="dropzone-content">
            <svg class="icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
            </svg>
            <p class="text"><span class="bold">Click to upload</span> or drag and drop</p>
            <p class="subtext">PDF, DOC, DOCX (MAX. 10MB Each, Max 5 files)</p>
        </div>
        <input id="commented-files-upload" type="file" name="commented_files[]" class="file-input" multiple accept=".pdf,.doc,.docx"/>
    </label>
</div>
<div class="files" id="commented-preview">
    <?php if (isset($saved_files['commented_files'])): ?>
        <?php foreach ($saved_files['commented_files'] as $index => $file): ?>
            <div class="uploaded-file">
                <p><span><?php echo formatFileSize($file['size']); ?></span> <?php echo htmlspecialchars($file['name']); ?></p>
                <button type="button" class="remove" onclick="removeSavedFile('commented_files', <?php echo $index; ?>)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
                    <label for="time_blocks">List down the most significant time blocks you have between the start of June and the end of September. <span>*</span></label>
                    <input type="text" name="time_blocks" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['time_blocks']) ? htmlspecialchars($saved_data['time_blocks']) : ''; ?>">
                </div>
                <div class="form-input">
                    <label for="hear_about">Where did you hear about YSJ? <span>*</span></label>
                    <input type="text" name="hear_about" placeholder="Your Answer" required
                           value="<?php echo isset($saved_data['hear_about']) ? htmlspecialchars($saved_data['hear_about']) : ''; ?>">
                </div>
                <br>
                <div class="form-input">
                    <label for="additional-files" style="font-weight: 500;">Feel free to add whatever you feel may boost your application even further. <br><br>If you have previously participated in YSJ, put a link to your paper and specify which parts you wrote.<span>*</span></label>
                    <div class="container">
    <label for="additional-files-upload" class="dropzone">
        <div class="dropzone-content">
            <svg class="icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
            </svg>
            <p class="text"><span class="bold">Click to upload</span> or drag and drop</p>
            <p class="subtext">All file types (MAX. 10MB Each, Max 5 files)</p>
        </div>
        <input id="additional-files-upload" type="file" name="additional_files[]" class="file-input" multiple/>
    </label>
</div>
<div class="files" id="additional-preview">
    <?php if (isset($saved_files['additional_files'])): ?>
        <?php foreach ($saved_files['additional_files'] as $index => $file): ?>
            <div class="uploaded-file">
                <p><span><?php echo formatFileSize($file['size']); ?></span> <?php echo htmlspecialchars($file['name']); ?></p>
                <button type="button" class="remove" onclick="removeSavedFile('additional_files', <?php echo $index; ?>)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
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
