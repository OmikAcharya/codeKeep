<?php 
require 'config.php';

if (!isset($_SESSION['email'])) {
    die("Session error: User not logged in.");
}

// Check if user has existing profile data
$userEmail = $_SESSION['email'];
$existingProfile = null;

$checkProfile = $conn->prepare("SELECT codechef_id, codeforces_id, leetcode_id FROM profile WHERE Uemail = ?");
$checkProfile->bind_param("s", $userEmail);
$checkProfile->execute();
$result = $checkProfile->get_result();

if ($result->num_rows > 0) {
    $existingProfile = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codechef_id = $_POST['codechef_id'] ?? '';
    $codeforces_id = $_POST['codeforces_id'] ?? '';
    $leetcode_id = $_POST['leetcode_id'] ?? '';

    if ($existingProfile) {
        // Update existing profile
        $stmt = $conn->prepare("UPDATE profile SET codechef_id = ?, codeforces_id = ?, leetcode_id = ? WHERE Uemail = ?");
        $stmt->bind_param("ssss", $codechef_id, $codeforces_id, $leetcode_id, $userEmail);
    } else {
        // Insert new profile
        $stmt = $conn->prepare("INSERT INTO profile(Uemail, codechef_id, codeforces_id, leetcode_id) VALUES(?,?,?,?)");
        $stmt->bind_param("ssss", $userEmail, $codechef_id, $codeforces_id, $leetcode_id);
    }
    $stmt->execute();

    echo "Profile updated successfully!";
    header("Location: dashboard.php");
    exit;
}

// Initialize variables with empty values or existing profile values
$codechef_value = $existingProfile ? $existingProfile['codechef_id'] : '';
$codeforces_value = $existingProfile ? $existingProfile['codeforces_id'] : '';
$leetcode_value = $existingProfile ? $existingProfile['leetcode_id'] : '';
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
            <h1>&lt;Code<span style="color: #1a4eaf">Case&gt;</span></h1>
            <h1><span style="text-align: left;">Connect Profiles</span></h1>
        </div>
        <form action="profile.php" method="POST" style="width: 100%;">
            <div class="form-group">
                <label for="codechef">CodeChef ID</label>
                <input type="text" id="codechef" name="codechef_id" placeholder="CodeChef ID" value="<?php echo htmlspecialchars($codechef_value); ?>" required>
            </div>
            <div class="form-group">
                <label for="codeforces">Codeforces ID</label>
                <input type="text" id="codeforces" name="codeforces_id" placeholder="Codeforces ID" value="<?php echo htmlspecialchars($codeforces_value); ?>" required>
            </div>
            <div class="form-group" style="padding-bottom: 10px">
                <label for="leetcode">LeetCode ID</label>
                <input type="text" id="leetcode" name="leetcode_id" placeholder="LeetCode ID" value="<?php echo htmlspecialchars($leetcode_value); ?>" required>
            </div>
            <button type="submit" style="padding-bottom: 10px">
                <?php echo $existingProfile ? 'Update Profiles' : 'Connect Profiles'; ?>
            </button>
        </form>
        <div style="display: flex; justify-content: center; margin-top: 10px; gap: 5px;">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>