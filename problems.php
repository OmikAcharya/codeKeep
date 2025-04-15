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

// Handle bookmark toggle action via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'toggle_bookmark') {
    if (!isset($_POST['problem_id']) || !isset($_SESSION['email'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }

    $problem_id = $_POST['problem_id'];
    $user_email = $_SESSION['email'];

    // Check if bookmark already exists
    $check_stmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_email = ? AND problem_id = ?");
    $check_stmt->bind_param("si", $user_email, $problem_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Bookmark exists, so remove it
        $delete_stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_email = ? AND problem_id = ?");
        $delete_stmt->bind_param("si", $user_email, $problem_id);
        $result = $delete_stmt->execute();
        $delete_stmt->close();

        echo json_encode(['success' => $result, 'bookmarked' => false]);
    } else {
        // Bookmark doesn't exist, so add it
        $insert_stmt = $conn->prepare("INSERT INTO bookmarks (user_email, problem_id) VALUES (?, ?)");
        $insert_stmt->bind_param("si", $user_email, $problem_id);
        $result = $insert_stmt->execute();
        $insert_stmt->close();

        echo json_encode(['success' => $result, 'bookmarked' => true]);
    }

    $check_stmt->close();
    exit;
}

// Get filter parameters
$platform_filter = $_GET['platform'] ?? 'all';
$difficulty_filter = $_GET['difficulty'] ?? 'all';
$bookmarked_filter = isset($_GET['bookmarked']) ? ($_GET['bookmarked'] === '1') : false;

// Build the SQL query based on filters
$sql = "SELECT p.id, p.title, p.difficulty, p.platform, p.problem_url, p.description, p.tags,
        CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END AS bookmarked
        FROM problems p
        LEFT JOIN bookmarks b ON p.id = b.problem_id AND b.user_email = ?";

$where_clauses = [];
$params = [$email];
$types = "s";

if ($platform_filter !== 'all') {
    $where_clauses[] = "p.platform = ?";
    $params[] = $platform_filter;
    $types .= "s";
}

if ($difficulty_filter !== 'all') {
    $where_clauses[] = "p.difficulty = ?";
    $params[] = $difficulty_filter;
    $types .= "s";
}

if ($bookmarked_filter) {
    $where_clauses[] = "b.id IS NOT NULL";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

// Bind parameters dynamically
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all problems
$problems = [];
while ($row = $result->fetch_assoc()) {
    // Convert tags string to array
    $row['tags'] = explode(',', $row['tags']);
    $row['url'] = $row['problem_url']; // Rename for compatibility with existing code
    $row['bookmarked'] = (bool)$row['bookmarked'];
    $problems[] = $row;
}

$stmt->close();

// Use the fetched problems as filtered problems
$filtered_problems = $problems;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problems - CodeKeep</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 14px;
            color: #a0aec0;
        }

        .filter-options {
            display: flex;
            gap: 5px;
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

        .problem-card {
            background-color: #1e2130;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
            transition: transform 0.3s;
        }

        .problem-card:hover {
            transform: translateY(-5px);
        }

        .easy {
            border-left-color: #48bb78;
        }

        .medium {
            border-left-color: #f6ad55;
        }

        .hard {
            border-left-color: #f56565;
        }

        .problem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .problem-title {
            font-size: 18px;
            font-weight: 600;
        }

        .problem-platform {
            font-size: 14px;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
        }

        .platform-leetcode {
            background-color: #f59e0b;
        }

        .platform-codechef {
            background-color: #3182ce;
        }

        .platform-codeforces {
            background-color: #805ad5;
        }

        .problem-description {
            margin: 15px 0;
            color: #a0aec0;
            font-size: 14px;
            line-height: 1.5;
        }

        .problem-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .tag {
            font-size: 12px;
            padding: 4px 8px;
            background-color: #2d3748;
            border-radius: 4px;
            color: #a0aec0;
        }

        .problem-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            align-items: center;
        }

        .difficulty {
            font-size: 14px;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
        }

        .difficulty-easy {
            background-color: #48bb78;
        }

        .difficulty-medium {
            background-color: #f6ad55;
        }

        .difficulty-hard {
            background-color: #f56565;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .problem-btn {
            padding: 8px 16px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .problem-btn:hover {
            background-color: #1d4ed8;
        }

        .bookmark-btn {
            background-color: transparent;
            border: 1px solid #2d3748;
            color: #a0aec0;
        }

        .bookmark-btn.active {
            background-color: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }

        .search-container {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            background-color: #1e2130;
            border: 1px solid #2d3748;
            border-radius: 4px;
            color: white;
            font-size: 16px;
        }

        .search-input::placeholder {
            color: #a0aec0;
        }

        @media (max-width: 768px) {
            .problem-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .problem-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
            <a href="contests.php" class="nav-item">
                <i class="fas fa-calendar"></i>
                <span>Contests</span>
            </a>
            <a href="problems.php" class="nav-item active">
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
                <div class="user-email"><?php echo htmlspecialchars($email); ?></div>
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
        <h1>Coding Problems</h1>
        <p>Browse, solve, and bookmark coding problems from various platforms.</p>

        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search problems..." id="problemSearch">
        </div>

        <div class="filter-container">
            <div class="filter-group">
                <div class="filter-label">Platform</div>
                <div class="filter-options">
                    <a href="?platform=all<?php echo $difficulty_filter !== 'all' ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?platform=leetcode<?php echo $difficulty_filter !== 'all' ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'leetcode' ? 'active' : ''; ?>">LeetCode</a>
                    <a href="?platform=codechef<?php echo $difficulty_filter !== 'all' ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'codechef' ? 'active' : ''; ?>">CodeChef</a>
                    <a href="?platform=codeforces<?php echo $difficulty_filter !== 'all' ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'codeforces' ? 'active' : ''; ?>">Codeforces</a>
                </div>
            </div>

            <div class="filter-group">
                <div class="filter-label">Difficulty</div>
                <div class="filter-options">
                    <a href="?difficulty=all<?php echo $platform_filter !== 'all' ? '&platform=' . $platform_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $difficulty_filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?difficulty=easy<?php echo $platform_filter !== 'all' ? '&platform=' . $platform_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $difficulty_filter === 'easy' ? 'active' : ''; ?>">Easy</a>
                    <a href="?difficulty=medium<?php echo $platform_filter !== 'all' ? '&platform=' . $platform_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $difficulty_filter === 'medium' ? 'active' : ''; ?>">Medium</a>
                    <a href="?difficulty=hard<?php echo $platform_filter !== 'all' ? '&platform=' . $platform_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $difficulty_filter === 'hard' ? 'active' : ''; ?>">Hard</a>
                </div>
            </div>

            <div class="filter-group">
                <div class="filter-label">Bookmarked</div>
                <div class="filter-options">
                    <a href="?<?php echo $platform_filter !== 'all' ? 'platform=' . $platform_filter . '&' : ''; ?><?php echo $difficulty_filter !== 'all' ? 'difficulty=' . $difficulty_filter . '&' : ''; ?>bookmarked=1" class="filter-btn <?php echo $bookmarked_filter ? 'active' : ''; ?>">Bookmarked Only</a>
                    <a href="?<?php echo $platform_filter !== 'all' ? 'platform=' . $platform_filter : ''; ?><?php echo $difficulty_filter !== 'all' ? '&difficulty=' . $difficulty_filter : ''; ?>" class="filter-btn <?php echo !$bookmarked_filter ? 'active' : ''; ?>">All Problems</a>
                </div>
            </div>
        </div>

        <div class="problems-container">
            <?php if (empty($filtered_problems)): ?>
                <div class="dashboard-card">
                    <p>No problems found matching your filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_problems as $problem): ?>
                    <div class="problem-card <?php echo strtolower($problem['difficulty']); ?>">
                        <div class="problem-header">
                            <div class="problem-title"><?php echo htmlspecialchars($problem['title']); ?></div>
                            <div class="problem-platform platform-<?php echo strtolower($problem['platform']); ?>">
                                <?php echo htmlspecialchars($problem['platform']); ?>
                            </div>
                        </div>

                        <div class="problem-description">
                            <?php echo htmlspecialchars($problem['description']); ?>
                        </div>

                        <div class="problem-tags">
                            <?php foreach ($problem['tags'] as $tag): ?>
                                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="problem-actions">
                            <div class="difficulty difficulty-<?php echo strtolower($problem['difficulty']); ?>">
                                <?php echo htmlspecialchars($problem['difficulty']); ?>
                            </div>

                            <div class="action-buttons">
                                <button class="problem-btn bookmark-btn <?php echo $problem['bookmarked'] ? 'active' : ''; ?>" onclick="toggleBookmark(<?php echo $problem['id']; ?>)">
                                    <i class="fas <?php echo $problem['bookmarked'] ? 'fa-bookmark' : 'fa-bookmark'; ?>"></i>
                                    <?php echo $problem['bookmarked'] ? 'Bookmarked' : 'Bookmark'; ?>
                                </button>

                                <a href="<?php echo htmlspecialchars($problem['url']); ?>" target="_blank" class="problem-btn">
                                    <i class="fas fa-external-link-alt"></i>
                                    Solve
                                </a>
                            </div>
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

        function toggleBookmark(problemId) {
            // Make an AJAX call to update the database
            const button = event.currentTarget;

            // Create form data
            const formData = new FormData();
            formData.append('action', 'toggle_bookmark');
            formData.append('problem_id', problemId);

            // Send AJAX request
            fetch('problems.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI based on server response
                    if (data.bookmarked) {
                        button.classList.add('active');
                        button.innerHTML = '<i class="fas fa-bookmark"></i> Bookmarked';
                    } else {
                        button.classList.remove('active');
                        button.innerHTML = '<i class="fas fa-bookmark"></i> Bookmark';
                    }
                } else {
                    console.error('Failed to toggle bookmark');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Simple client-side search functionality
        document.getElementById('problemSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const problemCards = document.querySelectorAll('.problem-card');

            problemCards.forEach(card => {
                const title = card.querySelector('.problem-title').textContent.toLowerCase();
                const description = card.querySelector('.problem-description').textContent.toLowerCase();
                const tags = Array.from(card.querySelectorAll('.tag')).map(tag => tag.textContent.toLowerCase());

                const matchesSearch = title.includes(searchTerm) ||
                                     description.includes(searchTerm) ||
                                     tags.some(tag => tag.includes(searchTerm));

                card.style.display = matchesSearch ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>
