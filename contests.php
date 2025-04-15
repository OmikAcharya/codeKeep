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
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($codechef_id, $leetcode_id, $codeforces_id);
        $stmt->fetch();
        $stmt->close();
    } else {
        die("Query preparation failed: " . $conn->error);
    }
}

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

// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Function to format date in IST
function formatDateIST($timestamp) {
    return date('d M Y H:i:s', $timestamp) . ' IST';
}

$upcoming_contests_url = 'https://competeapi.vercel.app/contests/upcoming/';
$leetcode_contests_url = 'https://competeapi.vercel.app/contests/leetcode/';

try {
    $upcoming_response = file_get_contents($upcoming_contests_url);
    $upcoming_data = json_decode($upcoming_response, true);
} catch (Exception $e) {
    $upcoming_data = [];
}

try {
    $leetcode_response = file_get_contents($leetcode_contests_url);
    $leetcode_data = json_decode($leetcode_response, true);
} catch (Exception $e) {
    $leetcode_data = null;
}

$formatted_codechef_contests = [];
$cf_future_contests = [];

if (!empty($upcoming_data)) {
    foreach ($upcoming_data as $contest) {
        $start_time_seconds = $contest['startTime'] / 1000;
        $end_time_seconds = $contest['endTime'] / 1000;
        $duration_hours = $contest['duration'] / (1000 * 60 * 60);
        $url_parts = explode('/', rtrim($contest['url'], '/'));
        $contest_code = end($url_parts);

        $contest_data = [
            'platform' => ucfirst($contest['site']),
            'contest_name' => $contest['title'],
            'contest_code' => $contest_code,
            'contest_start_date' => formatDateIST($start_time_seconds),
            'contest_end_date' => formatDateIST($end_time_seconds),
            'duration' => round($duration_hours, 2) . ' hours',
            'url' => $contest['url']
        ];

        if (strtolower($contest['site']) === 'codechef') {
            $formatted_codechef_contests[] = $contest_data;
        } else if (strtolower($contest['site']) === 'codeforces') {
            $cf_future_contests[] = $contest_data;
        }
    }
}


$leetcode_contests = [];
if ($leetcode_data && isset($leetcode_data['data']) && isset($leetcode_data['data']['topTwoContests'])) {
    foreach ($leetcode_data['data']['topTwoContests'] as $contest) {
        $start_time_seconds = $contest['startTime'];
        $end_time_seconds = $start_time_seconds + $contest['duration'];
        $duration_hours = $contest['duration'] / 3600; // Convert seconds to hours

        $leetcode_contests[] = [
            'platform' => 'LeetCode',
            'contest_name' => $contest['title'],
            'contest_code' => str_replace(' ', '-', strtolower($contest['title'])),
            'contest_start_date' => formatDateIST($start_time_seconds),
            'contest_end_date' => formatDateIST($end_time_seconds),
            'duration' => round($duration_hours, 2) . ' hours',
            'url' => 'https://leetcode.com/contest/'
        ];
    }
}

$saved_contests_stmt = $conn->prepare("SELECT platform, contest_code FROM saved_contests WHERE user_email = ?");
$saved_contests_stmt->bind_param("s", $email);
$saved_contests_stmt->execute();
$saved_contests_result = $saved_contests_stmt->get_result();

$saved_contest_keys = [];
while ($row = $saved_contests_result->fetch_assoc()) {
    $saved_contest_keys[] = $row['platform'] . '-' . $row['contest_code'];
}
$saved_contests_stmt->close();

$all_contests = array_merge($formatted_codechef_contests, $cf_future_contests, $leetcode_contests);

foreach ($all_contests as &$contest) {
    $contest_key = $contest['platform'] . '-' . $contest['contest_code'];
    $contest['saved'] = in_array($contest_key, $saved_contest_keys);
}
unset($contest);

usort($all_contests, function ($a, $b) {
    return strtotime($a['contest_start_date']) - strtotime($b['contest_start_date']);
});

$platform_filter = $_GET['platform'] ?? 'all';
$saved_filter = isset($_GET['saved']) && $_GET['saved'] === '1';
$filtered_contests = $all_contests;

if ($platform_filter !== 'all') {
    $filtered_contests = array_filter($filtered_contests, function ($contest) use ($platform_filter) {
        return strtolower($contest['platform']) === strtolower($platform_filter);
    });
}

if ($saved_filter) {
    $filtered_contests = array_filter($filtered_contests, function ($contest) {
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
<<<<<<< HEAD
    <style>
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .filter-btn {
            padding: 10px 18px;
            background-color: rgba(30, 33, 48, 0.8);
            border: 1px solid #2d3748;
            color: #cbd5e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .filter-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: 0.5s;
        }

        .filter-btn:hover:before {
            left: 100%;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            background-color: #252a3d;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .contests-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }

        .contest-card {
            background: linear-gradient(145deg, #1e2130, #1a1d2a);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .contest-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .contest-card:after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 30%;
            height: 5px;
            background: inherit;
            opacity: 0.3;
            border-radius: 0 0 0 10px;
        }

        .codechef {
            border-left-color: #3182ce;
            box-shadow: 0 4px 20px rgba(49, 130, 206, 0.1);
        }

        .codeforces {
            border-left-color: #805ad5;
            box-shadow: 0 4px 20px rgba(128, 90, 213, 0.1);
        }

        .leetcode {
            border-left-color: #f59e0b;
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.1);
        }

        .contest-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }

        .contest-title {
            font-size: 20px;
            font-weight: 600;
            color: #f7fafc;
            margin-right: 10px;
            line-height: 1.4;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            flex: 1;
        }

        .contest-platform {
            font-size: 14px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
        }

        .platform-codechef {
            background: linear-gradient(135deg, #3182ce, #2c5282);
        }

        .platform-codeforces {
            background: linear-gradient(135deg, #805ad5, #6b46c1);
        }

        .platform-leetcode {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .leetcode {
            border-left-color: #f59e0b;
        }

        .contest-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            padding: 8px;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .detail-item:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }

        .detail-label {
            font-size: 12px;
            color: #a0aec0;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 500;
        }

        .detail-value {
            font-size: 14px;
            color: #e2e8f0;
            font-weight: 500;
            word-break: break-word;
        }

        .contest-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .contest-btn {
            padding: 10px 18px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.25);
        }

        .contest-btn:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(37, 99, 235, 0.3);
        }

        .contest-btn:active {
            transform: translateY(0);
        }

        .save-btn {
            background-color: transparent;
            border: 2px solid #4b5563;
            color: #cbd5e0;
            box-shadow: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .save-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: 0.5s;
            z-index: -1;
        }

        .save-btn:hover:before {
            left: 100%;
        }

        .save-btn:hover {
            background-color: #374151;
            border-color: #6b7280;
            color: white;
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.2);
        }

        .save-btn.saved {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }

        .save-btn.saved:hover {
            background: linear-gradient(135deg, #38a169, #2f855a);
        }

        /* Add platform icons */
        .platform-codechef:before {
            content: '🍽️';
            margin-right: 5px;
        }

        .platform-codeforces:before {
            content: '🏆';
            margin-right: 5px;
        }

        .platform-leetcode:before {
            content: '💻';
            margin-right: 5px;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .contests-container {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .contests-container {
                grid-template-columns: 1fr;
            }

            .contest-details {
                grid-template-columns: 1fr;
            }

            .filter-container {
                overflow-x: auto;
                padding-bottom: 10px;
                justify-content: flex-start;
            }

            .filter-btn {
                flex-shrink: 0;
            }
        }

        /* Loading animation */
        @keyframes pulse {
            0% {
                opacity: 0.6;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.6;
            }
        }

        /* Main heading animations */
        h1 {
            background: linear-gradient(135deg, #f7fafc, #cbd5e0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
            display: inline-block;
            margin-bottom: 0.5em;
        }

        h1:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, #3182ce, #2c5282);
            border-radius: 2px;
        }

        .back-to-dashboard-btn {
            position: absolute; /* Position the button absolutely */
            top: 20px; /* Distance from the top */
            right: 20px; /* Distance from the right */
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.25);
            z-index: 10; /* Ensure it stays above other elements */
        }

        .back-to-dashboard-btn:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(37, 99, 235, 0.3);
        }

        .back-to-dashboard-btn i {
            font-size: 16px;
        }
    </style>
=======
    <link rel="stylesheet" href="contests.css">
>>>>>>> ffdde78614fdb118a1b920e75bc9a02e43e211eb
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="https://picsum.photos/200/200" alt="Profile Picture">
            <span class="logo-text">CodeCase</span>
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
        <!-- Back to Dashboard Button -->
        <a href="dashboard.php" class="back-to-dashboard-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <h1>Upcoming Contests</h1>
        <p>Stay updated with all the upcoming competitive programming contests.</p>

        <div class="filter-container" style="padding: 20px 0;">
            <a href="?platform=all<?php echo $saved_filter ? '&saved=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'all' ? 'active' : ''; ?>">All Platforms</a>
            <a href="?platform=codechef<?php echo $saved_filter ? '&saved=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'codechef' ? 'active' : ''; ?>">CodeChef</a>
            <a href="?platform=codeforces<?php echo $saved_filter ? '&saved=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'codeforces' ? 'active' : ''; ?>">Codeforces</a>
            <a href="?platform=leetcode<?php echo $saved_filter ? '&saved=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'leetcode' ? 'active' : ''; ?>">LeetCode</a>
            <a href="?<?php echo $platform_filter !== 'all' ? 'platform=' . $platform_filter . '&' : ''; ?>saved=1" class="filter-btn <?php echo $saved_filter ? 'active' : ''; ?>">Saved Only</a>
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
                                <div class="detail-label ist-label">Start Time (IST)</div>
                                <div class="detail-value time-ist">
                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($contest['contest_start_date']); ?>
                                </div>
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

                            <?php if (isset($contest['url'])): ?>
                                <a href="<?php echo htmlspecialchars($contest['url']); ?>" target="_blank" class="contest-btn">
                                    <i class="fas fa-external-link-alt"></i> View Contest
                                </a>
                            <?php elseif (strtolower($contest['platform']) === 'codechef'): ?>
                                <a href="https://www.codechef.com/<?php echo htmlspecialchars($contest['contest_code']); ?>" target="_blank" class="contest-btn">
                                    <i class="fas fa-external-link-alt"></i> View Contest
                                </a>
                            <?php elseif (strtolower($contest['platform']) === 'codeforces'): ?>
                                <a href="https://codeforces.com/contest/<?php echo htmlspecialchars($contest['contest_code']); ?>" target="_blank" class="contest-btn">
                                    <i class="fas fa-external-link-alt"></i> View Contest
                                </a>
                            <?php elseif (strtolower($contest['platform']) === 'leetcode'): ?>
                                <a href="https://leetcode.com/contest/<?php echo htmlspecialchars($contest['contest_code']); ?>" target="_blank" class="contest-btn">
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