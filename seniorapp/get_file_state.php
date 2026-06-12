<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

try {
    $response = [];
    
    // Check for session files first
    if (isset($_SESSION['saved_files'])) {
        $response = $_SESSION['saved_files'];
    } else {
        // Fallback to database if no session data
        $user_id = $_SESSION['user_id'];
        
        // Check for draft files
        $stmt = $conn->prepare("SELECT files FROM application_drafts WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($files_json);
        
        if ($stmt->fetch()) {
            $response = json_decode($files_json, true) ?: [];
        }
        
        $stmt->close();
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>