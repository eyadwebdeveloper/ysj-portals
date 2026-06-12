<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login");
    exit();
}

$user_id = $_GET['id'] ?? '';
if (empty($user_id)) {
    die("Invalid user ID");
}

try {
    $conn->begin_transaction();

    // Fetch all applications for this user
    $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Insert each application into deleted_applications
    while ($row = $result->fetch_assoc()) {
        $columns = array_keys($row);
        $columns_list = implode(',', $columns);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $types = '';
        $values = [];
        foreach ($columns as $col) {
            $val = $row[$col];
            $types .= is_int($val) ? 'i' : 's';
            $values[] = $val;
        }
        $insert_sql = "INSERT INTO deleted_applications ($columns_list) VALUES ($placeholders)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param($types, ...$values);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $stmt->close();

    // Delete from applications
    $stmt = $conn->prepare("DELETE FROM applications WHERE user_id = ?");
    $stmt->bind_param("s", $user_id); 
    $stmt->execute();
    $stmt->close();

    // Delete from users
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header("Location: users");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error deleting user: " . $e->getMessage());
    die("Error deleting user. Please try again.");
}
?>