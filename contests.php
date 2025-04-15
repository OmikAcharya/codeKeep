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

// Handle AJAX requests for saving contests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_contest') {
    if (!isset($_SESSION['email'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    $user_email = $_SESSION['email'];
    $platform = $_POST['platform'] ?? '';
    $contest_name = $_POST['contest_name'] ?? '';
    $contest_code = $_POST['contest_code'] ?? '';
    $contest_start_date = $_POST['contest_start_date'] ?? '';
    $contest_end_date = $_POST['contest_end_date'] ?? '';

    if (empty($platform) || empty($contest_name) || empty($contest_code) || empty($contest_start_date) || empty($contest_end_date)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Check if contest is already saved
    $check_stmt = $conn->prepare("SELECT id FROM saved_contests WHERE user_email = ? AND platform = ? AND contest_code = ?");
    $check_stmt->bind_param("sss", $user_email, $platform, $contest_code);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Contest already saved']);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();

    // Save the contest
    $stmt = $conn->prepare("INSERT INTO saved_contests (user_email, platform, contest_name, contest_code, contest_start_date, contest_end_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $user_email, $platform, $contest_name, $contest_code, $contest_start_date, $contest_end_date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Contest saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save contest: ' . $stmt->error]);
    }

    $stmt->close();
    exit;
}

// Fetch contests
$codechef_url = 'https://www.codechef.com/api/list/contests/all?sort_by=START&sorting_order=asc&offset=0&mode=all';
$codeforces_url = 'https://codeforces.com/api/contest.list';
$leetcode_url = 'https://leetcode.com/graphql?query={%20allContests%20{%20title%20titleSlug%20startTime%20duration%20__typename%20}%20}';

$codechef_response = file_get_contents($codechef_url);
$codechef_data = json_decode($codechef_response, true);

$codeforces_response = file_get_contents($codeforces_url);
$codeforces_data = json_decode($codeforces_response, true);

// LeetCode API might not work as expected due to GraphQL restrictions
// This is a placeholder - you might need to use a different approach for LeetCode
try {
    $leetcode_response = file_get_contents($leetcode_url);
    $leetcode_data = json_decode($leetcode_response, true);
} catch (Exception $e) {
    $leetcode_data = null;
}

$future_contests = $codechef_data['future_contests'] ?? [];
$present_contests = $codechef_data['present_contests'] ?? [];

$cf_future_contests = [];
if ($codeforces_data && isset($codeforces_data['result'])) {
    foreach ($codeforces_data['result'] as $contest) {
        if ($contest['phase'] === 'BEFORE') {
            $cf_future_contests[] = [
                'platform' => 'Codeforces',
                'contest_name' => $contest['name'],
                'contest_code' => $contest['id'],
                'contest_start_date' => date('d M Y H:i:s', $contest['startTimeSeconds']), // Convert Unix timestamp
                'contest_end_date' => date('d M Y H:i:s', $contest['startTimeSeconds'] + $contest['durationSeconds']),
                'duration' => $contest['durationSeconds'] / 3600 . ' hours', // Convert to hours
            ];
        }
    }
}

// Format CodeChef contests
$formatted_codechef_contests = [];
foreach ($future_contests as $contest) {
    $formatted_codechef_contests[] = [
        'platform' => 'CodeChef',
        'contest_name' => $contest['contest_name'],
        'contest_code' => $contest['contest_code'],
        'contest_start_date' => $contest['contest_start_date'],
        'contest_end_date' => $contest['contest_end_date'],
        'duration' => (strtotime($contest['contest_end_date']) - strtotime($contest['contest_start_date'])) / 3600 . ' hours',
    ];
}

// Get saved contests
$saved_contests_stmt = $conn->prepare("SELECT platform, contest_code FROM saved_contests WHERE user_email = ?");
$saved_contests_stmt->bind_param("s", $email);
$saved_contests_stmt->execute();
$saved_contests_result = $saved_contests_stmt->get_result();

$saved_contest_keys = [];
while ($row = $saved_contests_result->fetch_assoc()) {
    $saved_contest_keys[] = $row['platform'] . '-' . $row['contest_code'];
}
$saved_contests_stmt->close();

// Merge all contests and mark saved ones
$all_contests = array_merge($formatted_codechef_contests, $cf_future_contests);

// Mark saved contests
foreach ($all_contests as &$contest) {
    $contest_key = $contest['platform'] . '-' . $contest['contest_code'];
    $contest['saved'] = in_array($contest_key, $saved_contest_keys);
}
unset($contest); // Break the reference

// Sort by start date
usort($all_contests, function ($a, $b) {
    return strtotime($a['contest_start_date']) - strtotime($b['contest_start_date']);
});

// Filter contests if needed
$platform_filter = $_GET['platform'] ?? 'all';
$saved_filter = isset($_GET['saved']) && $_GET['saved'] === '1';
$filtered_contests = $all_contests;

if ($platform_filter !== 'all') {
    $filtered_contests = array_filter($filtered_contests, function($contest) use ($platform_filter) {
        return strtolower($contest['platform']) === strtolower($platform_filter);
    });
}

if ($saved_filter) {
    $filtered_contests = array_filter($filtered_contests, function($contest) {
        return $contest['saved'] === true;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contests - CodeKeep</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .filter-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            background-color: #1e2130;
            border: 1px solid #2d3748;
            color: #a0aec0;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background-color: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .contest-card {
            background-color: #1e2130;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }

        .codechef {
            border-left-color: #3182ce;
        }

        .codeforces {
            border-left-color: #805ad5;
        }

        .leetcode {
            border-left-color: #f59e0b;
        }

        .contest-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .contest-title {
            font-size: 18px;
            font-weight: 600;
        }

        .contest-platform {
            font-size: 14px;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
        }

        .platform-codechef {
            background-color: #3182ce;
        }

        .platform-codeforces {
            background-color: #805ad5;
        }

        .platform-leetcode {
            background-color: #f59e0b;
        }

        .contest-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #a0aec0;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 14px;
        }

        .contest-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }

        .contest-btn {
            padding: 8px 16px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .contest-btn:hover {
            background-color: #1d4ed8;
        }

        .save-btn {
            background-color: transparent;
            border: 1px solid #2d3748;
            color: #a0aec0;
        }

        .save-btn.saved {
            background-color: #48bb78;
            color: white;
            border-color: #48bb78;
        }

        .save-btn:hover {
            background-color: #2d3748;
            color: white;
        }

        @media (max-width: 768px) {
            .contest-details {
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
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="./allusers.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>All Users</span>
            </a>
            <a href="contests.php" class="nav-item active">
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

        <div class="user-profile">
            <div class="user-avatar">
                <?php echo substr($name, 0, 1); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
            </div>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Upcoming Contests</h1>
        <p>Stay updated with all the upcoming competitive programming contests.</p>

        <div class="filter-container">
            <a href="?platform=all<?php echo $saved_filter ? '&saved=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'all' ? 'active' : ''; ?>">All Platforms</a>
            <a href="?platform=codechef<?php echo $saved_filter ? '&saved=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'codechef' ? 'active' : ''; ?>">CodeChef</a>
            <a href="?platform=codeforces<?php echo $saved_filter ? '&saved=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'codeforces' ? 'active' : ''; ?>">Codeforces</a>
            <a href="?<?php echo $platform_filter !== 'all' ? 'platform=' . $platform_filter . '&' : ''; ?>saved=1" class="filter-btn <?php echo $saved_filter ? 'active' : ''; ?>">Saved Only</a>
            <a href="?<?php echo $platform_filter !== 'all' ? 'platform=' . $platform_filter : ''; ?>" class="filter-btn <?php echo !$saved_filter ? 'active' : ''; ?>">All Contests</a>
        </div>

        <div class="contests-container">
            <?php if (empty($filtered_contests)): ?>
                <div class="dashboard-card">
                    <p>No upcoming contests found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_contests as $contest): ?>
                    <div class="contest-card <?php echo strtolower($contest['platform']); ?>">
                        <div class="contest-header">
                            <div class="contest-title"><?php echo htmlspecialchars($contest['contest_name']); ?></div>
                            <div class="contest-platform platform-<?php echo strtolower($contest['platform']); ?>">
                                <?php echo htmlspecialchars($contest['platform']); ?>
                            </div>
                        </div>

                        <div class="contest-details">
                            <div class="detail-item">
                                <div class="detail-label">Contest ID</div>
                                <div class="detail-value"><?php echo htmlspecialchars($contest['contest_code']); ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Start Time</div>
                                <div class="detail-value"><?php echo htmlspecialchars($contest['contest_start_date']); ?></div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Duration</div>
                                <div class="detail-value"><?php echo htmlspecialchars($contest['duration']); ?></div>
                            </div>
                        </div>

                        <div class="contest-actions">
                            <button class="contest-btn save-btn <?php echo $contest['saved'] ? 'saved' : ''; ?>"
                                    onclick="saveContest('<?php echo htmlspecialchars($contest['platform']); ?>',
                                                     '<?php echo htmlspecialchars($contest['contest_name']); ?>',
                                                     '<?php echo htmlspecialchars($contest['contest_code']); ?>',
                                                     '<?php echo htmlspecialchars($contest['contest_start_date']); ?>',
                                                     '<?php echo htmlspecialchars($contest['contest_end_date']); ?>')">
                                <i class="fas <?php echo $contest['saved'] ? 'fa-bookmark' : 'fa-bookmark'; ?>"></i>
                                <?php echo $contest['saved'] ? 'Saved' : 'Save Contest'; ?>
                            </button>

                            <?php if (strtolower($contest['platform']) === 'codechef'): ?>
                                <a href="https://www.codechef.com/<?php echo htmlspecialchars($contest['contest_code']); ?>" target="_blank" class="contest-btn">
                                    <i class="fas fa-external-link-alt"></i> View Contest
                                </a>
                            <?php elseif (strtolower($contest['platform']) === 'codeforces'): ?>
                                <a href="https://codeforces.com/contest/<?php echo htmlspecialchars($contest['contest_code']); ?>" target="_blank" class="contest-btn">
                                    <i class="fas fa-external-link-alt"></i> View Contest
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmLogout() {
            let logoutConfirm = confirm("Are you sure you want to logout?");
            if (logoutConfirm) {
                window.location.href = 'logout.php';
            }
        }

        function saveContest(platform, contestName, contestCode, startDate, endDate) {
            // Create form data
            const formData = new FormData();
            formData.append('action', 'save_contest');
            formData.append('platform', platform);
            formData.append('contest_name', contestName);
            formData.append('contest_code', contestCode);
            formData.append('contest_start_date', startDate);
            formData.append('contest_end_date', endDate);

            // Get the button that was clicked
            const button = event.currentTarget;

            // Send AJAX request
            fetch('contests.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button appearance
                    button.classList.add('saved');
                    button.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the contest.');
            });
        }
    </script>
</body>
</html>
