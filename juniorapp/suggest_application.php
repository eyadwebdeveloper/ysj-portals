<?php
session_start();
include 'db.php';

if ($_SESSION['role'] != 'reviewer') {
    header("Location: login");
    exit();
}

$app_id = $_GET['id'];
$suggestion = $_GET['suggestion'];
if (!in_array($suggestion, ['accept', 'reject'])) {
    die("Invalid suggestion value");
}
$conn->query("UPDATE applications SET reviewer_suggestion='$suggestion' WHERE id=$app_id");

header("Location: reviewer_dashboard");
?>