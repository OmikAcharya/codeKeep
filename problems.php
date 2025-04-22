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

// Handle AJAX actions
if (isset($_POST['action'])) {
    if (!isset($_SESSION['email'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    $user_email = $_SESSION['email'];

    // Handle bookmark toggle action
    if ($_POST['action'] === 'toggle_bookmark') {
        if (!isset($_POST['problem_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing problem ID']);
            exit;
        }

        $problem_id = $_POST['problem_id'];

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

    // Handle add new problem action
    if ($_POST['action'] === 'add_problem') {
        // Validate required fields
        $required_fields = ['title', 'difficulty', 'platform', 'problem_url', 'description'];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]);
            exit;
        }

        // Get form data
        $title = $_POST['title'];
        $difficulty = $_POST['difficulty'];
        $platform = $_POST['platform'] === 'other' ? 'Custom' : $_POST['platform'];
        $problem_url = $_POST['problem_url'];
        $description = $_POST['description'];
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';

        // Insert the problem
        $stmt = $conn->prepare("INSERT INTO problems (title, difficulty, platform, problem_url, description, tags) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $title, $difficulty, $platform, $problem_url, $description, $tags);

        if ($stmt->execute()) {
            $new_problem_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Problem added successfully',
                'problem_id' => $new_problem_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add problem: ' . $stmt->error]);
        }

        $stmt->close();
        exit;
    }

    // Handle mark as solved action
    if ($_POST['action'] === 'mark_solved') {
        if (!isset($_POST['problem_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing problem ID']);
            exit;
        }

        $problem_id = $_POST['problem_id'];
        $solution_code = $_POST['solution_code'] ?? '';
        $language = $_POST['language'] ?? '';
        $time_taken = $_POST['time_taken'] ?? null;

        // Check if problem is already marked as solved
        $check_stmt = $conn->prepare("SELECT id FROM solved_problems WHERE user_email = ? AND problem_id = ?");
        $check_stmt->bind_param("si", $user_email, $problem_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Problem already marked as solved']);
            $check_stmt->close();
            exit;
        }
        $check_stmt->close();

        // Get problem details for statistics update
        $problem_stmt = $conn->prepare("SELECT difficulty, platform FROM problems WHERE id = ?");
        $problem_stmt->bind_param("i", $problem_id);
        $problem_stmt->execute();
        $problem_stmt->bind_result($difficulty, $platform);
        $problem_stmt->fetch();
        $problem_stmt->close();

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into solved_problems
            $insert_stmt = $conn->prepare("INSERT INTO solved_problems (user_email, problem_id, solution_code, language, time_taken) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sissi", $user_email, $problem_id, $solution_code, $language, $time_taken);
            $insert_stmt->execute();
            $insert_stmt->close();

            // Update user statistics
            $today = date('Y-m-d');

            // Check if user has statistics record
            $stats_check = $conn->prepare("SELECT id, last_solved_date FROM user_statistics WHERE user_email = ?");
            $stats_check->bind_param("s", $user_email);
            $stats_check->execute();
            $stats_check->store_result();

            if ($stats_check->num_rows === 0) {
                // Create new statistics record
                $stats_insert = $conn->prepare("INSERT INTO user_statistics (user_email, total_solved, last_solved_date, streak_days) VALUES (?, 1, ?, 1)");
                $stats_insert->bind_param("ss", $user_email, $today);
                $stats_insert->execute();
                $stats_insert->close();
            } else {
                // Update existing statistics
                $stats_check->bind_result($stats_id, $last_solved_date);
                $stats_check->fetch();

                // Calculate streak
                $streak_update = '';
                if ($last_solved_date) {
                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                    if ($last_solved_date == $today) {
                        // Already solved today, no streak update needed
                        $streak_update = '';
                    } elseif ($last_solved_date == $yesterday) {
                        // Solved yesterday, increment streak
                        $streak_update = ', streak_days = streak_days + 1';
                    } else {
                        // Streak broken, reset to 1
                        $streak_update = ', streak_days = 1';
                    }
                } else {
                    // First time solving, set streak to 1
                    $streak_update = ', streak_days = 1';
                }

                // Update statistics based on difficulty and platform
                $difficulty_col = strtolower($difficulty) . '_solved';
                $platform_col = strtolower($platform) . '_solved';

                $stats_update = $conn->prepare("UPDATE user_statistics SET
                    total_solved = total_solved + 1,
                    $difficulty_col = $difficulty_col + 1,
                    $platform_col = $platform_col + 1,
                    last_solved_date = ? $streak_update
                    WHERE user_email = ?");
                $stats_update->bind_param("ss", $today, $user_email);
                $stats_update->execute();
                $stats_update->close();
            }
            $stats_check->close();

            // Commit transaction
            $conn->commit();

            echo json_encode(['success' => true, 'message' => 'Problem marked as solved']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }

        exit;
    }
}

// Get filter parameters
$platform_filter = $_GET['platform'] ?? 'all';
$difficulty_filter = $_GET['difficulty'] ?? 'all';
$bookmarked_filter = isset($_GET['bookmarked']) ? ($_GET['bookmarked'] === '1') : false;

// Build the SQL query based on filters
$sql = "SELECT p.id, p.title, p.difficulty, p.platform, p.problem_url, p.description, p.tags,
        CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END AS bookmarked,
        CASE WHEN sp.id IS NOT NULL THEN 1 ELSE 0 END AS solved
        FROM problems p
        LEFT JOIN bookmarks b ON p.id = b.problem_id AND b.user_email = ?
        LEFT JOIN solved_problems sp ON p.id = sp.problem_id AND sp.user_email = ?";

$where_clauses = [];
$params = [$email, $email];
$types = "ss";

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

// Add solved filter if specified
$solved_filter = isset($_GET['solved']) ? ($_GET['solved'] === '1') : false;
if ($solved_filter) {
    $where_clauses[] = "sp.id IS NOT NULL";
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
    <link rel="stylesheet" href="problems.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h1><span class="logo-text">&lt;Code<span style="color: #1a4eaf">Case&gt;</span></span></h1>
        </div>

        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="allusers.php" class="nav-item">
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
            <a href="cp_helper.php" class="nav-item">
                <i class="fas fa-robot"></i>
                <span>CP Helper</span>
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
        <div class="header-with-button">
            <div>
                <a href="dashboard.php" class="back-to-dashboard-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h1>Coding Problems</h1>
                <p>Browse, solve, and bookmark coding problems from various platforms.</p>
            </div>
        </div>

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
                    <a href="?platform=custom<?php echo $difficulty_filter !== 'all' ? '&difficulty=' . $difficulty_filter : ''; ?><?php echo $bookmarked_filter ? '&bookmarked=1' : ''; ?>" class="filter-btn <?php echo $platform_filter === 'custom' ? 'active' : ''; ?>">Custom</a>
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

            <div class="filter-group">
                <div class="filter-label">Solved Status</div>
                <div class="filter-options">
                    <a href="?<?php echo $platform_filter !== 'all' ? 'platform=' . $platform_filter . '&' : ''; ?><?php echo $difficulty_filter !== 'all' ? 'difficulty=' . $difficulty_filter . '&' : ''; ?>solved=1" class="filter-btn <?php echo $solved_filter ? 'active' : ''; ?>">Solved Only</a>
                    <a href="?<?php echo $platform_filter !== 'all' ? 'platform=' . $platform_filter : ''; ?><?php echo $difficulty_filter !== 'all' ? '&difficulty=' . $difficulty_filter : ''; ?>" class="filter-btn <?php echo !$solved_filter ? 'active' : ''; ?>">All Problems</a>
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
                            <div class="problem-title">
                                <?php echo htmlspecialchars($problem['title']); ?>
                                <?php if (strtolower($problem['platform']) === 'custom'): ?>
                                    <span class="custom-badge"><i class="fas fa-user-edit"></i> User Added</span>
                                <?php endif; ?>
                            </div>
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
                                <?php if ($problem['solved']): ?>
                                    <span class="solved-badge"><i class="fas fa-check-circle"></i> Solved</span>
                                <?php endif; ?>
                            </div>

                            <div class="action-buttons">
                                <button class="problem-btn bookmark-btn <?php echo $problem['bookmarked'] ? 'active' : ''; ?>" onclick="toggleBookmark(<?php echo $problem['id']; ?>)">
                                    <i class="fas <?php echo $problem['bookmarked'] ? 'fa-bookmark' : 'fa-bookmark'; ?>"></i>
                                    <?php echo $problem['bookmarked'] ? 'Bookmarked' : 'Bookmark'; ?>
                                </button>

                                <?php if (!$problem['solved']): ?>
                                    <button class="problem-btn solved-btn" onclick="markAsSolved(<?php echo $problem['id']; ?>)">
                                        <i class="fas fa-check-circle"></i>
                                        Mark as Solved
                                    </button>
                                <?php endif; ?>

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
            const button = event.currentTarget;
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

        function markAsSolved(problemId) {
            // Show solution form in a modal
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Mark Problem as Solved</h3>
                        <button class="close-btn" onclick="closeModal(this.parentNode.parentNode.parentNode)">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="solutionForm">
                            <div class="form-group">
                                <label for="language">Programming Language</label>
                                <select id="language" name="language" class="form-control">
                                    <option value="C++">C++</option>
                                    <option value="Java">Java</option>
                                    <option value="Python">Python</option>
                                    <option value="JavaScript">JavaScript</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="timeTaken">Time Taken (minutes)</label>
                                <input type="number" id="timeTaken" name="timeTaken" class="form-control" min="1">
                            </div>
                            <div class="form-group">
                                <label for="solutionCode">Solution Code (optional)</label>
                                <textarea id="solutionCode" name="solutionCode" class="form-control" rows="10"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-btn" onclick="closeModal(this.parentNode.parentNode.parentNode.parentNode.parentNode)">Cancel</button>
                                <button type="button" class="submit-btn" onclick="submitSolution(${problemId})">Mark as Solved</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Add modal styles if not already added
            if (!document.getElementById('modalStyles')) {
                const style = document.createElement('style');
                style.id = 'modalStyles';
                style.textContent = `
                    .modal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.8);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 1000;
                        backdrop-filter: blur(5px);
                        animation: fadeIn 0.3s ease-out;
                    }

                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }

                    @keyframes slideIn {
                        from { transform: translateY(-20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }

                    .modal-content {
                        background-color: #1e2130;
                        border-radius: 12px;
                        width: 90%;
                        max-width: 650px;
                        max-height: 90vh;
                        overflow-y: auto;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
                        animation: slideIn 0.3s ease-out;
                        border: 1px solid #3f4865;
                    }

                    .modal-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 20px 25px;
                        border-bottom: 1px solid #3f4865;
                        background-color: #171923;
                    }

                    .modal-header h3 {
                        margin: 0;
                        font-size: 20px;
                        color: #e2e8f0;
                        font-weight: 600;
                    }

                    .close-btn {
                        background: none;
                        border: none;
                        font-size: 28px;
                        color: #a0aec0;
                        cursor: pointer;
                        transition: color 0.2s;
                        line-height: 1;
                        padding: 0;
                        margin: 0;
                    }

                    .close-btn:hover {
                        color: #e2e8f0;
                    }

                    .modal-body {
                        padding: 25px;
                    }

                    .form-group {
                        margin-bottom: 24px;
                    }

                    .form-group label {
                        display: block;
                        margin-bottom: 10px;
                        color: #e2e8f0;
                        font-weight: 500;
                        font-size: 15px;
                    }

                    .form-control {
                        width: 100%;
                        padding: 12px 15px;
                        background-color: #171923;
                        border: 1px solid #3f4865;
                        border-radius: 8px;
                        color: white;
                        font-size: 15px;
                        transition: border-color 0.3s, box-shadow 0.3s;
                    }

                    .form-control:focus {
                        border-color: #4299e1;
                        outline: none;
                        box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.3);
                    }

                    textarea.form-control {
                        resize: vertical;
                        min-height: 120px;
                        line-height: 1.5;
                        font-family: inherit;
                    }

                    .form-actions {
                        display: flex;
                        justify-content: flex-end;
                        gap: 15px;
                        margin-top: 30px;
                    }

                    .cancel-btn, .submit-btn {
                        padding: 12px 24px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 15px;
                        font-weight: 600;
                        transition: all 0.3s;
                    }

                    .cancel-btn {
                        background-color: transparent;
                        border: 1px solid #3f4865;
                        color: #e2e8f0;
                    }

                    .cancel-btn:hover {
                        background-color: #2d3748;
                    }

                    .submit-btn {
                        background-color: #4299e1;
                        color: white;
                        border: none;
                        box-shadow: 0 4px 6px rgba(66, 153, 225, 0.2);
                    }

                    .submit-btn:hover {
                        background-color: #3182ce;
                        transform: translateY(-2px);
                        box-shadow: 0 6px 8px rgba(66, 153, 225, 0.3);
                    }

                    .submit-btn:active {
                        transform: translateY(0);
                    }
                `;
                document.head.appendChild(style);
            }
        }

        function submitSolution(problemId) {
            const language = document.getElementById('language').value;
            const timeTaken = document.getElementById('timeTaken').value;
            const solutionCode = document.getElementById('solutionCode').value;

            if (!language || !timeTaken) {
                alert('Please fill in all required fields');
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('action', 'mark_solved');
            formData.append('problem_id', problemId);
            formData.append('language', language);
            formData.append('time_taken', timeTaken);
            formData.append('solution_code', solutionCode);

            // Send AJAX request
            fetch('problems.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Close the modal
                    const modal = document.querySelector('.modal');
                    document.body.removeChild(modal);
                    // Reload the page to update the UI
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the problem as solved.');
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
