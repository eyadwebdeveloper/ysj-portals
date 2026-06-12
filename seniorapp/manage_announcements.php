<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user_exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$user_exists) {
    die("Invalid user account");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!isset($_POST['csrf_token'])) {
        die("CSRF token missing");
    }
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    if (isset($_POST['delete'])) {

        $id = (int)$_POST['id'];

        $stmt = $conn->prepare("SELECT title FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $announcement = $result->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            $log_stmt = $conn->prepare("INSERT INTO announcements_activity_log (user_id, action, details) VALUES (?, ?, ?)");
            $action = "Deleted announcement";
            $details = "Title: " . $announcement['title'];
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $action, $details);
            $log_stmt->execute();
            $log_stmt->close();

            header("Location: manage_announcements");
            exit();
        } else {
            die("Error deleting announcement: " . $stmt->error);
        }
        $stmt->close();

    } elseif (isset($_POST['title']) && isset($_POST['content'])) {

        $title = htmlspecialchars($_POST['title']);
        $content = $_POST['content']; 

        $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check_user->bind_param("s", $_SESSION['user_id']);
        $check_user->execute();

        if ($check_user->get_result()->num_rows === 0) {
            die("Error: Your user account is invalid");
        }
        $check_user->close();

        if (isset($_POST['id'])) {

            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
            $stmt->bind_param("ssi", $title, $content, $id);
            $action = "Updated announcement";
        } else {

            $stmt = $conn->prepare("INSERT INTO announcements (title, content, user_id) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $content, $_SESSION['user_id']);
            $action = "Created new announcement";
        }

        if (!$stmt->execute()) {
            die("Error saving announcement: " . $stmt->error);
        }
        $announcement_id = $conn->insert_id; // Get the ID of the new announcement
        $stmt->close();


        $log_stmt = $conn->prepare("INSERT INTO announcements_activity_log (user_id, action, details) VALUES (?, ?, ?)");
        $details = "Title: $title";
        $log_stmt->bind_param("iss", $_SESSION['user_id'], $action, $details);
        $log_stmt->execute();
        $log_stmt->close();

        header("Location: manage_announcements");
        exit();
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conn->prepare("SELECT a.*, u.username FROM announcements a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$editing = false;
$current_announcement = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_announcement = $result->fetch_assoc();
    $stmt->close();
    $editing = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements</title>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Paaji+2&family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/announcements.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>Manage Announcements</h2>
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

        <h3><?= $editing ? 'Edit Announcement' : 'Create New Announcement' ?></h3>
        <div class="announcement-form">
            <form method="POST" class="form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?= $current_announcement['id'] ?>">
                <?php endif; ?>
                <input type="text" name="title" placeholder="Title" 
                       value="<?= $editing ? htmlspecialchars($current_announcement['title']) : '' ?>" required>
                <textarea name="content" placeholder="Content" required><?= 
                    $editing ? htmlspecialchars($current_announcement['content']) : '' 
                ?></textarea>
                <br>
                <div style="display: flex; gap: 10px">
                    <button type="submit" class="button"><?= $editing ? 'Update' : 'Create' ?> Announcement</button>
                <?php if ($editing): ?>
                    <a href="manage_announcements" style="text-decoration: none;" class="button">Cancel</a>
                <?php endif; ?>
                </div>
            </form>
        </div>
        <h3>Current Announcements</h3>
        <div class="announcements">
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement">
                    <div>
                        <h4><?= htmlspecialchars($announcement['title']) ?></h4>
                        <h5><?= date('d M, Y', strtotime($announcement['created_at'])) ?> - Posted by YSJ management</h5>
                    </div>
                    <div class="actionss">
                        <a href="manage_announcements?edit=<?= $announcement['id'] ?>" class="button">Edit</a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id" value="<?= $announcement['id'] ?>">
                            <button type="submit" name="delete" class="button" onclick="return confirm('Are you sure you want to delete this announcement?')">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <br><br><br>
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
        <script src="https://cdn.tiny.cloud/1/m4ysb3dyzs630nwibwmy6uqnaoqakwoxyqwztqk9xmkp93pv/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

    <script>
  tinymce.init({
    selector: 'textarea',
    plugins: [

      'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',

      'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage', 'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown','importword', 'exportword', 'exportpdf'
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    mergetags_list: [
      { value: 'YSJ', title: 'First Name' },
      { value: 'ysciencejournal@gmail.com', title: 'Email' },
    ],
    font_family_formats:
    'Poppins=Poppins,sans-serif;' +
    'Baloo Paaji 2=Baloo Paaji 2,cursive;' +
    'Arial=arial,helvetica,sans-serif;' +
    'Courier New=courier new,courier,monospace;' +
    'Times New Roman=times new roman,times;',
  content_style: `
    @import url('https://fonts.googleapis.com/css2?family=Baloo+Paaji+2&family=Poppins&display=swap');
    body { font-family: Poppins, 'Baloo Paaji 2', Arial, sans-serif; }
  `,
    ai_request: (request, respondWith) => respondWith.string(() => Promise.reject('See docs to implement AI Assistant')),
  });
</script>
</body>
</html>