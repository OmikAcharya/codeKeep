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

// Handle AJAX requests for notes CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Make sure user is logged in
    if (!isset($_SESSION['email'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    $user_email = $_SESSION['email'];

    // Create new note
    if (isset($_POST['action']) && $_POST['action'] === 'create_note') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? '';

        if (empty($title) || empty($content) || empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO notes (user_email, title, content, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user_email, $title, $content, $category);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Note created successfully',
                'note' => [
                    'id' => $new_id,
                    'title' => $title,
                    'content' => $content,
                    'category' => $category,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create note: ' . $stmt->error]);
        }

        $stmt->close();
        exit;
    }

    // Update existing note
    if (isset($_POST['action']) && $_POST['action'] === 'update_note') {
        $id = $_POST['id'] ?? 0;
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? '';

        if (empty($id) || empty($title) || empty($content) || empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        // Verify the note belongs to the user
        $check_stmt = $conn->prepare("SELECT id FROM notes WHERE id = ? AND user_email = ?");
        $check_stmt->bind_param("is", $id, $user_email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Note not found or not authorized']);
            $check_stmt->close();
            exit;
        }
        $check_stmt->close();

        $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, category = ? WHERE id = ? AND user_email = ?");
        $stmt->bind_param("sssis", $title, $content, $category, $id, $user_email);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Note updated successfully',
                'note' => [
                    'id' => $id,
                    'title' => $title,
                    'content' => $content,
                    'category' => $category,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update note: ' . $stmt->error]);
        }

        $stmt->close();
        exit;
    }

    // Delete note
    if (isset($_POST['action']) && $_POST['action'] === 'delete_note') {
        $id = $_POST['id'] ?? 0;

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Missing note ID']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_email = ?");
        $stmt->bind_param("is", $id, $user_email);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete note: ' . $stmt->error]);
        }

        $stmt->close();
        exit;
    }
}

// Get notes from database
$category_filter = $_GET['category'] ?? 'all';

// Build query based on filter
$sql = "SELECT id, title, content, category, created_at, updated_at FROM notes WHERE user_email = ?";
$params = [$email];
$types = "s";

if ($category_filter !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$sql .= " ORDER BY updated_at DESC";

// Execute query
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all notes
$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}
$stmt->close();

$filtered_notes = $notes;

// Get unique categories
$category_sql = "SELECT DISTINCT category FROM notes WHERE user_email = ? ORDER BY category";
$category_stmt = $conn->prepare($category_sql);
$category_stmt->bind_param("s", $email);
$category_stmt->execute();
$category_result = $category_stmt->get_result();

$categories = [];
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
$category_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes - CodeKeep</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="notes.css">
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
            <a href="contests.php" class="nav-item">
                <i class="fas fa-calendar"></i>
                <span>Contests</span>
            </a>
            <a href="problems.php" class="nav-item">
                <i class="fas fa-code"></i>
                <span>Problems</span>
            </a>
            <a href="notes.php" class="nav-item active">
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
        <a href="dashboard.php" class="back-to-dashboard-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1>Notes</h1>
        <p>Store and organize your programming notes, algorithms, and code snippets.</p>
        <div class="notes-container" style="padding: 20px 0;">
            <!-- Notes Sidebar -->
            <div class="notes-sidebar">
                <div class="notes-header">
                    <div class="notes-title">All Notes</div>
                    <button class="new-note-btn" onclick="createNewNote()">
                        <i class="fas fa-plus"></i>
                        New
                    </button>
                </div>

                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search notes..." id="noteSearch">
                </div>

                <div class="category-filter">
                    <div class="category-label">Categories</div>
                    <div class="category-list">
                        <a href="?category=all" class="category-item <?php echo $category_filter === 'all' ? 'active' : ''; ?>">
                            <span>All Categories</span>
                            <span class="category-count"><?php echo count($notes); ?></span>
                        </a>

                        <?php foreach ($categories as $category): ?>
                            <?php
                            $count = count(array_filter($notes, function ($note) use ($category) {
                                return $note['category'] === $category;
                            }));
                            ?>
                            <a href="?category=<?php echo urlencode(strtolower($category)); ?>" class="category-item <?php echo strtolower($category_filter) === strtolower($category) ? 'active' : ''; ?>">
                                <span><?php echo htmlspecialchars($category); ?></span>
                                <span class="category-count"><?php echo $count; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="notes-list">
                    <?php if (empty($filtered_notes)): ?>
                        <div class="empty-note">No notes found.</div>
                    <?php else: ?>
                        <?php foreach ($filtered_notes as $index => $note): ?>
                            <div class="note-item <?php echo $index === 0 ? 'active' : ''; ?>" data-id="<?php echo $note['id']; ?>" onclick="viewNote(<?php echo $note['id']; ?>)">
                                <div class="note-item-title"><?php echo htmlspecialchars($note['title']); ?></div>
                                <div class="note-item-preview"><?php echo htmlspecialchars(substr($note['content'], 0, 50) . (strlen($note['content']) > 50 ? '...' : '')); ?></div>
                                <div class="note-item-date">Updated: <?php echo htmlspecialchars(date('M d, Y', strtotime($note['updated_at']))); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Note Content -->
            <div class="note-content" id="noteContent">
                <?php if (empty($filtered_notes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-sticky-note"></i>
                        <h3>No notes found</h3>
                        <p>Create a new note to get started.</p>
                        <button class="new-note-btn" onclick="createNewNote()">
                            <i class="fas fa-plus"></i>
                            Create New Note
                        </button>
                    </div>
                <?php else: ?>
                    <div class="note-content-header">
                        <div class="note-content-title"><?php echo htmlspecialchars($filtered_notes[0]['title']); ?></div>
                        <div class="note-content-actions">
                            <button class="note-action-btn" onclick="editNote(<?php echo $filtered_notes[0]['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="note-action-btn" onclick="deleteNote(<?php echo $filtered_notes[0]['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="note-content-body">
                        <?php
                        $content = $filtered_notes[0]['content'];
                        // Convert markdown-style code blocks to HTML
                        $content = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $content);
                        echo nl2br(htmlspecialchars_decode($content));
                        ?>
                    </div>

                    <div class="note-content-footer">
                        <div>Category: <?php echo htmlspecialchars($filtered_notes[0]['category']); ?></div>
                        <div>Last updated: <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($filtered_notes[0]['updated_at']))); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Note Editor -->
            <div class="note-editor" id="noteEditor">
                <div class="note-editor-header">
                    <input type="text" class="note-editor-title" id="editTitle" placeholder="Note Title">

                    <!-- Category Dropdown -->
                    <select id="editCategoryDropdown" class="note-editor-category" onchange="handleCategoryChange()">
                        <option value="">Select a category</option>
                        <option value="algorithms">Algorithms</option>
                        <option value="code-snippets">Code Snippets</option>
                        <option value="data-structures">Data Structures</option>
                        <option value="projects">Projects</option>
                        <option value="other">Other</option>
                    </select>

                    <!-- Category Input (hidden by default) -->
                    <input type="text" class="note-editor-category" id="editCategoryInput" placeholder="Enter custom category" style="display: none;">
                </div>

                <div class="note-editor-body">
                    <textarea class="note-editor-content" id="editContent" placeholder="Write your note here..."></textarea>
                </div>

                <div class="note-editor-actions">
                    <button class="editor-btn cancel-btn" onclick="cancelEdit()">Cancel</button>
                    <button class="editor-btn save-btn" onclick="saveNote()">Save Note</button>
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

        // Note viewing and editing functionality
        let currentNoteId = <?php echo !empty($filtered_notes) ? $filtered_notes[0]['id'] : 'null'; ?>;
        let isNewNote = false;

        function viewNote(noteId) {
            // Find the note in our data
            const notes = <?php echo json_encode($notes); ?>;
            const note = notes.find(n => parseInt(n.id) === parseInt(noteId));

            if (!note) return;

            currentNoteId = noteId;

            // Update UI
            document.querySelectorAll('.note-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`.note-item[data-id="${noteId}"]`).classList.add('active');

            // Update content
            const noteContent = document.getElementById('noteContent');
            let content = note.content;
            // Convert markdown-style code blocks to HTML
            content = content.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');

            noteContent.innerHTML = `
                <div class="note-content-header">
                    <div class="note-content-title">${note.title}</div>
                    <div class="note-content-actions">
                        <button class="note-action-btn" onclick="editNote(${note.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="note-action-btn" onclick="deleteNote(${note.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="note-content-body">
                    ${content.replace(/\n/g, '<br>')}
                </div>

                <div class="note-content-footer">
                    <div>Category: ${note.category}</div>
                    <div>Last updated: ${new Date(note.updated_at).toLocaleString()}</div>
                </div>
            `;

            // Show content, hide editor
            noteContent.style.display = 'flex';
            document.getElementById('noteEditor').style.display = 'none';
        }

        function editNote(noteId) {
            // Find the note in our data
            const notes = <?php echo json_encode($notes); ?>;
            const note = notes.find(n => parseInt(n.id) === parseInt(noteId));

            if (!note) return;

            currentNoteId = noteId;
            isNewNote = false;

            // Populate editor
            document.getElementById('editTitle').value = note.title;
            document.getElementById('editCategory').value = note.category;
            document.getElementById('editContent').value = note.content;

            // Show editor, hide content
            document.getElementById('noteContent').style.display = 'none';
            document.getElementById('noteEditor').style.display = 'flex';
        }

        function createNewNote() {
            currentNoteId = null;
            isNewNote = true;

            // Clear editor fields
            document.getElementById('editTitle').value = '';
            document.getElementById('editCategoryInput').value = '';
            document.getElementById('editContent').value = '';

            // Show the dropdown and hide the input field
            document.getElementById('editCategoryDropdown').style.display = 'block';
            document.getElementById('editCategoryInput').style.display = 'none';

            // Show editor, hide content
            document.getElementById('noteContent').style.display = 'none';
            document.getElementById('noteEditor').style.display = 'flex';
        }

        function saveNote() {
            // Get values from editor
            const title = document.getElementById('editTitle').value.trim();
            const categoryDropdown = document.getElementById('editCategoryDropdown');
            const categoryInput = document.getElementById('editCategoryInput');
            const content = document.getElementById('editContent').value.trim();

            // Determine the category value (use input if "Other" is selected, otherwise use dropdown)
            const category = categoryDropdown.value === 'other' ? categoryInput.value.trim() : categoryDropdown.value.trim();

            // Validate
            if (!title || !category || !content) {
                alert('Please fill in all fields');
                return;
            }

            // Create form data
            const formData = new FormData();

            if (isNewNote) {
                formData.append('action', 'create_note');
            } else {
                formData.append('action', 'update_note');
                formData.append('id', currentNoteId);
            }

            formData.append('title', title);
            formData.append('category', category);
            formData.append('content', content);

            // Send AJAX request
            fetch('notes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Reload the page to show updated notes
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the note.');
                });
        }

        function deleteNote(noteId) {
            const confirmDelete = confirm('Are you sure you want to delete this note?');

            if (confirmDelete) {
                // Create form data
                const formData = new FormData();
                formData.append('action', 'delete_note');
                formData.append('id', noteId);

                // Send AJAX request
                fetch('notes.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            // Reload the page to show updated notes
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the note.');
                    });
            }
        }

        function cancelEdit() {
            // If we were editing an existing note, go back to viewing it
            if (!isNewNote && currentNoteId) {
                viewNote(currentNoteId);
            } else {
                // Otherwise, show the first note or empty state
                const notes = <?php echo json_encode($notes); ?>;
                if (notes.length > 0) {
                    viewNote(notes[0].id);
                } else {
                    document.getElementById('noteEditor').style.display = 'none';
                    document.getElementById('noteContent').style.display = 'flex';
                    document.getElementById('noteContent').innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-sticky-note"></i>
                            <h3>No notes found</h3>
                            <p>Create a new note to get started.</p>
                            <button class="new-note-btn" onclick="createNewNote()">
                                <i class="fas fa-plus"></i>
                                Create New Note
                            </button>
                        </div>
                    `;
                }
            }
        }

        function handleCategoryChange() {
            const categoryDropdown = document.getElementById('editCategoryDropdown');
            const categoryInput = document.getElementById('editCategoryInput');

            if (categoryDropdown.value === 'other') {
                categoryInput.style.display = 'block'; // Show input field
                categoryInput.value = ''; // Clear any previous value
            } else {
                categoryInput.style.display = 'none'; // Hide input field
                categoryInput.value = categoryDropdown.value; // Set input value to selected category
            }
        }

        // Search functionality
        document.getElementById('noteSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const noteItems = document.querySelectorAll('.note-item');

            noteItems.forEach(item => {
                const title = item.querySelector('.note-item-title').textContent.toLowerCase();
                const preview = item.querySelector('.note-item-preview').textContent.toLowerCase();

                if (title.includes(searchTerm) || preview.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>