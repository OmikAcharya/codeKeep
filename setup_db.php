<?php
require 'config.php';

// Create problems table
$sql_problems = "CREATE TABLE IF NOT EXISTS problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    difficulty ENUM('Easy', 'Medium', 'Hard') NOT NULL,
    platform VARCHAR(50) NOT NULL,
    problem_url VARCHAR(255) NOT NULL,
    description TEXT,
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create bookmarks table
$sql_bookmarks = "CREATE TABLE IF NOT EXISTS bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    problem_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE,
    UNIQUE KEY user_problem (user_email, problem_id)
)";

// Create notes table
$sql_notes = "CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Create saved_contests table
$sql_saved_contests = "CREATE TABLE IF NOT EXISTS saved_contests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    contest_name VARCHAR(255) NOT NULL,
    contest_code VARCHAR(100) NOT NULL,
    contest_start_date DATETIME NOT NULL,
    contest_end_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_contest (user_email, platform, contest_code)
)";

// Create solved_problems table
$sql_solved_problems = "CREATE TABLE IF NOT EXISTS solved_problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    problem_id INT NOT NULL,
    solution_code TEXT,
    language VARCHAR(50),
    time_taken INT,  -- Time taken in minutes
    solved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE,
    UNIQUE KEY user_problem (user_email, problem_id)
)";

// Create user_statistics table
$sql_user_statistics = "CREATE TABLE IF NOT EXISTS user_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    total_solved INT DEFAULT 0,
    easy_solved INT DEFAULT 0,
    medium_solved INT DEFAULT 0,
    hard_solved INT DEFAULT 0,
    leetcode_solved INT DEFAULT 0,
    codechef_solved INT DEFAULT 0,
    codeforces_solved INT DEFAULT 0,
    streak_days INT DEFAULT 0,
    last_solved_date DATE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_stats (user_email)
)";

// Execute the SQL statements
if ($conn->query($sql_problems) === TRUE) {
    echo "Table 'problems' created successfully<br>";
} else {
    echo "Error creating table 'problems': " . $conn->error . "<br>";
}

if ($conn->query($sql_bookmarks) === TRUE) {
    echo "Table 'bookmarks' created successfully<br>";
} else {
    echo "Error creating table 'bookmarks': " . $conn->error . "<br>";
}

if ($conn->query($sql_notes) === TRUE) {
    echo "Table 'notes' created successfully<br>";
} else {
    echo "Error creating table 'notes': " . $conn->error . "<br>";
}

if ($conn->query($sql_saved_contests) === TRUE) {
    echo "Table 'saved_contests' created successfully<br>";
} else {
    echo "Error creating table 'saved_contests': " . $conn->error . "<br>";
}

if ($conn->query($sql_solved_problems) === TRUE) {
    echo "Table 'solved_problems' created successfully<br>";
} else {
    echo "Error creating table 'solved_problems': " . $conn->error . "<br>";
}

if ($conn->query($sql_user_statistics) === TRUE) {
    echo "Table 'user_statistics' created successfully<br>";
} else {
    echo "Error creating table 'user_statistics': " . $conn->error . "<br>";
}

// Insert some sample problems
$sample_problems = [
    [
        'title' => 'Two Sum',
        'difficulty' => 'Easy',
        'platform' => 'LeetCode',
        'problem_url' => 'https://leetcode.com/problems/two-sum/',
        'description' => 'Given an array of integers nums and an integer target, return indices of the two numbers such that they add up to target.',
        'tags' => 'Array,Hash Table'
    ],
    [
        'title' => 'Add Two Numbers',
        'difficulty' => 'Medium',
        'platform' => 'LeetCode',
        'problem_url' => 'https://leetcode.com/problems/add-two-numbers/',
        'description' => 'You are given two non-empty linked lists representing two non-negative integers. The digits are stored in reverse order, and each of their nodes contains a single digit.',
        'tags' => 'Linked List,Math'
    ],
    [
        'title' => 'Chef and Strings',
        'difficulty' => 'Easy',
        'platform' => 'CodeChef',
        'problem_url' => 'https://www.codechef.com/problems/CHEFSTR1',
        'description' => 'Chef has a string S consisting of lowercase English alphabets. Chef defined a function F such that F(i) denotes the frequency of the character S[i] in S.',
        'tags' => 'Strings,Implementation'
    ],
    [
        'title' => 'Watermelon',
        'difficulty' => 'Easy',
        'platform' => 'Codeforces',
        'problem_url' => 'https://codeforces.com/problemset/problem/4/A',
        'description' => 'One hot summer day Pete and his friend Billy decided to buy a watermelon. They chose the biggest and the ripest one, in their opinion.',
        'tags' => 'Math,Brute Force'
    ],
    [
        'title' => 'Theatre Square',
        'difficulty' => 'Medium',
        'platform' => 'Codeforces',
        'problem_url' => 'https://codeforces.com/problemset/problem/1/A',
        'description' => 'Theatre Square in the capital city of Berland has a rectangular shape with the size n × m meters. On the occasion of the city\'s anniversary, a decision was taken to pave the Square with square granite flagstones.',
        'tags' => 'Math'
    ]
];

// Check if problems already exist
$check_problems = $conn->query("SELECT COUNT(*) as count FROM problems");
$problem_count = $check_problems->fetch_assoc()['count'];

if ($problem_count == 0) {
    // Prepare statement for inserting problems
    $stmt = $conn->prepare("INSERT INTO problems (title, difficulty, platform, problem_url, description, tags) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $difficulty, $platform, $problem_url, $description, $tags);

    // Insert each problem
    foreach ($sample_problems as $problem) {
        $title = $problem['title'];
        $difficulty = $problem['difficulty'];
        $platform = $problem['platform'];
        $problem_url = $problem['problem_url'];
        $description = $problem['description'];
        $tags = $problem['tags'];

        $stmt->execute();
    }

    echo "Sample problems inserted successfully<br>";
    $stmt->close();
} else {
    echo "Problems already exist in the database<br>";
}

// Insert sample notes if the user exists
if (isset($_SESSION['email'])) {
    $user_email = $_SESSION['email'];

    // Check if notes already exist for this user
    $check_notes = $conn->prepare("SELECT COUNT(*) as count FROM notes WHERE user_email = ?");
    $check_notes->bind_param("s", $user_email);
    $check_notes->execute();
    $result = $check_notes->get_result();
    $note_count = $result->fetch_assoc()['count'];

    if ($note_count == 0) {
        // Sample notes
        $sample_notes = [
            [
                'title' => 'Dynamic Programming Patterns',
                'content' => 'Common DP patterns to remember:
1. Fibonacci sequence pattern
2. 0/1 Knapsack pattern
3. Unbounded Knapsack pattern
4. Longest Common Subsequence pattern
5. Palindromic Subsequence pattern',
                'category' => 'Algorithms'
            ],
            [
                'title' => 'Graph Traversal Techniques',
                'content' => 'BFS: Use a queue, good for shortest path in unweighted graphs.
DFS: Use a stack or recursion, good for exploring all paths.
Dijkstra: Use a priority queue, finds shortest path in weighted graphs without negative edges.
Bellman-Ford: Can handle negative edges, checks for negative cycles.',
                'category' => 'Algorithms'
            ],
            [
                'title' => 'C++ STL Containers Cheat Sheet',
                'content' => 'vector: Dynamic array, fast random access
list: Doubly linked list, fast insertions/deletions
deque: Double-ended queue, fast at both ends
set: Ordered unique elements
map: Ordered key-value pairs
unordered_set: Hash table, faster lookups
unordered_map: Hash table with key-value pairs',
                'category' => 'Programming'
            ]
        ];

        // Prepare statement for inserting notes
        $stmt = $conn->prepare("INSERT INTO notes (user_email, title, content, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user_email, $title, $content, $category);

        // Insert each note
        foreach ($sample_notes as $note) {
            $title = $note['title'];
            $content = $note['content'];
            $category = $note['category'];

            $stmt->execute();
        }

        echo "Sample notes inserted successfully<br>";
        $stmt->close();
    } else {
        echo "Notes already exist for this user<br>";
    }
} else {
    echo "User not logged in, skipping sample notes insertion<br>";
}

echo "<br>Database setup completed. <a href='dashboard.php'>Return to Dashboard</a>";
?>
