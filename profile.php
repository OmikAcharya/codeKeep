<?php 
require 'config.php';

if (!isset($_SESSION['email'])) {
    die("Session error: User not logged in.");
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codechef_id = $_POST['codechef_id'] ?? '';
    $codeforces_id = $_POST['codeforces_id'] ?? '';
    $leetcode_id = $_POST['leetcode_id'] ?? '';

    $stmt = $conn->prepare("INSERT INTO profile(Uemail, codechef_id, codeforces_id, leetcode_id) VALUES(?,?,?,?)");
    $stmt->bind_param("ssss", $_SESSION['email'], $codechef_id, $codeforces_id, $leetcode_id);
    $stmt->execute();

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
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        button {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div style="display: flex; flex-direction: row-reverse; justify-content: space-between; align-items: center; width: 100%;">
            <h1>&lt;Code<span style="color: #1a4eaf">Keep&gt;</span></h1>
            <h1><span style="text-align: left;">Connect Profiles</span></h1>
        </div>
        <form action="profile.php" method="POST" style="width: 100%;">
            <div class="form-group">
                <label for="codechef">CodeChef ID</label>
                <input type="text" id="codechef" name="codechef_id" placeholder="CodeChef ID" required>
            </div>
            <div class="form-group">
                <label for="codeforces">Codeforces ID</label>
                <input type="text" id="codeforces" name="codeforces_id" placeholder="Codeforces ID" required>
            </div>
            <div class="form-group" style="padding-bottom: 10px">
                <label for="leetcode">LeetCode ID</label>
                <input type="text" id="leetcode" name="leetcode_id" placeholder="LeetCode ID" required>
            </div>
            <button type="submit" style="padding-bottom: 10px">Connect Profiles</button>
        </form>
        <div style="display: flex; justify-content: center; margin-top: 10px; gap: 5px;">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>