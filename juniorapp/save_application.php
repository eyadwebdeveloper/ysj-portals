<?php
// Error reporting configuration
// At the very top of save_application.php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/apache2/juniorapp_errors.log');

session_start();
include 'db.php';


// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [];

// Enhanced logging function
function log_error($message, $context = []) {
    $log_entry = sprintf(
        "[%s] %s %s\n",
        date('Y-m-d H:i:s'),
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    error_log($log_entry);
}

// Debug: Log all incoming data
log_error('Incoming request data', [
    'post' => $_POST,
    'files' => $_FILES,
    'input' => file_get_contents('php://input'),
    'headers' => getallheaders()
]);

// Verify application status
try {
    $status_row = $conn->query("SELECT application_status FROM app_action LIMIT 1")->fetch_assoc();
    if ($status_row && $status_row['application_status'] === 'closed') {
        throw new Exception('Application is currently closed');
    }
} catch (Exception $e) {
    log_error('Application status check failed', ['error' => $e->getMessage()]);
    $response['message'] = 'Application is currently closed';
    echo json_encode($response);
    exit;
}

// Verify user session
if (!isset($_SESSION['user_id'])) {
    log_error('Unauthorized access attempt', ['ip' => $_SERVER['REMOTE_ADDR']]);
    $response['message'] = 'Session expired. Please login again.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// Process form data
try {
    // Handle form clearing
    if (isset($_POST['clear_form'])) {
        unset($_SESSION['saved_application']);
        $stmt = $conn->prepare("DELETE FROM application_drafts WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $stmt->close();
        }
        $response['success'] = true;
        $response['message'] = 'Form cleared successfully';
        echo json_encode($response);
        exit;
    }

    // Process and validate form data
    $form_data = [];
    $received_fields = [];
    
    foreach ($_POST as $key => $value) {
        // Skip system fields
        if (in_array($key, ['save_progress', 'final_submission', 'clear_form']) || 
            strpos($key, '_removed') !== false) {
            continue;
        }
        
        $received_fields[] = $key;
        
        // Special handling for different field types
        if ($key === 'interest') {
            $form_data[$key] = explode(',', $value);
        } elseif ($key === 'interest_ratings' && is_array($value)) {
            $form_data[$key] = $value;
        } else {
            $form_data[$key] = $value;
        }
    }
    
    log_error('Processed form data', [
        'user_id' => $user_id,
        'received_fields' => $received_fields,
        'data_sample' => array_slice($form_data, 0, 3) // Log first 3 fields for sample
    ]);

    // Merge with existing session data
    if (isset($_SESSION['saved_application'])) {
        $form_data = array_merge($_SESSION['saved_application'], $form_data);
    }
    
    $_SESSION['saved_application'] = $form_data;

    // Save to database
    $data_json = json_encode($form_data);
    $stmt = $conn->prepare("INSERT INTO application_drafts (user_id, data) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()");
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $user_id, $data_json);
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    $stmt->close();

    // Handle response
// In the response handling section:
if (isset($_POST['final_submission']) && $_POST['final_submission'] == '1') {
    $_SESSION['final_submission_data'] = $form_data;
    $response = [
        'success' => true,
        'message' => 'Ready for final submission',
        'is_final' => true
    ];
} else {
    $response = [
        'success' => true,
        'message' => 'Progress saved successfully'
    ];
}
    
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    log_error('Save operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => $user_id
    ]);
    
    $response['message'] = 'Failed to save your progress. Please try again.';
    echo json_encode($response);
    exit;
}
?>