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
    if (empty($username)) {
        return "N/A";
    }

    $url = "https://competeapi.vercel.app/user/leetcode/{$username}/";

    try {
        $response = @file_get_contents($url);

        if ($response === false) {
            return "Error";
        }

        $data = json_decode($response, true);

        if (isset($data['data']) && isset($data['data']['matchedUser'])) {
            $userData = $data['data']['matchedUser'];

            // Extract problems solved by difficulty
            $totalSolved = 0;
            $easySolved = 0;
            $mediumSolved = 0;
            $hardSolved = 0;

            if (isset($userData['submitStats']['acSubmissionNum'])) {
                foreach ($userData['submitStats']['acSubmissionNum'] as $stat) {
                    if ($stat['difficulty'] === 'All') {
                        $totalSolved = $stat['count'];
                    } elseif ($stat['difficulty'] === 'Easy') {
                        $easySolved = $stat['count'];
                    } elseif ($stat['difficulty'] === 'Medium') {
                        $mediumSolved = $stat['count'];
                    } elseif ($stat['difficulty'] === 'Hard') {
                        $hardSolved = $stat['count'];
                    }
                }
            }

            // Get streak information
            $streak = isset($userData['userCalendar']['streak']) ? $userData['userCalendar']['streak'] : 0;
            $totalActiveDays = isset($userData['userCalendar']['totalActiveDays']) ? $userData['userCalendar']['totalActiveDays'] : 0;

            // Get avatar URL
            $avatarUrl = isset($userData['profile']['userAvatar']) ? $userData['profile']['userAvatar'] : '';

            // Return as an array with all the information
            return [
                'total_solved' => $totalSolved,
                'easy_solved' => $easySolved,
                'medium_solved' => $mediumSolved,
                'hard_solved' => $hardSolved,
                'streak' => $streak,
                'total_active_days' => $totalActiveDays,
                'avatar_url' => $avatarUrl
            ];
        }

        return "Error parsing data";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function getCodechefRating($username)
{
    if (empty($username)) {
        return "N/A";
    }

    $url = "https://competeapi.vercel.app/user/codechef/{$username}/";

    try {
        $response = @file_get_contents($url);

        if ($response === false) {
            return "Error";
        }

        $data = json_decode($response, true);

        // Check if the response contains rating data
        if (isset($data['rating_number'])) {
            return $data['rating_number']; // Return just the rating value
        }

        return "No rating";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function getCodeforcesRating($username)
{
    if (empty($username)) {
        return "N/A";
    }

    $url = "https://competeapi.vercel.app/user/codeforces/{$username}/";

    try {
        $response = @file_get_contents($url);

        if ($response === false) {
            return "Error";
        }

        $data = json_decode($response, true);

        // Check if the response is an array and contains user info in the first element
        if (is_array($data) && !empty($data) && isset($data[0]['rating'])) {
            // Extract additional information for display
            $userInfo = [
                'rating' => $data[0]['rating'],
                'max_rating' => $data[0]['maxRating'],
                'rank' => $data[0]['rank'],
                'avatar' => $data[0]['avatar'],
                'handle' => $data[0]['handle']
            ];

            return $userInfo;
        }

        return "No rating";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Get ratings if IDs exist
$leetcode_data = !empty($leetcode_id) ? getLeetcodeRating($leetcode_id) : "N/A";
$codechef_rating = !empty($codechef_id) ? getCodechefRating($codechef_id) : "N/A";
$codeforces_data = !empty($codeforces_id) ? getCodeforcesRating($codeforces_id) : "N/A";

// Extract LeetCode rating (using total problems solved as a proxy for rating)
$leetcode_rating = is_array($leetcode_data) ? $leetcode_data['total_solved'] : $leetcode_data;

// Extract Codeforces rating
$codeforces_rating = is_array($codeforces_data) ? $codeforces_data['rating'] : $codeforces_data;

// Calculate total rating (if all are numeric)
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
            <?php if (is_array($leetcode_data) && !empty($leetcode_data['avatar_url'])): ?>
                <img src="<?php echo htmlspecialchars($leetcode_data['avatar_url']); ?>" alt="Profile Picture">
            <?php elseif (is_array($codeforces_data) && !empty($codeforces_data['avatar']) && $codeforces_data['avatar'] !== 'https://userpic.codeforces.org/no-avatar.jpg'): ?>
                <img src="<?php echo htmlspecialchars($codeforces_data['avatar']); ?>" alt="Profile Picture">
            <?php else: ?>
                <img src="https://picsum.photos/200/200" alt="Profile Picture">
            <?php endif; ?>
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
                <div class="stat-value"><?php echo htmlspecialchars($leetcode_rating); ?> Problems</div>
                <div class="stat-details">
                    <?php if (is_array($leetcode_data)): ?>
                    <div class="problem-breakdown">
                        <span class="easy">Easy: <?php echo $leetcode_data['easy_solved']; ?></span>
                        <span class="medium">Medium: <?php echo $leetcode_data['medium_solved']; ?></span>
                        <span class="hard">Hard: <?php echo $leetcode_data['hard_solved']; ?></span>
                    </div>
                    <div class="streak-info">
                        <span>Streak: <?php echo $leetcode_data['streak']; ?> days</span>
                        <span>Active: <?php echo $leetcode_data['total_active_days']; ?> days</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="stat-change">
                    <span><?php echo htmlspecialchars($leetcode_id); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-utensils codechef-icon"></i>
                    <span>CodeChef</span>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($codechef_rating); ?> Rating</div>
                <div class="stat-change">
                    <span><?php echo htmlspecialchars($codechef_id); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-title">
                    <i class="fas fa-laptop-code codeforces-icon"></i>
                    <span>Codeforces</span>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($codeforces_rating); ?> Rating</div>
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
                        <div class="card-title">HeatMap CodeChef</div>
                    </div>

                    <div class="chart-container">
                        <iframe src="https://codechef-api.vercel.app/heatmap/omitron" style="width: 100%; height: 100%"></iframe>
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
                                // Use actual LeetCode data if available
                                $totalCount = 0;
                                if (is_array($leetcode_data)) {
                                    $totalCount += $leetcode_data['total_solved'];
                                }
                                // Add other platforms' counts here when implemented

                                echo $totalCount > 0 ? $totalCount : 'N/A';
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