<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role'])) {
    header("Location: login");
    exit();
}

if ($_SESSION['role'] != 'admin') {
    if ($_SESSION['role'] != 'reviewer') {
        header("Location: reviewer_dashboard");
        exit();
    }
    if ($_SESSION['role'] != 'applicant') {
        header("Location: application");
        exit();
    }
}

if (isset($_POST['toggle_app_status'])) {
    $new_status = $_POST['toggle_app_status'] === 'close' ? 'closed' : 'open';
    $conn->query("UPDATE app_action SET application_status='$new_status' LIMIT 1");
}

// Get current status
$status_row = $conn->query("SELECT application_status FROM app_action LIMIT 1")->fetch_assoc();
$app_status = $status_row ? $status_row['application_status'] : 'open';

$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$reviewers_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='reviewer'")->fetch_assoc()['count'];
$applications_count = $conn->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
        <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">

</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Admin Dashboard</h2>
            <a href="logout">Logout</a>
        </div>
        <div class="actions">
            <a href="admin_dashboard">Dashboard</a>
            <a href="users">Manage Users</a>
            <a href="applications">Manage Applications</a>
            <a href="decisions">Decisions</a>
            <a href="manage_announcements" class="active">Manage Announcements</a>
            <a href="emails" class="active">Seniors Emails</a>
        </div>
        <div class="stats">
            <div class="card">
                <h5>00<?php echo $users_count; ?></h5>
                <hr>
                <p>Total Users</p>
            </div>
            <div class="card">
                <h5>00<?php echo $applications_count; ?></h5>
                <hr>
                <p>Total Applications</p>
            </div>
            <div class="card">
                <h5>00<?php echo $reviewers_count; ?></h5>
                <hr>
                <p>Total Reviewers</p>
            </div>
        </div>
        <form method="post" id="toggleAppStatusForm" style="margin: 20px 50px;">
    <?php if ($app_status === 'open'): ?>
    <button type="button" name="toggle_app_status" value="close" class="btn" id="closeAppBtn" style="background:#d32f2f;">Close Application</button>
<?php else: ?>
    <button type="button" name="toggle_app_status" value="open" class="btn" id="openAppBtn" style="background:#388e3c;">Open Application</button>
    <span style="background: rgba(163, 19, 19, 0.21);color: #a31313;padding: 10px; font-size: 18px;border: 2px solid #a31313;margin: 10px;border-radius: 10px">Application is currently closed</span>
<?php endif; ?>
</form>
<div id="customConfirmBox" style="display:none"></div>

<script>
// Confirm box logic (same as application.php)
function showConfirmBox(message, background = '#a31313') {
    return new Promise(resolve => {
        let box = document.getElementById('customConfirmBox');
        if (!box) {
            box = document.createElement('div');
            box.id = 'customConfirmBox';
            document.body.appendChild(box);
        }
        box.style.position = 'fixed';
        box.style.top = '30px';
        box.style.left = '50%';
        box.style.transform = 'translateX(-50%)';
        box.style.padding = '18px 28px';
        box.style.background = background;
        box.style.color = '#fff';
        box.style.borderRadius = '7px';
        box.style.zIndex = '10001';
        box.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
        box.style.fontSize = '18px';
        box.style.minWidth = '320px';
        box.style.textAlign = 'center';
        box.style.opacity = '1';
        box.style.transition = 'opacity 0.5s';
        box.style.display = 'block';
        box.style.filter = '';

        box.innerHTML = `
            <div style="margin-bottom:18px;">${message}</div>
            <button id="confirmYesBtn" style="margin-right:18px;padding:8px 18px;background:#388e3c;color:#fff;border:none;border-radius:4px;cursor:pointer;">Yes</button>
            <button id="confirmNoBtn" style="padding:8px 18px;background:#d32f2f;color:#fff;border:none;border-radius:4px;cursor:pointer;">No</button>
        `;

        document.getElementById('confirmYesBtn').onclick = function () {
            box.style.opacity = '0';
            setTimeout(() => { box.style.display = 'none'; }, 500);
            resolve(true);
        };
        document.getElementById('confirmNoBtn').onclick = function () {
            box.style.opacity = '0';
            setTimeout(() => { box.style.display = 'none'; }, 500);
            resolve(false);
        };
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('toggleAppStatusForm');
    const closeBtn = document.getElementById('closeAppBtn');
    const openBtn = document.getElementById('openAppBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showConfirmBox('Are you sure you want to <b>close</b> the application? This will prevent users from registering or submitting applications.', '#a31313')
                .then(confirmed => {
                    if (confirmed) {
                        // Set hidden input and submit
                        setToggleStatusAndSubmit(form, 'close');
                    }
                });
        });
    }
    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showConfirmBox('Are you sure you want to <b>open</b> the application? This will allow users to register and submit applications.', '#a31313')
                .then(confirmed => {
                    if (confirmed) {
                        setToggleStatusAndSubmit(form, 'open');
                    }
                });
        });
    }

    function setToggleStatusAndSubmit(form, value) {
        let input = form.querySelector('input[name="toggle_app_status"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'toggle_app_status';
            form.appendChild(input);
        }
        input.value = value;
        form.submit();
    }
});
</script>
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
</body>
</html>