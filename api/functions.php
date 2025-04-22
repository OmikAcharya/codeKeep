<?php

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

?>