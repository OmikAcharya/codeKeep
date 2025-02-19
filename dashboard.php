<?php
require 'config.php';

if (!isset($_SESSION['name'])) {
    echo "Session Error";
    header("Location: login.php");
    exit;
}

$name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <script>
        function confirmLogout() {
            let logoutConfirm = confirm("Are you sure you want to logout?");
            if (logoutConfirm) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</head>
<body>
    <div class="main">
        <div class="navbar">
            <div class="profile">
                <!-- You can display a user icon or avatar here -->
            </div>
            <div class="items">
                <ul>
                    <li>Home</li>
                    <li>About</li>
                    <li>Contact</li>
                    <li><a href="#" onclick="confirmLogout()">Logout</a></li>
                </ul>
            </div>
        </div>
        <div class="container">
            <div class="helloCard">
                <h1 style="font-size:84px;">Welcome back</h1>
                <h1 style="font-size:65px;"><?php echo htmlspecialchars($name); ?></h1>
            </div>
            <div class="box">
                <div class="cards">
                    <div class="graph">
                        <h1>Graph</h1>
                    </div>
                    <div class="tp">
                        <div class="problem">
                            <h1>Problems</h1>
                        </div>
                        <div class="notes">
                            <h1>Notes to remember</h1>
                        </div>
                    </div>
                </div>
                <div class="calendar">
                    <h1>Calendar</h1>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
