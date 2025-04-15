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

// We'll fetch user stats via API instead of direct SQL query
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - CodeKeep</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #1e2130;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .leaderboard-table th, .leaderboard-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #2d3748;
        }
        
        .leaderboard-table th {
            background-color: #2d3748;
            color: white;
            font-weight: 600;
        }
        
        .leaderboard-table tr:hover {
            background-color: #2a2e3f;
        }
        
        .rank {
            font-weight: bold;
            text-align: center;
            width: 60px;
        }
        
        .rank-1 {
            color: gold;
        }
        
        .rank-2 {
            color: silver;
        }
        
        .rank-3 {
            color: #cd7f32; /* bronze */
        }
        
        .total-solved {
            font-weight: bold;
            text-align: center;
            color: #4299e1;
        }
        
        .header-with-icon {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .header-with-icon i {
            font-size: 24px;
            color: #4299e1;
        }
        
        .platform-count {
            text-align: center;
            font-weight: 500;
        }
        
        .platform-header {
            text-align: center;
        }
        
        .platform-logo {
            font-size: 16px;
            margin-right: 5px;
        }
        
        .loading-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #4299e1;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loader-container {
            text-align: center;
            padding: 20px;
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
            <a href="./allusers.php" class="nav-item active">
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
            <a href="statistics.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Statistics</span>
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
    
    <div class="extra" style="width: 220px; background-color: #171923; padding: 20px 0; display: flex; flex-direction: column; border-right: 1px solid #2d3748;"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-with-icon">
            <i class="fas fa-trophy"></i>
            <div>
                <h1>Leaderboard</h1>
                <p>Users ranked by total problems solved across platforms</p>
            </div>
        </div>

        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th class="platform-header"><i class="platform-logo fab fa-node-js"></i>LeetCode</th>
                    <th class="platform-header"><i class="platform-logo fas fa-utensils"></i>CodeChef</th>
                    <th class="platform-header"><i class="platform-logo fas fa-code"></i>Codeforces</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="leaderboardBody">
                <tr>
                    <td colspan="7">
                        <div class="loader-container">
                            <div class="loading-spinner"></div>
                            <p>Loading leaderboard data...</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        function confirmLogout() {
            let logoutConfirm = confirm("Are you sure you want to logout?");
            if (logoutConfirm) {
                window.location.href = 'logout.php';
            }
        }
        
        // Fetch and populate leaderboard data
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/get_user_stats.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    populateLeaderboard(data.users);
                })
                .catch(error => {
                    document.getElementById('leaderboardBody').innerHTML = 
                        `<tr><td colspan="7" style="text-align: center; color: #f56565; padding: 20px;">
                            <i class="fas fa-exclamation-circle"></i> Error: ${error.message}
                         </td></tr>`;
                });
        });
        
        function populateLeaderboard(users) {
            const tbody = document.getElementById('leaderboardBody');
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No users found</td></tr>';
                return;
            }
            
            let tableContent = '';
            users.forEach((user, index) => {
                const rank = index + 1;
                const rankClass = (rank <= 3) ? `rank-${rank}` : '';
                
                tableContent += `
                    <tr>
                        <td class="rank ${rankClass}">${rank}</td>
                        <td>${escapeHtml(user.name)}</td>
                        <td>${escapeHtml(user.email)}</td>
                        <td class="platform-count">${user.leetcode_solved}</td>
                        <td class="platform-count">${user.codechef_solved}</td>
                        <td class="platform-count">${user.codeforces_solved}</td>
                        <td class="total-solved">${user.total_solved}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = tableContent;
        }
        
        // Helper function to escape HTML to prevent XSS
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>