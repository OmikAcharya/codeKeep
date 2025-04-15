<?php
require 'config.php';

if (!isset($_SESSION['name']) && !isset($_COOKIE['name'])) {
    echo "Session Error";
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['name']) && isset($_SESSION['email'])) {
    $name = $_SESSION['name'];
    $email = $_SESSION['email'];

    $stmt = $conn->prepare("SELECT codechef_id, leetcode_id, codeforces_id FROM profile WHERE Uemail = ?");

    if ($stmt) {
        $stmt->bind_param("s", $email);  // Bind the email as a string
        $stmt->execute();
        $stmt->bind_result($codechef_id, $leetcode_id, $codeforces_id);
        $stmt->fetch();
        $stmt->close();
    } else {
        die("Query preparation failed: " . $conn->error);
    }
}

// Fetch ratings (you would need to implement these functions)
function getLeetcodeRating($username)
{
    // This is a placeholder - implement actual API call
    return rand(1500, 2800); // Placeholder random rating
}

function getCodechefRating($username)
{
    // This is a placeholder - implement actual API call
    return rand(1500, 2800); // Placeholder random rating
}

function getCodeforcesRating($username)
{
    // This is a placeholder - implement actual API call
    return rand(1500, 2800); // Placeholder random rating
}

// Get ratings if IDs exist
$leetcode_rating = !empty($leetcode_id) ? getLeetcodeRating($leetcode_id) : "N/A";
$codechef_rating = !empty($codechef_id) ? getCodechefRating($codechef_id) : "N/A";
$codeforces_rating = !empty($codeforces_id) ? getCodeforcesRating($codeforces_id) : "N/A";
$total_rating = is_numeric($leetcode_rating) && is_numeric($codechef_rating) && is_numeric($codeforces_rating) ?
    ($leetcode_rating + $codechef_rating + $codeforces_rating) / 3 : "N/A";

// Fetch contests
$codechef_url = 'https://www.codechef.com/api/list/contests/all?sort_by=START&sorting_order=asc&offset=0&mode=all';
$codeforces_url = 'https://codeforces.com/api/contest.list';

$codechef_response = file_get_contents($codechef_url);
$codechef_data = json_decode($codechef_response, true);

$codeforces_response = file_get_contents($codeforces_url);
$codeforces_data = json_decode($codeforces_response, true);

$future_contests = $codechef_data['future_contests'] ?? [];
$present_contests = $codechef_data['present_contests'] ?? [];

$cf_future_contests = [];
if ($codeforces_data && isset($codeforces_data['result'])) {
    foreach ($codeforces_data['result'] as $contest) {
        if ($contest['phase'] === 'BEFORE') {
            $cf_future_contests[] = [
                'contest_name' => $contest['name'],
                'contest_code' => $contest['id'],
                'contest_start_date' => date('d M Y H:i:s', $contest['startTimeSeconds']), // Convert Unix timestamp
                'contest_end_date' => date('d M Y H:i:s', $contest['startTimeSeconds'] + $contest['durationSeconds']),
            ];
        }
    }
}

$merged_future_contests = array_merge($future_contests, $cf_future_contests);

usort($merged_future_contests, function ($a, $b) {
    return strtotime($a['contest_start_date']) - strtotime($b['contest_start_date']);
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competitive Programming Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./dashboard.css">
</head>

<body>
    <!-- Sidebar -->
     
    <div class="sidebar">
        <div class="logo">
            <img src="https://picsum.photos/200/200" alt="Profile Picture">
            <span class="logo-text">CodeCase</span>
        </div>

        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="./allusers.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>All Users</span>
            </a>
            <a href="contests.php" class="nav-item">
                <i class="fas fa-calendar"></i>
                <span>Contests</span>
            </a>
            <a href="problems.php" class="nav-item">
                <i class="fas fa-code"></i>
                <span>Problems</span>
            </a>
            <a href="notes.php" class="nav-item">
                <i class="fas fa-sticky-note"></i>
                <span>Notes</span>
            </a>
        </div>

        <div class="configure-profiles">
            <a href="profile.php" class="configure-btn">
                <i class="fas fa-user-cog"></i>
                <span>Configure Profiles</span>
            </a>
        </div>

        <div class="user-profile">
            <div class="user-avatar">
                <?php echo substr($name, 0, 1); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                
            </div>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>
    <div class="extra" style="width: 220px;
    background-color: #171923;
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #2d3748;">

     </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Stats Cards -->
        <div class="stats-grid" style="display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 20px;">
            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-code leetcode-icon"></i>
                    <span>LeetCode</span>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($leetcode_rating); ?></div>
                <div class="stat-change">
                    <span><?php echo htmlspecialchars($leetcode_id); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-utensils codechef-icon"></i>
                    <span>CodeChef</span>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($codechef_rating); ?></div>
                <div class="stat-change">
                    <span><?php echo htmlspecialchars($codechef_id); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-laptop-code codeforces-icon"></i>
                    <span>Codeforces</span>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($codeforces_rating); ?></div>
                <div class="stat-change">
                    <span><?php echo htmlspecialchars($codeforces_id); ?></span>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Performance Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-title">Performance</div>
                    </div>

                    <div class="chart-container">
                        <p>Performance data will be displayed here</p>
                    </div>
                </div>

                <!-- Total Count Card -->
                <div class="dashboard-card" style="height:50vh">
                    <div class="card-header">
                        <div class="card-title">Total Solved Problems</div>
                    </div>

                    <div class="donut-chart">
                        <div class="donut-ring"></div>
                        <div class="donut-hole">
                            <div class="donut-label">Total</div>
                            <div class="donut-value">
                                <?php
                                // This is a placeholder - implement actual count
                                echo rand(100, 500);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <!-- Contests Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-title">Upcoming Contests</div>
                    </div>

                    <div class="details-list">
                        <?php
                        $count = 0;
                        foreach ($merged_future_contests as $contest) {
                            if ($count >= 5) break; // Limit to 5 contests
                        ?>
                            <div class="details-item">
                                <div class="details-label"><?php echo htmlspecialchars($contest['contest_name']); ?></div>
                                <div class="details-value">
                                    ID: <?php echo htmlspecialchars($contest['contest_code']); ?>
                                    <div class="contest-date">
                                        <span>Start: <?php echo htmlspecialchars($contest['contest_start_date']); ?></span>
                                    </div>
                                    <div class="contest-date">
                                        <span>End: <?php echo htmlspecialchars($contest['contest_end_date']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php
                            $count++;
                        }

                        if (count($merged_future_contests) === 0) {
                            echo '<div class="details-item"><div class="details-value">No upcoming contests found.</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

  

    <script>
        function confirmLogout() {
            let logoutConfirm = confirm("Are you sure you want to logout?");
            if (logoutConfirm) {
                window.location.href = 'logout.php';
            }
        }
        
    </script>
</body>

</html>