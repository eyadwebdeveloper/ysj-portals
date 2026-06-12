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

// Copy application to deleted_applications before hard delete
$stmt = $conn->prepare("SELECT * FROM applications WHERE id=?");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
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

// Hard delete: remove from applications table
$stmt = $conn->prepare("DELETE FROM applications WHERE id=?");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$stmt->close();

header("Location: applications");
exit();
?>