<?php
// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/apache2/juniorapp_errors.log');

// Start session and include database connection
session_start();
include 'db.php';

// Initialize messages
$_SESSION['application_message'] = '';
$_SESSION['application_success'] = false;

try {
    // Verify we have submission data
    if (!isset($_SESSION['saved_application'])) {
        throw new Exception("No application data found. Please complete the form first.");
    }

    $data = $_SESSION['saved_application'];
    $user_id = $_SESSION['user_id'] ?? null;
    
    error_log("Processing submission for user: " . ($user_id ?? 'unknown'));

    // Verify application is open
    $status_row = $conn->query("SELECT application_status FROM app_action LIMIT 1")->fetch_assoc();
    if ($status_row && $status_row['application_status'] === 'closed') {
        throw new Exception('Application period is currently closed.');
    }

    // Verify user session
    if (!$user_id) {
        throw new Exception('Your session has expired. Please login again.');
    }

    // Check for existing application
    $stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Database error. Please try again.");
    }
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        throw new Exception("You have already submitted an application.");
    }
    $stmt->close();

    // Prepare data for database - ensure all values are variables, not expressions
    $interest = isset($data['interest']) && is_array($data['interest']) ? 
        implode(',', $data['interest']) : '';
    $interest_ratings = isset($data['interest_ratings']) && is_array($data['interest_ratings']) ? 
        json_encode($data['interest_ratings']) : null;
    $hours_commitment = isset($data['hours_commitment']) && $data['hours_commitment'] !== '' ? 
        (int)$data['hours_commitment'] : 0;

    // Create variables for all bound parameters
    $full_name = $data['full_name'] ?? '';
    $email = $data['email-address'] ?? '';
    $contact_number = $data['contact-number'] ?? '';
    $country = $data['country'] ?? '';
    $gender = $data['gender'] ?? '';
    $birth_date = $data['birth-date'] ?? '';
    $institution = $data['institution'] ?? '';
    $grade_year = $data['grade-year'] ?? '';
    $gpa = $data['gpa'] ?? '';
    $grade_previous = $data['grade-previous'] ?? '';
    $first_essay = $data['first-essay'] ?? '';
    $second_essay = $data['second-essay'] ?? '';
    $third_essay = $data['third-essay'] ?? '';
    $fourth_essay = $data['fourth-essay'] ?? '';
    $additional_essay = $data['additional-essay'] ?? '';
    $time_blocks = $data['time_blocks'] ?? '';
    $hear_about = $data['hear_about'] ?? '';

    // Begin database transaction
    $conn->begin_transaction();

    // Insert application
    $stmt = $conn->prepare("INSERT INTO applications (
        user_id, full_name, email, contact_number, country, gender, 
        birth_date, institution, grade_year, gpa, interest, interest_ratings,
        grade_previous, first_essay, second_essay, third_essay, fourth_essay, additional_essay,
        hours_commitment, time_blocks, hear_about, status, submitted_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    
    if (!$stmt) {
        throw new Exception("Database preparation failed: ".$conn->error);
    }

    // Bind parameters - all variables now
    $bind_result = $stmt->bind_param("ssssssssssssssssssiss", 
        $user_id,
        $full_name,
        $email,
        $contact_number,
        $country,
        $gender,
        $birth_date,
        $institution,
        $grade_year,
        $gpa,
        $interest,
        $interest_ratings,
        $grade_previous,
        $first_essay,
        $second_essay,
        $third_essay,
        $fourth_essay,
        $additional_essay,
        $hours_commitment,
        $time_blocks,
        $hear_about
    );

    if (!$bind_result) {
        throw new Exception("Parameter binding failed: ".$stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execution failed: ".$stmt->error);
    }

    $application_id = $conn->insert_id;
    $stmt->close();
    error_log("Application submitted successfully. ID: $application_id");

    // Clean up drafts
    $stmt = $conn->prepare("DELETE FROM application_drafts WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Clean up session
    unset($_SESSION['saved_application']);
    if (isset($_SESSION['saved_files'])) {
        unset($_SESSION['saved_files']);
    }

    // Set success message and flag
    $_SESSION['application_message'] = 'Application submitted successfully!';
    $_SESSION['application_success'] = true;
    $_SESSION['application_id'] = $application_id;

    // Redirect to status page
    header("Location: status.php");
    exit();

} catch (Exception $e) {
    // Roll back transaction on error
    if (isset($conn) && method_exists($conn, 'rollback')) {
        $conn->rollback();
    }
    
    error_log("[".date('Y-m-d H:i:s')."] Submission error: ".$e->getMessage());
    error_log("Stack trace: ".$e->getTraceAsString());
    
    // Set error message
    $_SESSION['application_message'] = $e->getMessage();
    $_SESSION['application_success'] = false;

    // Redirect back to application page
    header("Location: application.php");
    exit();
}