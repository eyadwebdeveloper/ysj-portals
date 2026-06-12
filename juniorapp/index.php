<?php 
echo '<script>
document.addEventListener("contextmenu", function(e) {
    e.preventDefault();
});

// Optional: Prevent keyboard shortcuts for dev tools
document.addEventListener("keydown", function(e) {
    // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
    if (e.key === "F12" || 
        (e.ctrlKey && e.shiftKey && e.key === "I") || 
        (e.ctrlKey && e.shiftKey && e.key === "J") || 
        (e.ctrlKey && e.shiftKey && e.key === "C") || 
        (e.ctrlKey && e.key === "U")) {
        e.preventDefault();
    }
});
</script>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | 2025 YSJ Junior Application</title>
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.6.0/css/all.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        p.welcome {
            font-size: 20px;
            text-align: center;
            max-width: 650px;
            margin: auto;
            margin-top: 50px;
        }
        .links {
            display: flex;
            justify-content: center;
            gap: 10px;
            width: 100%;margin-top: 10px;
        }
        .links a {
            background: #a31313;
            padding: 10px;
            font-size: 20px;
            color: #fff;
            text-decoration: none;
            width: 250px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <img src="./images/ysj-cover.jpg" alt="">
        <div class="control-head">
            <h2>2025 YSJ Junior Application</h2>
        </div>


        <div class="actions">
            <a href="login">Login</a>
            <a href="register">Create account</a>
        </div>
        <p class="welcome">Welcome to the Junior Application for the 2025 season of the YSJ annual program! Get started by logging in to your account or creating a new one.</p>
        <div class="links">
            <a href="login">Login</a>
            <a href="register">Create account</a>
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
</body>
</html>