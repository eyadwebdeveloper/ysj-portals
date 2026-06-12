<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

try {
    // Check application status
    $status_row = $conn->query("SELECT application_status FROM app_action LIMIT 1")->fetch_assoc();
    if ($status_row && $status_row['application_status'] === 'closed') {
        throw new Exception('Application is currently closed. You cannot submit or save applications at this time.');
    }

    // Check user session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('You are not logged in. Please log in and try again.');
    }

    $user_id = $_SESSION['user_id'];

    // Check for existing application
    $stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ?");
    if (!$stmt) throw new Exception("Database error: " . $conn->error);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $existing_application = $stmt->get_result();
    $stmt->close();

    if ($existing_application->num_rows > 0) {
        throw new Exception("You have already submitted an application.");
    }

    // Validate session data
    if (!isset($_SESSION['saved_application']) || !isset($_SESSION['saved_files'])) {
        throw new Exception("No saved application data found. Please fill out the application form.");
    }

    $data = $_SESSION['saved_application'];
    $files = $_SESSION['saved_files'];

    // Prepare file paths
    $draft_dir = 'uploads/' . $user_id . '/drafts/';
    $final_dir = 'uploads/' . $user_id . '/final/';
    if (!file_exists($final_dir)) {
        if (!mkdir($final_dir, 0777, true)) {
            throw new Exception("Failed to create final upload directory.");
        }
    }

    // Move files from draft to final and update paths in $files
    foreach ($files as $field => $fileArr) {
        foreach ($fileArr as $i => $file) {
            $safe_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file['name']);
            $final_path = $final_dir . $safe_name;
            if (file_exists($file['path'])) {
                if (!rename($file['path'], $final_path)) {
                    throw new Exception("Failed to move file: " . $file['name']);
                }
                $files[$field][$i]['path'] = $final_path;
            }
        }
    }

    // Clean up draft directory
    if (file_exists($draft_dir)) {
        array_map('unlink', glob("$draft_dir/*"));
        @rmdir($draft_dir);
    }

    // Prepare interest and ratings
    $interest = isset($data['interest']) && is_array($data['interest']) ? implode(',', $data['interest']) : '';
    $interest_ratings = isset($data['interest_ratings']) && is_array($data['interest_ratings']) ? json_encode($data['interest_ratings']) : null;
    $hours_commitment = isset($data['hours_commitment']) && $data['hours_commitment'] !== '' ? (int)$data['hours_commitment'] : 0;

    // Insert application
    $stmt = $conn->prepare("INSERT INTO applications (
        user_id, full_name, email, contact_number, country, gender, 
        birth_date, institution, grade_year, gpa, interest, interest_ratings,
        grade_previous, first_essay, second_essay, third_essay, 
        hours_commitment, time_blocks, hear_about, status, submitted_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) throw new Exception("Database error: " . $conn->error);

    $status = 'pending';
    $stmt->bind_param("ssssssssssssssssisss", 
        $user_id,
        $data['full-name'],
        $data['email-address'],
        $data['contact-number'],
        $data['country'],
        $data['gender'],
        $data['birth-date'],
        $data['institution'],
        $data['grade-year'],
        $data['gpa'],
        $interest,
        $interest_ratings,
        $data['grade-previous'],
        $data['first-essay'],
        $data['second-essay'],
        $data['third-essay'],
        $hours_commitment,
        $data['time_blocks'],
        $data['hear_about'],
        $status
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to submit application. Please try again. " . $stmt->error);
    }
    $application_id = $stmt->insert_id;
    $stmt->close();

    // Insert files
    $field_type_map = [
        'research_files'   => 'research',
        'commented_files'  => 'commented',
        'additional_files' => 'additional'
    ];

    foreach ($files as $field => $fileArr) {
        $file_type = isset($field_type_map[$field]) ? $field_type_map[$field] : $field;
        foreach ($fileArr as $file) {
            $stmt = $conn->prepare("INSERT INTO application_files (application_id, file_type, file_path, original_name) VALUES (?, ?, ?, ?)");
            if (!$stmt) throw new Exception("Database error: " . $conn->error);
            $stmt->bind_param("isss", $application_id, $file_type, $file['path'], $file['name']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to save file info for: " . $file['name']);
            }
            $stmt->close();
        }
    }

    // Remove draft from db
    $stmt = $conn->prepare("DELETE FROM application_drafts WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Clean up session
    unset($_SESSION['saved_application']);
    unset($_SESSION['saved_files']);

    $_SESSION['success'] = "Application submitted successfully!";
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
?>