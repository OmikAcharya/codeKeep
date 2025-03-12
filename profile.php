<?php 
require 'config.php';

if (!isset($_SESSION['email'])) {
    die("Session error: User not logged in.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platforms = [
        'codechef' => $_POST['codechef_id'] ?? '',
        'codeforces' => $_POST['codeforces_id'] ?? '',
        'leetcode' => $_POST['leetcode_id'] ?? ''
    ];

    foreach ($platforms as $platform => $profileID) {
        if (!empty($profileID)) {
            echo $profileID;
            echo $
            $stmt = $conn->prepare("INSERT INTO profile (Uemail, platformId) VALUES (?, ?)");
            
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("ss", $_SESSION['email'], $profileID);
            
            if (!$stmt->execute()) {
                die("Execution failed: " . $stmt->error);
            }

            $stmt->close();
        }
    }

    echo "Data inserted successfully!";
    header("Location: dashboard.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="login.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 600px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Code<span style="color: #1a4eaf">Keep</span></h1>
        <form action="profile.php" method="POST" style="display: flex; justify-content: center; align-items: center; flex-direction: column;">
            <div class="input-form">
                <input type="text" name="codechef_id" required>
                <label style="color: white;">
                    <span style="transition-delay:0ms">C</span><span style="transition-delay:50ms">o</span><span style="transition-delay:100ms">d</span><span style="transition-delay:150ms">e</span><span style="transition-delay:200ms">c</span><span style="transition-delay:250ms">h</span><span style="transition-delay:300ms">e</span><span style="transition-delay:350ms">f</span><span style="transition-delay:400ms"> </span><span style="transition-delay:450ms">I</span><span style="transition-delay:500ms">D</span>
                </label>
            </div>
            <div class="input-form">
                <input type="text" name="codeforces_id" required>
                <label style="color: white;">
                    <span style="transition-delay:0ms">C</span><span style="transition-delay:50ms">o</span><span style="transition-delay:100ms">d</span><span style="transition-delay:150ms">e</span><span style="transition-delay:200ms">f</span><span style="transition-delay:250ms">o</span><span style="transition-delay:300ms">r</span><span style="transition-delay:350ms">c</span><span style="transition-delay:400ms">e</span><span style="transition-delay:450ms">s</span><span style="transition-delay:500ms"> </span><span style="transition-delay:550ms">I</span><span style="transition-delay:600ms">D</span>
                </label>
            </div>
            <div class="input-form">
                <input type="text" name="leetcode_id" required>
                <label style="color: white;">
                    <span style="transition-delay:0ms">L</span><span style="transition-delay:50ms">e</span><span style="transition-delay:100ms">e</span><span style="transition-delay:150ms">t</span><span style="transition-delay:200ms">c</span><span style="transition-delay:250ms">o</span><span style="transition-delay:300ms">d</span><span style="transition-delay:350ms">e</span><span style="transition-delay:400ms"> </span><span style="transition-delay:450ms">I</span><span style="transition-delay:500ms">D</span>
                </label>
            </div>
            <button class="button" align="center">
                Connect
            </button>
        </form>
    </div>
</body>
</html>