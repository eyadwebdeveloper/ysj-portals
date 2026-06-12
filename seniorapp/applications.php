<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'reviewer') {
    header("Location: application");
    exit();
}

function truncateString($string, $length = 20) {
    if (strlen($string) > $length) {
        return substr($string, 0, $length) . '...';
    }
    return $string;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'under_review', 'reviewed', 'approved', 'rejected'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

$interest_list = [];
$interest_query = $conn->query("SELECT DISTINCT interest FROM applications WHERE interest IS NOT NULL AND interest != ''");
while ($row = $interest_query->fetch_assoc()) {
    $interests = array_map('trim', explode(',', $row['interest']));
    foreach ($interests as $interest) {
        if ($interest !== '') $interest_list[$interest] = $interest;
    }
}
ksort($interest_list);

$interest_filter = isset($_GET['interest']) ? trim($_GET['interest']) : '';
$rating_filter = isset($_GET['interest_rating']) ? (int)$_GET['interest_rating'] : '';

$query = "SELECT applications.*, users.username 
          FROM applications 
          JOIN users ON applications.user_id = users.id";
$where = ["applications.is_deleted=0"];
$params = [];
$types = "";

if ($status_filter != 'all') {
    $where[] = "applications.status=?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($interest_filter !== '') {
    $where[] = "FIND_IN_SET(?, applications.interest)";
    $params[] = $interest_filter;
    $types .= "s";
}
if ($rating_filter !== '') {
    $where[] = "JSON_EXTRACT(applications.interest_ratings, CONCAT('$.', ?)) >= ?";
    $params[] = $interest_filter;
    $params[] = $rating_filter;
    $types .= "si";
}
if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Save params/types for count query BEFORE adding LIMIT/OFFSET
$count_params = $params;
$count_types = $types;

$query .= " ORDER BY applications.submitted_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$applications = $stmt->get_result();
$stmt->close();

$count_query = "SELECT COUNT(*) as count FROM applications";
if ($where) {
    $count_query .= " WHERE " . implode(" AND ", $where);
}
$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['count'];
$count_stmt->close();
$total_pages = max(1, ceil($total / $per_page));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

    <style>
        select {
            padding: 8px;
        }

    .filter-container form {
        padding: 20px;
        background: #d4d4d4;
        border-radius: 10px;
        gap: 10px;
    }
    .form-input {
        display: flex;
        flex-direction: column;
    }
    .row a {
        background: #a31313;
  color: #fff;
  border: none;
  outline: none;
  padding: 10px;
  font-size: 18px;
  width: 100%;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
    }

    </style>

</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Manage Applications</h2>
            <a href="admin_dashboard">Back to Dashboard</a>
        </div>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements">Manage Announcements</a>
            <a href="assign_reviewers">Assign Applications</a>
        </div>

<div class="filter-container">
    <form method="GET" class="form" style="width: 100%; display: flex; gap: 10px; align-items: center;">
        <div class="row">
            <div class="form-input">
            <label for="status">Filter by Status:</label>
            <select name="status" id="status" onchange="this.form.submit()">
                <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="under_review" <?= $status_filter == 'under_review' ? 'selected' : '' ?>>Under review</option>
                <option value="reviewed" <?= $status_filter == 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Accepted</option>
                <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="form-input">
            <label for="per_page">Items per page:</label>
            <select name="per_page" id="per_page" onchange="this.form.submit()">
                <option value="5" <?= $per_page == 5 ? 'selected' : '' ?>>5</option>
                <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20</option>
                <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
            </select>
        </div>
        </div>
        <div class="row">
            <div class="form-input">
    <label for="interest">Filter by Interest:</label>
    <select name="interest" id="interest" onchange="this.form.submit()">
        <option value="">All Interests</option>
        <?php foreach ($interest_list as $interest): ?>
            <option value="<?= htmlspecialchars($interest) ?>" <?= $interest_filter == $interest ? 'selected' : '' ?>>
                <?= htmlspecialchars($interest) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-input">
    <label for="interest_rating">Interest Rating ≥</label>
    <select name="interest_rating" id="interest_rating" onchange="this.form.submit()">
        <option value="">Any</option>
        <?php for ($i = 0; $i <= 10; $i++): ?>
            <option value="<?= $i ?>" <?= $rating_filter !== '' && $rating_filter == $i ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
    </select>
</div>
        </div>
        <div class="row">
            <a href="applications">Clear all filters</a>
        </div>
    </form>
</div>

        <table>
            <thead>
                <tr>
                    <th>App ID</th>
                    <th>Applicant</th>
                    <th>Status</th>
                    <th>Reviewer Suggestion</th>
                    <th>Submitted At</th>
                    <th>Assigned To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($app = $applications->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($app['id']) ?></td>
                    <td title="<?= htmlspecialchars($app['username']) ?>" style="max-width: 150px;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">
    <?= htmlspecialchars(truncateString($app['username'], 20)) ?>
</td>
                    <td class="status-<?= htmlspecialchars($app['status']) ?>">
    <?= htmlspecialchars(ucfirst($app['status'])) ?>
    <?php if ($app['status'] == 'reviewed'): ?>
        <br><small>(<?= $app['total_score'] ?>/100 - <?= $app['overall_rating'] ?>)</small>
    <?php endif; ?>
</td>
                    <td class="suggestion-<?= !empty($app['reviewer_suggestion']) ? htmlspecialchars($app['reviewer_suggestion']) : 'none' ?>">
                        <?= !empty($app['reviewer_suggestion']) ? 
                            htmlspecialchars(ucfirst($app['reviewer_suggestion'])) : 'No suggestion' ?>
                    </td>
                    <td title="<?= date('M d, Y H:i', strtotime($app['submitted_at'])) ?>"><?= date('M d, Y H:i', strtotime($app['submitted_at'])) ?></td>
                    <td>
                        <?php
                        if ($app['assigned_to']) {
                            $reviewer_stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
                            $reviewer_stmt->bind_param("s", $app['assigned_to']);
                            $reviewer_stmt->execute();
                            $reviewer = $reviewer_stmt->get_result()->fetch_assoc();
                            echo htmlspecialchars($reviewer['username']);
                        } else {
                            echo "Not Assigned";
                        }
                        ?>
                    </td>
                    <td>
        <a href="view_application?id=<?= $app['id'] ?>"  class="action-btn"><i class="fa-duotone fa-solid fa-eye"></i></a>
        <a href="assign_reviewer?id=<?= $app['id'] ?>" class="action-btn"><i class="fa-duotone fa-regular fa-users"></i></a>
        <a href="delete_application?id=<?= $app['id'] ?>"  
           onclick="return confirmDeleteApplication(event);" class="action-btn"><i class="fa-duotone fa-solid fa-trash"></i></a>
    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

<div class="pagination">
    <div class="pagination-info">
        Showing page <?= $page ?> of <?= $total_pages ?> (Total: <?= $total ?> applications)
    </div>
    <div class="pagination-links">
        <?php if ($page > 1): ?>
            <a href="?page=1&status=<?= $status_filter ?>&per_page=<?= $per_page ?>">First</a>
            <a href="?page=<?= $page-1 ?>&status=<?= $status_filter ?>&per_page=<?= $per_page ?>">Previous</a>
        <?php endif; ?>

        <?php 

        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);

        if ($start > 1) echo '<span>...</span>';
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&per_page=<?= $per_page ?>" 
               <?= $i == $page ? 'class="active"' : '' ?>>
                <?= $i ?>
            </a>
        <?php endfor;
        if ($end < $total_pages) echo '<span>...</span>';
        ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>&status=<?= $status_filter ?>&per_page=<?= $per_page ?>">Next</a>
            <a href="?page=<?= $total_pages ?>&status=<?= $status_filter ?>&per_page=<?= $per_page ?>">Last</a>
        <?php endif; ?>
    </div>
</div>
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
<script src="js/applications.min.js"></script>
</body>
</html>