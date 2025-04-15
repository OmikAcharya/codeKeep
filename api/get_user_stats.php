<?php
require '../config.php';

// Set header to indicate JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Function to get LeetCode stats using GraphQL API
function getLeetCodeStats($username) {
    if (empty($username)) return 0;
    
    $url = str_replace('%7Busername%7D', $username, 'https://leetcode-stats-api.herokuapp.com/%7Busername%7D');
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['totalSolved'])) {
        return (int)$data['totalSolved'];
    }
    
    return 0;
}

// Function to get CodeChef stats by scraping user profile
function getCodeChefStats($username) {
    if (empty($username)) return 0;
    
    $url = "https://www.codechef.com/users/{$username}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    $html = curl_exec($ch);
    curl_close($ch);
    // Basic regex to extract solved count (this is a simplified approach)
    preg_match('/<h3>Total Problems Solved: (\d+)<\/h3>/i', $html, $matches);

    if (isset($matches[1])) {
        return (int)$matches[1];
    }
    
    return 0;
}

// Function to get CodeForces stats using their API
function getCodeForcesStats($username) {
    if (empty($username)) return 0;
    
    $url = "https://codeforces.com/api/user.status?handle={$username}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] === 'OK') {
        $solved = [];
        foreach ($data['result'] as $submission) {
            if (isset($submission['verdict']) && $submission['verdict'] === 'OK') {
                $problemId = $submission['problem']['contestId'] . $submission['problem']['index'];
                $solved[$problemId] = true;
            }
        }
        return count($solved);
    }
    
    return 0;
}

// Query to get users with their platform usernames
$sql = "SELECT 
            u.name, 
            u.email,
            p.leetcode_id,
            p.codechef_id,
            p.codeforces_id
        FROM users u
        JOIN profile p ON u.email = p.uemail
        ORDER BY u.name";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
// Debug: Output all usernames to log
error_log("Querying user stats");
while ($debug_row = $result->fetch_assoc()) {
    error_log("User: " . $debug_row['name'] . 
              " | LeetCode: " . $debug_row['leetcode_id'] . 
              " | CodeChef: " . $debug_row['codechef_id'] . 
              " | CodeForces: " . $debug_row['codeforces_id']);
}
// Reset result pointer to start
$result->data_seek(0);
$users = [];
while ($row = $result->fetch_assoc()) {
    // Fetch real-time statistics from each platform
    $leetcode_solved = getLeetCodeStats($row['leetcode_id']);
    $codechef_solved = getCodeChefStats($row['codechef_id']);
    $codeforces_solved = getCodeForcesStats($row['codeforces_id']);
    $total_solved = $leetcode_solved + $codechef_solved + $codeforces_solved;
    
    $users[] = [
        'name' => $row['name'],
        'email' => $row['email'],
        'leetcode_solved' => $leetcode_solved,
        'codechef_solved' => $codechef_solved,
        'codeforces_solved' => $codeforces_solved,
        'total_solved' => $total_solved
    ];
}

// Sort users by total_solved in descending order
usort($users, function($a, $b) {
    return $b['total_solved'] - $a['total_solved'];
});

echo json_encode(['users' => $users]);
$conn->close();
?>
