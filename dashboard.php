<?php
require 'config.php';

if (!isset($_SESSION['name'])) {
    echo "Session Error";
    header("Location: login.php");
    exit;
}

$name = $_SESSION['name'];

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
            <img src="https://picsum.photos/200/200" />
            <div class="items">
                <ul>
                    <li><?php echo htmlspecialchars($name); ?></li>
                    <li>CodeChef</li>
                    <li>Leetcode</li>
                    <li>CodeForces</li>
                    <li><a href="#" onclick="confirmLogout()">Logout</a></li>
                </ul>
            </div>
        </div>
        <div class="container">
            <div class="helloCard">
                <h1 style="font-size:84px;">Welcome back</h1>
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
                    <h1>Upcoming Contests</h1>
                    <ul style="paddin>
                        <?php foreach ($merged_future_contests as $contest) { ?>
                            <li style="list-style: none">
                                <strong><?php echo htmlspecialchars($contest['contest_name']); ?></strong> <br>
                                Code: <?php echo htmlspecialchars($contest['contest_code']); ?> <br>
                                Start: <?php echo htmlspecialchars($contest['contest_start_date']); ?> <br>
                                End: <?php echo htmlspecialchars($contest['contest_end_date']); ?>
                            </li>
                            <hr>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
