<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login");
    exit();
}

// Handle recovery
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recover_selected'])) {
    if (isset($_POST['selected_apps']) && is_array($_POST['selected_apps'])) {
        $recover_stmt = $conn->prepare("UPDATE applications SET is_deleted=0 WHERE id=?");
        foreach ($_POST['selected_apps'] as $app_id) {
            $app_id = (int)$app_id;
            $recover_stmt->bind_param("i", $app_id);
            $recover_stmt->execute();
        }
        $recover_stmt->close();
    }
    header("Location: recover_applications.php");
    exit();
}

// Fetch deleted applications
$stmt = $conn->prepare("SELECT applications.*, users.username 
                       FROM applications 
                       JOIN users ON applications.user_id = users.id
                       WHERE applications.is_deleted=1
                       ORDER BY applications.id");
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recover Applications</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/decisions.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <div class="dashboard-container">
        <h2>Recover Deleted Applications</h2>
        <a href="admin_dashboard">Back to Dashboard</a>
        <form action="" method="POST">
            <table id="applications-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Select</th>
                        <th>App ID</th>
                        <th>User ID</th>
                        <th>Applicant</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $idx => $app): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <input type="checkbox" name="selected_apps[]" value="<?= $app['id'] ?>">
                        </td>
                        <td><?= htmlspecialchars($app['id']) ?></td>
                        <td><?= htmlspecialchars($app['user_id']) ?></td>
                        <td><?= htmlspecialchars($app['username']) ?></td>
                        <td><?= htmlspecialchars($app['status']) ?></td>
                        <td>
                            <a href="view_application?id=<?= $app['id'] ?>" class="action-btn"><i class="fa-duotone fa-solid fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="decision-actions row">
                <button type="submit" name="recover_selected" class="btn" value="recover">Recover Selected</button>
            </div>
        </form>
    </div>
</body>
</html>