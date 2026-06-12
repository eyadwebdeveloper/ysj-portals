<?php
session_start();
include 'db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: login");
    exit();
}

if (isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("ss", $new_role, $user_id); 
    $stmt->execute();
    $stmt->close();
}
function truncateString($string, $length = 20) {
    if (strlen($string) > $length) {
        return substr($string, 0, $length) . '...';
    }
    return $string;
}


$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT * FROM users WHERE role != 'admin'";
$params = [];
$types = "";

if ($filter == 'applicants') {
    $query .= " AND role = 'applicant'";
} elseif ($filter == 'reviewers') {
    $query .= " AND role = 'reviewer'";
}

if (!empty($search)) {
    if ($search[0] == "S" && $search[1] == "Y" && $search[2] == "S" && $search[3] == "J" && $search[4] == "0") {
        $query .= " AND id = ?";
        $params[] = $search;
        $types .= "s"; 
    } else {
        $query .= " AND username LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }
}

$query .= " ORDER BY role, username";

$per_page = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_query = preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) as count FROM', $query);
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $per_page);
$count_stmt->close();

$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

if (isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email_address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'reviewer';

    // Validate fields
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $add_user_error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $add_user_error = "Passwords do not match!";
    } else {
        // Check if username exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $add_user_error = "Username already exists!";
        }
        $check_stmt->close();

        // Check if email exists
        if (empty($add_user_error)) {
            $check_stmt_email = $conn->prepare("SELECT id FROM users WHERE email_address = ?");
            $check_stmt_email->bind_param("s", $email);
            $check_stmt_email->execute();
            $check_stmt_email->store_result();
            if ($check_stmt_email->num_rows > 0) {
                $add_user_error = "Email already registered!";
            }
            $check_stmt_email->close();
        }

        // Proceed with registration if all checks pass
        if (empty($add_user_error)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Generate user ID (same as register)
            $seq_stmt = $conn->query("INSERT INTO user_id_sequence VALUES ()");
            $user_id_num = $conn->insert_id;
            $user_id = 'SYSJ0' . str_pad($user_id_num, 2, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO users (id, username, email_address, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $user_id, $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
            header("Location: users?filter=" . urlencode($filter) . "&search=" . urlencode($search) . "&per_page=" . $per_page);
                $add_user_success = "User added successfully!";
                
            } else {
                $add_user_error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
        <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

    <style>
        select {
            padding: 5px;
            margin-bottom: -1px;
        }
        .filter-search {
            display: flex;
            justify-content: space-between;
            padding: 0 50px;
            margin-top: 20px;
            align-items: center;
        }
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-group input {
            padding: 10px;
            margin: 0;
            width: 300px;
        }

        .filter-group a, .search-group button, .search-group a {
            padding: 8px 15px;
            background: #a31313;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin: 0;
            font-size: 17px;
        }
        .filter-group a.active {
            background: #700000;
        }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
          <h2>Manage Users</h2>
          <a id="showAddUserModal" style="cursor: pointer;">Add user</a>
        </div>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements" class="active">Manage Announcements</a>
            <a href="emails" class="active">Seniors Emails</a>
        </div>
<?php if (!empty($add_user_error)): ?>
    <div class="error" style="background: rgba(163, 19, 19, 0.21);color: #a31313;padding: 10px; font-size: 18px;border: 2px solid #a31313;margin: 10px;border-radius: 10px"><?= htmlspecialchars($add_user_error) ?></div>
<?php elseif (!empty($add_user_success)): ?>
    <div class="success" style="background:rgba(56, 142, 60, 0.25);color: #388e3c;padding: 10px; font-size: 18px;border: 1px solid #388e3c;margin: 10px;border-radius: 10px"><?= htmlspecialchars($add_user_success) ?></div>
<?php endif; ?>
        <div class="filter-search">
    <div class="filter-group">
        <span>Filter:</span>
        <a href="?filter=all&per_page=<?= $per_page ?>" class="<?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
        <a href="?filter=applicants&per_page=<?= $per_page ?>" class="<?php echo $filter == 'applicants' ? 'active' : ''; ?>">Applicants</a>
        <a href="?filter=reviewers&per_page=<?= $per_page ?>" class="<?php echo $filter == 'reviewers' ? 'active' : ''; ?>">Reviewers</a>
    </div>
    <div class="search-group">
        <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="text" name="search" placeholder="Search by username or ID" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
            <?php if (!empty($search)): ?>
                <a href="?filter=<?php echo urlencode($filter); ?>&per_page=<?= $per_page ?>" style="background: #777;">Clear</a>
            <?php endif; ?>
            <select name="per_page" onchange="this.form.submit()">
                <option value="5" <?= $per_page == 5 ? 'selected' : '' ?>>5 per page</option>
                <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 per page</option>
                <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20 per page</option>
                <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 per page</option>
            </select>
        </form>
    </div>
</div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td title="<?= htmlspecialchars($user['username']) ?>"  style="max-width: 150px;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">
                            <?= htmlspecialchars(truncateString($user['username'], 20)) ?>
                        </td>
                        <td title="<?= htmlspecialchars($user['email_address']) ?>"  style="max-width: 150px;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">
                            <?= htmlspecialchars(truncateString($user['email_address'], 20)) ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="role" onchange="this.form.submit()">
                                    <option value="reviewer" <?php echo $user['role'] == 'reviewer' ? 'selected' : ''; ?>>Reviewer</option>
                                    <option value="applicant" <?php echo $user['role'] == 'applicant' ? 'selected' : ''; ?>>Applicant</option>
                                </select>
                                <input type="hidden" name="update_role" value="1">
                            </form>
                        </td>
                        <td>
    <a href="delete_user?id=<?php echo $user['id']; ?>" onclick="return confirmDeleteUser(event);" class="action-btn"><i class="fa-duotone fa-solid fa-trash"></i></a>
</td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>

<div class="pagination">
    <div class="pagination-info">
        Showing page <?= $page ?> of <?= $total_pages ?> (Total: <?= $total ?> users)
    </div>
    <div class="pagination-links">
        <?php if ($page > 1): ?>
            <a href="?page=1&filter=<?= $filter ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>">First</a>
            <a href="?page=<?= $page-1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>">Previous</a>
        <?php endif; ?>

        <?php 

        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);

        if ($start > 1) echo '<span>...</span>';
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>" 
               <?= $i == $page ? 'class="active"' : '' ?>>
                <?= $i ?>
            </a>
        <?php endfor;
        if ($end < $total_pages) echo '<span>...</span>';
        ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>">Next</a>
            <a href="?page=<?= $total_pages ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>">Last</a>
        <?php endif; ?>
    </div>
</div>
        <br>
        <br>
        <br>
        <br>
    </div>

    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 style="text-align: center;margin-bottom: 10px;">Add New User</h2>
            <form method="post" id="addUserForm">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email_address">Email Address:</label>
                    <input type="email" id="email_address" name="email_address" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div id="passwordError" class="error"></div>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="reviewer">Reviewer</option>
                        <option value="applicant">Applicant</option>
                    </select>
                </div>
                <button type="submit" class="btn" name="add_user">Add User</button>
            </form>
        </div>
    </div>
<div id="addUserModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" id="closeAddUserModal">&times;</span>
        <h2 style="text-align: center;margin-bottom: 10px;">Add New User</h2>
        <form method="post" id="addUserForm" autocomplete="off">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email_address">Email Address:</label>
                <input type="email" id="email_address" name="email_address" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div id="passwordError" class="error" style="color:#a31313;"></div>
            </div>
            <button type="submit" class="btn" name="add_user">Add User</button>
        </form>
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

<script src="js/users.min.js"></script>
<script>
document.getElementById('showAddUserModal').onclick = function() {
    document.getElementById('addUserModal').style.display = 'block';
};
document.getElementById('closeAddUserModal').onclick = function() {
    document.getElementById('addUserModal').style.display = 'none';
};
window.onclick = function(event) {
    if (event.target === document.getElementById('addUserModal')) {
        document.getElementById('addUserModal').style.display = 'none';
    }
};

// Password match validation
document.getElementById('addUserForm').onsubmit = function(e) {
    var pw = document.getElementById('password').value;
    var cpw = document.getElementById('confirm_password').value;
    var error = document.getElementById('passwordError');
    if (pw !== cpw) {
        error.textContent = "Passwords do not match!";
        e.preventDefault();
        return false;
    }
    error.textContent = "";
};
</script>
</body>
</html>