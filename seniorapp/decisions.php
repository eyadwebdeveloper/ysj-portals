<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'reviewer')) {
    header("Location: login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    if (isset($_POST['selected_apps']) && is_array($_POST['selected_apps']) && isset($_POST['action'])) {
        $selected_apps = $_POST['selected_apps'];
        $action = $_POST['action']; 

        $update_stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
        $update_stmt->bind_param("si", $action, $app_id);

        $log_stmt = $conn->prepare("INSERT INTO application_status_log 
                                  (application_id, old_status, new_status, changed_by, notes) 
                                  VALUES (?, ?, ?, ?, ?)");

        foreach ($selected_apps as $app_id) {
            $app_id = (int)$app_id;

            $status_stmt = $conn->prepare("SELECT status FROM applications WHERE id = ?");
            $status_stmt->bind_param("i", $app_id);
            if ($status_stmt->execute()) {
                $status_result = $status_stmt->get_result();
                $current_status = $status_result->fetch_assoc()['status'] ?? 'unknown';
                $status_stmt->close();

                $update_stmt->execute();

                $log_notes = "Status changed to $action";
                $log_stmt->bind_param("issss", $app_id, $current_status, $action, $_SESSION['user_id'], $log_notes);
                $log_stmt->execute();
            }
            $user_query = $conn->prepare("SELECT u.email_address, u.username 
                                 FROM users u 
                                 JOIN applications a ON u.id = a.user_id 
                                 WHERE a.id = ?");
    $user_query->bind_param("i", $app_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_query->close();
        }
        $update_stmt->close();
        $log_stmt->close();
    } elseif (isset($_POST['action_all'])) {
$action = $_POST['action_all']; 
    $status = ($action == 'approve_all') ? 'approved' : 'rejected';

    $stmt = $conn->prepare("UPDATE applications SET status=?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $stmt->close();

}

    header("Location: decisions");
    exit();
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$stmt = $conn->prepare("SELECT applications.*, users.username 
                       FROM applications 
                       JOIN users ON applications.user_id = users.id
                       WHERE applications.is_deleted=0
                       ORDER BY applications.id");
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();
$from = isset($_GET['from']) ? max(1, (int)$_GET['from']) : 1;
$to = isset($_GET['to']) ? (int)$_GET['to'] : count($applications);

$filtered_apps = [];
foreach ($applications as $idx => $app) {
    $row_number = $idx + 1;
    if ($row_number >= $from && $row_number <= $to) {
        $filtered_apps[] = $app;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decisions Page</title>
    <link rel="stylesheet" href="css/admin.css">
        <link rel="stylesheet" href="css/decisions.css">

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

    <style>
        input {
            padding: 5px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .status-badge.pending {
            background-color: #ffcc00;
            color: #000;
        }
        .status-badge.under_review {
            background-color: #0099ff;
            color: #fff;
        }
        .status-badge.reviewed {
            background-color: #9900ff;
            color: #fff;
        }
        .status-badge.approved {
            background-color: #00aa00;
            color: #fff;
        }
        .status-badge.rejected {
            background-color: #ff0000;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
    <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
        <h2>Decisions</h2>
          <a href="admin_dashboard">Back to Dashboard</a>
        </div>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements" class="active">Manage Announcements</a>
            <a href="emails" class="active">Seniors Emails</a>
        </div>
       <form action="" class="filter-form">
          <div class="form-container">
           <div class="inputs">
                <div>
                    <label for="from">From</label>
                   <input type="number" name="from" min="1" required value="<?= htmlspecialchars($from) ?>">
                </div>
                <div>
                    <label for="to">To</label>
                   <input type="number" name="to" required value="<?= htmlspecialchars($to) ?>">
                </div>
           </div>
          <div class="buttons">
            <button type="submit">Filter</button>
            <a href="decisions">Clear</a>
          </div>
          
          </div>
       </form>
        <form action="decisions" class="form" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                <tbody id="sortable">
                    <?php foreach ($filtered_apps as $idx => $app): ?>
<tr data-id="<?php echo $app['id']; ?>">
    <td class="row-number"><?php echo $from + $idx; ?></td>
    <td><label class="ios-checkbox red"><input type="checkbox" name="selected_apps[]" value="<?php echo $app['id']; ?>"/><div class="checkbox-wrapper"><div class="checkbox-bg"></div><svg class="checkbox-icon" viewBox="0 0 24 24" fill="none"><path class="check-path" d="M4 12L10 18L20 6" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path></svg></div></label></td>
    <td><?php echo $app['id']; ?></td>
    <td><?php echo $app['user_id']; ?></td>
    <td><?php echo $app['username']; ?></td>
    <td><?php echo $app['status']; ?></td>
    <td>
        <a href="view_application?id=<?php echo $app['id']; ?>" class="action-btn"><i class="fa-duotone fa-solid fa-eye"></i></a>
    </td>
</tr>
<?php endforeach; ?>
                </tbody>

            </table>

            <div class="decision-actions row">
                <button type="submit" name="action" class="btn" value="approved">Approve Selected</button>
                <button type="submit" name="action" class="btn" value="rejected">Reject Selected</button>
                <button type="submit" name="action_all" class="btn" value="approve_all">Approve All</button>
                <button type="submit" name="action_all" class="btn" value="reject_all">Reject All</button>
            </div>
        </form>
    </div>
<footer>
        <h2>If you encounter any problem please contact us</h2>
        <div class="social-icons">
            <a href="https://www.facebook.com/YouthScienceJournall" class="social-icon">
                <i class="fa-brands fa-facebook-f"></i>
        </a>
            <a href="https://www.instagram.com/ysciencejournal?igsh=MWR3M3ZwYWxod3My" class="social-icon">
                <i class="fa-brands fa-instagram"></i>
        </a>
            <a href="https://www.linkedin.com/company/ysj/" class="social-icon">
                <i class="fa-brands fa-linkedin-in"></i>
        </a>
            <a href="mailto:ysciencejournal@gmail.com" class="social-icon">
                <i class="fa-duotone fa-solid fa-envelope-open-text"></i>
        </a>
        </div>
        <hr>
        <p>&copy; Youth Science Journal. All rights reserved</p>
    </footer>
    <div id="customConfirmBox" style="display:none;position:fixed;top:30px;left:50%;transform:translateX(-50%);padding:18px 28px;background:#ff9800;color:#fff;border-radius:7px;z-index:10001;box-shadow:0 2px 10px rgba(0,0,0,0.2);font-size:18px;min-width:320px;text-align:center;"></div>
    <script src="js/decisions.min.js"></script>
</body>
</html>