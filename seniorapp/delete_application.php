<?php
session_start();
include 'db.php';

if ($_SESSION['role'] != 'admin') {
    if ($_SESSION['role'] == 'reviewer') {
        header("Location: reviewer_dashboard");
        exit();
    }
    if ($_SESSION['role'] == 'applicant') {
        header("Location: application");
        exit();
    }
}

$app_id = (int)$_GET['id'];

// Soft delete: set is_deleted=1
$stmt = $conn->prepare("UPDATE applications SET is_deleted=1 WHERE id=?");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$stmt->close();

header("Location: applications");
exit();
?>