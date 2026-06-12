<?php
// error.php
header('Content-Type: application/json');
http_response_code(200); // Always return 200 for this endpoint
echo json_encode([
    'success' => false,
    'message' => 'An error occurred. Please try again later.'
]);
exit();