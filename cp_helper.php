<?php
require 'config.php';

if (!isset($_SESSION['name']) && !isset($_COOKIE['name'])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['name']) && isset($_SESSION['email'])) {
    $name = $_SESSION['name'];
    $email = $_SESSION['email'];
}

// Load environment variables from .env file
$env_path = __DIR__ . '/.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Gemini API configuration
$gemini_api_key = getenv('GEMINI_API_KEY') ?: ''; // Get API key from environment variable

// Handle chat API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = $_POST['message'] ?? '';
    
    // Check if we should use the Gemini API
    if (!empty($gemini_api_key)) {
        // Prepare data for Gemini API
        $prompt = "You are a helpful competitive programming assistant. " .
                  "Provide concise, accurate information about algorithms, data structures, and coding techniques. " .
                  "Also whenever a user feeds you a problem to solve always make sure you try and give hints instead of the actual answer! " .
                  "Always answer in concise and understand that your text is directly copy pasted so never use markdown!".
                  "Again always keep your answer concise!!!!!!!".
                  "User query: " . $message;
        
        // Call the Gemini API
        $response = callGeminiAPI($gemini_api_key, $prompt);
    } else {
        // Fallback to local responses when API key is not set
        $responses = [
            "How do I improve my problem-solving skills?" => "Practice regularly with a variety of problems. Start with easier problems and gradually increase difficulty. Analyze solutions after solving them and learn different approaches to the same problem.",
            "What are some good resources for CP?" => "LeetCode, Codeforces, CodeChef, and HackerRank are great platforms. Also check out competitive programming books by Steven Halim and Antti Laaksonen. The CP-Algorithms website is another excellent resource.",
            "How to prepare for coding interviews?" => "Focus on data structures, algorithms, system design, and problem-solving. Practice mock interviews and review common interview questions. Create a study plan that covers arrays, strings, linked lists, trees, graphs, dynamic programming, etc.",
            "What language is best for CP?" => "C++ is popular due to its speed and STL library, but Python, Java, and others are also widely used. Choose what you're most comfortable with and master its standard libraries.",
        ];
        
        $response = "I'm not sure how to help with that. Try asking about competitive programming resources, practice strategies, or specific algorithms.";
        
        // Check if the message contains certain keywords
        foreach ($responses as $keyword => $resp) {
            if (stripos($message, explode(' ', $keyword)[0]) !== false || 
                similar_text(strtolower($message), strtolower($keyword)) > strlen($keyword) * 0.6) {
                $response = $resp;
                break;
            }
        }
    }
    
    $data = [
        'success' => true,
        'message' => $response,
    ];
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Function to call the Google Gemini API
 * @param string $api_key The Gemini API key
 * @param string $prompt The prompt to send to the API
 * @return string The response from the API
 */
function callGeminiAPI($api_key, $prompt) {
    // Updated endpoint URL for the Gemini API
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=".$api_key;
    // Prepare the request payload according to Gemini API specifications
    $data = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => $prompt
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "topK" => 40,
            "topP" => 0.95,
            "maxOutputTokens" => 800,
        ],
        "safetySettings" => [
            [
                "category" => "HARM_CATEGORY_HARASSMENT",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ],
            [
                "category" => "HARM_CATEGORY_HATE_SPEECH",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ],
            [
                "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ],
            [
                "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ]
        ]
    ];
    
    // Set up cURL for more detailed error information
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Increase timeout to 30 seconds
    
    // Execute the request
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Handle errors
    if ($result === false) {
        error_log("Gemini API Error: " . $error);
        return "Sorry, I couldn't connect to the AI service. Please try again later.";
    }
    
    // Log HTTP errors
    if ($httpCode != 200) {
        error_log("Gemini API HTTP Error: " . $httpCode . " - Response: " . $result);
        
        // Try to extract error message from response
        $response_data = json_decode($result, true);
        if (isset($response_data['error']['message'])) {
            $errorMsg = $response_data['error']['message'];
            error_log("Gemini API Error Message: " . $errorMsg);
            
            // If it's a key-related error, provide specific feedback
            if (strpos($errorMsg, 'API key') !== false) {
                return "API key error: Please check your Gemini API key configuration.";
            }
            
            // If quota exceeded
            if (strpos($errorMsg, 'quota') !== false) {
                return "The API quota has been exceeded. Please try again later.";
            }
        }
        
        return "Sorry, the AI service returned an error. Please try again later.";
    }
    
    // Process successful response
    $response_data = json_decode($result, true);
    
    // Extract the text from the response
    if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        return $response_data['candidates'][0]['content']['parts'][0]['text'];
    } elseif (isset($response_data['promptFeedback']['blockReason'])) {
        // Handle content blocked by safety settings
        return "Sorry, I can't provide a response to that query due to content safety restrictions.";
    } else {
        // If structure is unexpected, log it for debugging
        error_log("Unexpected Gemini API response structure: " . $result);
        return "Sorry, I received an unexpected response format. Please try a different question.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CP Helper - CodeKeep</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Chat-specific styles */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
            background-color: #1e2130;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .chat-header {
            padding: 20px;
            background-color: #252a3d;
            border-bottom: 1px solid #2d3748;
            display: flex;
            align-items: center;
        }
        
        .chat-header-icon {
            width: 36px;
            height: 36px;
            background-color: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .chat-header-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            max-width: 80%;
        }
        
        .message.user {
            align-self: flex-end;
        }
        
        .message.bot {
            align-self: flex-start;
        }
        
        .message-bubble {
            padding: 12px 15px;
            border-radius: 18px;
            font-size: 15px;
            line-height: 1.5;
            position: relative;
        }
        
        .user .message-bubble {
            background-color: #2563eb;
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .bot .message-bubble {
            background-color: #2d3748;
            color: #e2e8f0;
            border-bottom-left-radius: 4px;
            margin-left: 0; /* Ensure no margin since avatar is gone */
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .bot-avatar {
            background-color: #48bb78;
            color: white;
        }
        
        .chat-input-container {
            padding: 15px 20px;
            border-top: 1px solid #2d3748;
            background-color: #171923;
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border-radius: 24px;
            border: 1px solid #3f4865;
            background-color: #252a3d;
            color: #e2e8f0;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
        
        .chat-send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #2563eb;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .chat-send-btn:hover {
            background-color: #1d4ed8;
        }
        
        .chat-send-btn i {
            font-size: 18px;
        }
        
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        
        .suggestion-chip {
            background-color: #252a3d;
            border: 1px solid #3f4865;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 14px;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .suggestion-chip:hover {
            background-color: #2d3748;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 12px 15px;
            background-color: #2d3748;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            width: fit-content;
            margin-top: 10px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: #a0aec0;
            border-radius: 50%;
            animation: typing-animation 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) {
            animation-delay: 0s;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing-animation {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.6;
            }
            30% {
                transform: translateY(-5px);
                opacity: 1;
            }
        }
        
        .message code {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 2px 4px;
            border-radius: 4px;
            font-family: monospace;
        }
        
        .message pre {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            width: 100%;
            margin: 10px 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .message {
                max-width: 90%;
            }
            
            .suggestions {
                margin: 10px 0;
            }
        }
        
        /* Fix for back-to-dashboard button */
        .back-to-dashboard-btn {
            position: absolute;
            top: 20px;
            right: 20px;
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
            z-index: 10;
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
            <a href="problems.php" class="nav-item">
                <i class="fas fa-code"></i>
                <span>Problems</span>
            </a>
            <a href="notes.php" class="nav-item">
                <i class="fas fa-sticky-note"></i>
                <span>Notes</span>
            </a>
            <a href="cp_helper.php" class="nav-item active">
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
    border-right: 1px solid #2d3748;"></div>

    <!-- Main Content -->
    <div class="main-content">
        <a href="dashboard.php" class="back-to-dashboard-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1>CP Helper</h1>
        <p>Your AI assistant for competitive programming help and advice.</p>
        
        <div class="suggestions">
            <div class="suggestion-chip" onclick="selectSuggestion(this)">How do I improve my problem-solving skills?</div>
            <div class="suggestion-chip" onclick="selectSuggestion(this)">What are some good resources for CP?</div>
            <div class="suggestion-chip" onclick="selectSuggestion(this)">How to prepare for coding interviews?</div>
            <div class="suggestion-chip" onclick="selectSuggestion(this)">What language is best for CP?</div>
        </div>
        
        <div class="chat-container">
            <div class="chat-header">
                <div class="chat-header-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chat-header-title">CP Helper Bot</div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message bot">
                    <div class="message-bubble">
                        Hello! I'm your CP Helper bot. How can I assist you with your competitive programming journey today?
                    </div>
                </div>
            </div>
            
            <div class="chat-input-container">
                <input type="text" class="chat-input" placeholder="Type your message here..." id="messageInput">
                <button class="chat-send-btn" id="sendButton">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Handle sending messages
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const chatMessages = document.getElementById('chatMessages');
        
        function sendMessage() {
            const message = messageInput.value.trim();
            if (message === '') return;
            
            // Add user message to chat
            addMessage(message, 'user');
            messageInput.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send message to server with a longer timeout for API processing
            fetch('cp_helper.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'send_message',
                    'message': message
                })
            })
            .then(response => response.json())
            .then(data => {
                // Remove typing indicator
                removeTypingIndicator();
                
                // Add bot response to chat
                if (data.success) {
                    addMessage(data.message, 'bot');
                } else {
                    addMessage("Sorry, I'm having trouble connecting. Please try again later.", 'bot');
                }
            })
            .catch(error => {
                // Remove typing indicator
                removeTypingIndicator();
                addMessage("Sorry, I'm having trouble connecting. Please try again later.", 'bot');
                console.error('Error:', error);
            });
        }
        
        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            let messageContent = '';
            
            // Process message text to handle code formatting
            let processedText = text.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
            processedText = processedText.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            messageContent += `<div class="message-bubble">${processedText}</div>`;
            messageDiv.innerHTML = messageContent;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot';
            typingDiv.id = 'typingIndicator';
            
            typingDiv.innerHTML = `
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            
            chatMessages.appendChild(typingDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
        
        function selectSuggestion(element) {
            messageInput.value = element.textContent;
            sendMessage();
        }
        
        // Event listeners
        sendButton.addEventListener('click', sendMessage);
        
        messageInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });
        
        function confirmLogout() {
            let logoutConfirm = confirm("Are you sure you want to logout?");
            if (logoutConfirm) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>
