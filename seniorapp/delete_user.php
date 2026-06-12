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

    $stmt = $conn->prepare("DELETE FROM applications WHERE user_id = ?");
    $stmt->bind_param("s", $user_id); 
    $stmt->execute();
    $stmt->close();

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