<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

$status_row = $conn->query("SELECT application_status FROM app_action LIMIT 1")->fetch_assoc();
if ($status_row && $status_row['application_status'] === 'closed') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Application is currently closed. You cannot submit or save applications at this time.'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];

// Check if this is an edit of an existing application
$is_edit = false;
$application_id = null;

$stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $is_edit = true;
    $application_id = $result->fetch_assoc()['id'];
}
$stmt->close();

try {
    // Handle form clearing
    if (isset($_POST['clear_form'])) {
        clearSavedApplication($user_id);
        $response['success'] = true;
        sendResponse($response);
    }

    // Set up upload directories
    $base_dir = 'uploads/' . $user_id . '/';
    $draft_dir = $base_dir . 'drafts/';
    if (!file_exists($draft_dir)) {
        mkdir($draft_dir, 0777, true);
    }

    // Initialize session storage if not exists
    if (!isset($_SESSION['saved_files'])) {
        $_SESSION['saved_files'] = [];
    }

    // Process file uploads (save by original name, overwrite if exists)
    processFileUploads($draft_dir);

    // Process file removals
    processFileRemovals($draft_dir);

    // Save form data
    saveFormData();

    // Handle final submission
    if (isset($_POST['final_submission']) && $_POST['final_submission'] == '1') {
        if ($is_edit) {
            $response['redirect'] = 'update_application';
        } else {
            $response['redirect'] = 'submit_application';
        }
        $response['success'] = true;
        sendResponse($response);
    }

    $response['success'] = true;
    sendResponse($response);

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    sendResponse($response);
}

function clearSavedApplication($user_id) {
    global $conn;
    unset($_SESSION['saved_application']);
    unset($_SESSION['saved_files']);

    // Remove draft from db
    $stmt = $conn->prepare("DELETE FROM application_drafts WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->close();

    $draft_dir = 'uploads/' . $user_id . '/drafts/';
    if (file_exists($draft_dir)) {
        array_map('unlink', glob("$draft_dir/*"));
        rmdir($draft_dir);
    }
}

// In the processFileUploads function, modify to handle edit mode
// In the processFileUploads function, add this at the beginning:
function processFileUploads($draft_dir) {
    // Initialize file fields
    $file_fields = [
        'research_files' => ['multiple' => true, 'types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png']],
        'commented_files' => ['multiple' => true, 'types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']],
        'additional_files' => ['multiple' => true, 'types' => null]
    ];

    // Initialize session files array if not exists
    if (!isset($_SESSION['saved_files'])) {
        $_SESSION['saved_files'] = [];
    }

    foreach ($file_fields as $field => $config) {
        // Initialize field in session if not exists
        if (!isset($_SESSION['saved_files'][$field])) {
            $_SESSION['saved_files'][$field] = [];
        }

        // Process new uploads
        if (isset($_FILES[$field])) {
            $files = $_FILES[$field];
            $is_multiple = $config['multiple'];
            $allowed_types = $config['types'];

            if ($is_multiple) {
                $file_count = count($files['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                    $file_info = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    // Process and add to session immediately
                    $processed_file = processSingleFile($field, $file_info, $draft_dir, $allowed_types, $is_multiple);
                    if ($processed_file) {
                        $_SESSION['saved_files'][$field][] = $processed_file;
                    }
                }
            } else {
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $processed_file = processSingleFile($field, $files, $draft_dir, $allowed_types, $is_multiple);
                    if ($processed_file) {
                        $_SESSION['saved_files'][$field][] = $processed_file;
                    }
                }
            }
        }
    }

    // Handle file removals
    processFileRemovals($draft_dir);

    // For edit mode, ensure we persist the files in session
    if (isset($_POST['edit_mode'])) {
        file_put_contents('debug_session.txt', print_r($_SESSION['saved_files'], true)); // Debug only
    }
}
function processSingleFile($field, $file, $draft_dir, $allowed_types, $is_multiple) {
    // Validate file type
    if ($allowed_types !== null && !in_array($file['type'], $allowed_types)) {
        throw new Exception("Invalid file type for {$field}. Allowed types: " . implode(', ', $allowed_types));
    }

    // Validate file size (10MB limit)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception("File '{$file['name']}' exceeds 10MB limit");
    }

    // Save file with unique name to prevent collisions
    $safe_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file['name']);
    $unique_name = $field . '_' . uniqid() . '_' . $safe_name;
    $file_path = $draft_dir . $unique_name;



    // Remove previous file with same name in session and disk
    if (isset($_SESSION['saved_files'][$field])) {
        foreach ($_SESSION['saved_files'][$field] as $index => $existing_file) {
            if ($existing_file['name'] === $file['name']) {
                @unlink($existing_file['path']);
                unset($_SESSION['saved_files'][$field][$index]);
                break;
            }
        }
        // Re-index the array
        $_SESSION['saved_files'][$field] = array_values($_SESSION['saved_files'][$field]);
    }

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception("Failed to move uploaded file '{$file['name']}'");
    }

    return [
        'name' => $file['name'],
        'path' => $file_path,
        'size' => $file['size'],
        'type' => $file['type']
    ];
}

// In the processFileRemovals function
// Update processFileRemovals function
function processFileRemovals($draft_dir) {
    $file_fields = ['research_files', 'commented_files', 'additional_files'];

    foreach ($file_fields as $field) {
        if (isset($_POST["{$field}_removed"])) {
            $removed_files = (array)$_POST["{$field}_removed"];
            if (!empty($removed_files)) {
                foreach ($removed_files as $removed_name) {
                    // Clean the filename
                    $cleaned_name = trim($removed_name);
                    
                    // Remove from session
                    if (isset($_SESSION['saved_files'][$field])) {
                        $_SESSION['saved_files'][$field] = array_filter(
                            $_SESSION['saved_files'][$field],
                            function($file) use ($cleaned_name) {
                                return $file['name'] !== $cleaned_name;
                            }
                        );
                        // Re-index the array
                        $_SESSION['saved_files'][$field] = array_values($_SESSION['saved_files'][$field]);
                    }
                    
                    // Remove from disk - handle both draft and final directories
                    $user_id = $_SESSION['user_id'];
                    $final_dir = 'uploads/' . $user_id . '/final/';
                    
                    // Check both original name and sanitized name patterns
                    $patterns = [
                        $cleaned_name,
                        preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $cleaned_name),
                        $field . '_*_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $cleaned_name)
                    ];
                    
                    foreach ($patterns as $pattern) {
                        // Check draft directory
                        foreach (glob($draft_dir . $pattern) as $file) {
                            if (file_exists($file)) {
                                @unlink($file);
                            }
                        }
                        
                        // Check final directory
                        foreach (glob($final_dir . $pattern) as $file) {
                            if (file_exists($file)) {
                                @unlink($file);
                            }
                        }
                    }
                }
            }
        }
    }
}

function saveFormData() {
    global $conn, $user_id, $is_edit, $application_id;

    $form_data = [];
    foreach ($_POST as $key => $value) {
        if (in_array($key, ['save_progress', 'final_submission', 'clear_form']) ||
            strpos($key, '_removed') !== false) {
            continue;
        }
        
        // Handle interest and interest_ratings specially
        if ($key === 'interest') {
            $form_data[$key] = explode(',', $value);
        } elseif ($key === 'interest_ratings' && is_array($value)) {
            $form_data[$key] = $value;
        } else {
            // Do NOT cast time_blocks to int, just save as string
            $form_data[$key] = $value;
        }
    }

    // If we have existing saved data, merge with it
    if (isset($_SESSION['saved_application'])) {
        $form_data = array_merge($_SESSION['saved_application'], $form_data);
    }

    $_SESSION['saved_application'] = $form_data;

    // If this is an edit, include the application ID in the saved data
    if ($is_edit) {
        $form_data['application_id'] = $application_id;
    }

    // Save to database as draft
    $data_json = json_encode($form_data);
    $files_json = isset($_SESSION['saved_files']) ? json_encode($_SESSION['saved_files']) : null;

    $stmt = $conn->prepare("INSERT INTO application_drafts (user_id, data, files) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), files = VALUES(files), updated_at = NOW()");
    $stmt->bind_param("sss", $user_id, $data_json, $files_json);
    $stmt->execute();
    $stmt->close();
}

function sendResponse($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>