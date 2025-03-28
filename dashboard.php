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
function getLeetcodeRating($username) {
    // This is a placeholder - implement actual API call
    return rand(1500, 2800); // Placeholder random rating
}

function getCodechefRating($username) {
    // This is a placeholder - implement actual API call
    return rand(1500, 2800); // Placeholder random rating
}

function getCodeforcesRating($username) {
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
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: #0f1117;
            color: #fff;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 220px;
            background-color: #171923;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #2d3748;
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            margin-bottom: 10px;
        }

        .logo img {
            width: 30px;
            height: 30px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 600;
        }
        .nav-menu {
            flex: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #a0aec0;
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 5px;
        }

        .nav-item i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .nav-item.active {
            background-color: #2563eb;
            color: white;
            border-radius: 0 4px 4px 0;
        }

        .nav-item:hover:not(.active) {
            background-color: #2d3748;
            color: white;
        }

        .badge {
            margin-left: auto;
            background-color: #2d3748;
            color: #a0aec0;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 12px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-top: 1px solid #2d3748;
            margin-top: auto;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #38b2ac;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
            font-weight: bold;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 500;
            font-size: 14px;
        }

        .user-email {
            color: #a0aec0;
            font-size: 12px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: #1e2130;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .stat-title {
            display: flex;
            align-items: center;
            color: #a0aec0;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-title i {
            margin-right: 8px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .leetcode-icon {
            background-color: #f59e0b;
            color: white;
        }

        .codechef-icon {
            background-color: #3182ce;
            color: white;
        }

        .codeforces-icon {
            background-color: #805ad5;
            color: white;
        }

        .total-icon {
            background-color: #f6ad55;
            color: white;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-change {
            display: flex;
            align-items: center;
            font-size: 12px;
        }

        .stat-change.up {
            color: #48bb78;
        }

        .stat-change.down {
            color: #f56565;
        }

        .stat-change i {
            margin-right: 4px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .dashboard-card {
            background-color: #1e2130;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        /* Chart Styles */
        .chart-container {
            height: 200px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a0aec0;
            font-size: 16px;
        }

        .details-list {
            display: flex;
            flex-direction: column;
        }

        .details-item {
            display: flex;
            flex-direction: column;
            padding: 12px 0;
            border-bottom: 1px solid #2d3748;
        }

        .details-item:last-child {
            border-bottom: none;
        }

        .details-label {
            color: #a0aec0;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .details-value {
            font-weight: 400;
            font-size: 14px;
            line-height: 1.5;
        }

        .contest-date {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 12px;
            color: #a0aec0;
        }

        .donut-chart {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto;
        }

        .donut-hole {
            position: absolute;
            width: 120px;
            height: 120px;
            background-color: #1e2130;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .donut-ring {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(
                #3182ce 0% 25%,
                #48bb78 25% 55%,
                #805ad5 55% 75%,
                #f6ad55 75% 100%
            );
        }

        .donut-label {
            font-size: 12px;
            color: #a0aec0;
        }

        .donut-value {
            font-size: 24px;
            font-weight: 700;
        }

        .logout-btn {
            background-color: #e53e3e;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }

        .logout-btn:hover {
            background-color: #c53030;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="https://picsum.photos/200/200" alt="Profile Picture">
            <span class="logo-text">CodeTracker</span>
        </div>
        
        <div class="nav-menu">
            <a href="#" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="./allusers.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>All Users</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-calendar"></i>
                <span>Contests</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-code"></i>
                <span>Problems</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-sticky-note"></i>
                <span>Notes</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
        
        <div class="logo">
            <img src="https://picsum.photos/200/200" alt="Profile Picture">
            <span class="logo-text"></span>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Stats Cards -->
        <div class="stats-grid">
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
                <div class="dashboard-card">
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