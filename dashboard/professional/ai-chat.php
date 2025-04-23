<?php
// Set page variables
$page_title = "AI Chat";
$page_header = "Visafy AI Chat";

// Include header
include_once('includes/header.php');

// Check if the user is a professional
$is_professional = false;
$user_id = $_SESSION['user_id'] ?? 0;

// Verify professional status
$check_query = "SELECT p.id FROM professionals p WHERE p.user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $is_professional = true;
} else {
    // Redirect non-professionals to dashboard
    header("Location: index.php");
    exit;
}

// Load OpenAI API key from .env file
$env_file = __DIR__ . '/../../config/.env';
$openai_api_key = '';

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'OPENAI_API_KEY=') === 0) {
            $openai_api_key = trim(substr($line, strlen('OPENAI_API_KEY=')));
            break;
        }
    }
}

if (empty($openai_api_key)) {
    $api_error = "OpenAI API key not configured. Please contact the administrator.";
}

// Initialize variables
$active_conversation_id = null;
$conversations = [];
$messages = [];
$monthly_limit = 50; // Monthly message limit
$month_usage = 0;
$current_month = date('Y-m');
$chat_type = '';
$error_message = '';
$success_message = '';

// Check monthly usage
$usage_query = "SELECT message_count FROM ai_chat_usage 
                WHERE professional_id = ? AND month = ?";
$stmt = $conn->prepare($usage_query);
$stmt->bind_param("is", $user_id, $current_month);
$stmt->execute();
$usage_result = $stmt->get_result();

if ($usage_result->num_rows > 0) {
    $month_usage = $usage_result->fetch_assoc()['message_count'];
} else {
    // Create new usage record for this month
    $create_usage = "INSERT INTO ai_chat_usage (professional_id, month, message_count)
                    VALUES (?, ?, 0)";
    $stmt = $conn->prepare($create_usage);
    $stmt->bind_param("is", $user_id, $current_month);
    $stmt->execute();
}

// Get user's conversation history
$history_query = "SELECT c.id, c.title, c.chat_type, c.created_at, c.updated_at,
                 (SELECT COUNT(*) FROM ai_chat_messages m WHERE m.conversation_id = c.id) as message_count
                 FROM ai_chat_conversations c
                 WHERE c.professional_id = ? AND c.deleted_at IS NULL
                 ORDER BY c.updated_at DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history_result = $stmt->get_result();

while ($row = $history_result->fetch_assoc()) {
    $conversations[] = $row;
}

// Handle new conversation creation
if (isset($_GET['new']) && in_array($_GET['type'], ['ircc', 'cases'])) {
    $chat_type = $_GET['type'];
    $default_title = ($chat_type == 'ircc') ? 'IRCC Rules and Regulations' : 'Case Laws Research';
    
    $create_query = "INSERT INTO ai_chat_conversations (professional_id, title, chat_type) 
                    VALUES (?, ?, ?)";
    $stmt = $conn->prepare($create_query);
    $stmt->bind_param("iss", $user_id, $default_title, $chat_type);
    
    if ($stmt->execute()) {
        $active_conversation_id = $conn->insert_id;
        header("Location: ai-chat.php?conversation=" . $active_conversation_id);
        exit;
    } else {
        $error_message = "Failed to create new conversation. Please try again.";
    }
}

// Load specific conversation if requested
if (isset($_GET['conversation']) && is_numeric($_GET['conversation'])) {
    $active_conversation_id = (int)$_GET['conversation'];
    
    // Verify conversation belongs to this user
    $verify_query = "SELECT id, chat_type, title FROM ai_chat_conversations 
                    WHERE id = ? AND professional_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $active_conversation_id, $user_id);
    $stmt->execute();
    $verify_result = $stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $conversation_data = $verify_result->fetch_assoc();
        $chat_type = $conversation_data['chat_type'];
        
        // Get messages for this conversation
        $messages_query = "SELECT * FROM ai_chat_messages 
                         WHERE conversation_id = ? AND deleted_at IS NULL 
                         ORDER BY created_at ASC";
        $stmt = $conn->prepare($messages_query);
        $stmt->bind_param("i", $active_conversation_id);
        $stmt->execute();
        $messages_result = $stmt->get_result();
        
        while ($row = $messages_result->fetch_assoc()) {
            $messages[] = $row;
        }
    } else {
        $active_conversation_id = null;
        $error_message = "Conversation not found.";
    }
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['conversation_id'])) {
    $user_message = trim($_POST['message']);
    $conversation_id = (int)$_POST['conversation_id'];
    
    // Verify conversation belongs to this user and get type
    $verify_query = "SELECT id, chat_type FROM ai_chat_conversations 
                   WHERE id = ? AND professional_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $conversation_id, $user_id);
    $stmt->execute();
    $verify_result = $stmt->get_result();
    
    if ($verify_result->num_rows > 0 && !empty($user_message)) {
        $conversation_data = $verify_result->fetch_assoc();
        $chat_type = $conversation_data['chat_type'];
        
        // Check monthly limit
        if ($month_usage >= $monthly_limit) {
            $error_message = "You have reached your monthly limit of {$monthly_limit} messages. Please try again next month.";
        } else {
            // Insert user message
            $insert_message = "INSERT INTO ai_chat_messages (conversation_id, professional_id, role, content) 
                             VALUES (?, ?, 'user', ?)";
            $stmt = $conn->prepare($insert_message);
            $stmt->bind_param("iis", $conversation_id, $user_id, $user_message);
            
            if ($stmt->execute()) {
                $user_message_id = $conn->insert_id;
                
                // Update conversation update timestamp
                $update_query = "UPDATE ai_chat_conversations SET updated_at = NOW() 
                               WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $conversation_id);
                $stmt->execute();
                
                // Increment usage counter
                $update_usage = "UPDATE ai_chat_usage SET message_count = message_count + 1 
                               WHERE professional_id = ? AND month = ?";
                $stmt = $conn->prepare($update_usage);
                $stmt->bind_param("is", $user_id, $current_month);
                $stmt->execute();
                
                // Send to OpenAI API and get response
                $ai_response = getOpenAIResponse($user_message, $messages, $chat_type, $openai_api_key);
                
                if ($ai_response) {
                    // Insert AI response
                    $insert_response = "INSERT INTO ai_chat_messages (conversation_id, professional_id, role, content) 
                                      VALUES (?, ?, 'assistant', ?)";
                    $stmt = $conn->prepare($insert_response);
                    $stmt->bind_param("iis", $conversation_id, $user_id, $ai_response);
                    $stmt->execute();
                    
                    // Reload the page to show the new messages
                    header("Location: ai-chat.php?conversation=" . $conversation_id);
                    exit;
                } else {
                    $error_message = "Failed to get AI response. Please try again.";
                }
            } else {
                $error_message = "Failed to send message. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid conversation or empty message.";
    }
}

// Function to get response from OpenAI API
function getOpenAIResponse($user_message, $conversation_history, $chat_type, $api_key) {
    if (empty($api_key)) return false;
    
    $messages = [];
    
    // Add system message based on chat type
    if ($chat_type == 'ircc') {
        $system_message = "You are an AI assistant specialized in Canadian immigration rules, regulations, and policies. Provide accurate, up-to-date information about IRCC processes, requirements, and guidelines. Focus on factual information and cite sources when possible. Do not provide personal legal advice.";
    } else {
        $system_message = "You are an AI assistant specialized in Canadian immigration case law. Help users research relevant cases from the Federal Court, Federal Court of Appeal, and other Canadian courts. Provide case citations, summaries of key findings, and explanations of legal precedents. Do not provide personal legal advice.";
    }
    
    $messages[] = ["role" => "system", "content" => $system_message];
    
    // Add conversation history (limited to last 10 messages to save tokens)
    $relevant_history = array_slice($conversation_history, -10);
    foreach ($relevant_history as $msg) {
        $messages[] = ["role" => $msg['role'], "content" => $msg['content']];
    }
    
    // Add the current user message
    $messages[] = ["role" => "user", "content" => $user_message];
    
    // Prepare request to OpenAI API
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => $messages,
        "temperature" => 0.7,
        "max_tokens" => 1000
    ];
    
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    ];
    
    // Initialize cURL session
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute cURL session and get response
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check if request was successful
    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        if (isset($response_data['choices'][0]['message']['content'])) {
            return $response_data['choices'][0]['message']['content'];
        }
    }
    
    return false;
}

// Update conversation title if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_title'], $_POST['conversation_id'], $_POST['new_title'])) {
    $conversation_id = (int)$_POST['conversation_id'];
    $new_title = trim($_POST['new_title']);
    
    if (!empty($new_title)) {
        $update_query = "UPDATE ai_chat_conversations SET title = ? 
                       WHERE id = ? AND professional_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sii", $new_title, $conversation_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Conversation title updated successfully.";
            // Update title in the current array of conversations
            foreach ($conversations as &$conv) {
                if ($conv['id'] == $conversation_id) {
                    $conv['title'] = $new_title;
                    break;
                }
            }
        } else {
            $error_message = "Failed to update conversation title.";
        }
    }
}

// Delete conversation if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $conversation_id = (int)$_GET['delete'];
    
    // Soft delete the conversation
    $delete_query = "UPDATE ai_chat_conversations SET deleted_at = NOW() 
                   WHERE id = ? AND professional_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $conversation_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // If we deleted the active conversation, redirect to the main page
        if ($active_conversation_id == $conversation_id) {
            header("Location: ai-chat.php");
            exit;
        } else {
            // Remove from the array
            foreach ($conversations as $key => $conv) {
                if ($conv['id'] == $conversation_id) {
                    unset($conversations[$key]);
                    break;
                }
            }
            $success_message = "Conversation deleted successfully.";
        }
    } else {
        $error_message = "Failed to delete conversation.";
    }
}

// Add page specific CSS
$page_specific_css = '
.ai-chat-container {
    display: flex;
    gap: 24px;
    height: calc(100vh - 180px);
    margin: 20px;
}

.chat-history-sidebar {
    width: 280px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.history-header {
    padding: 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.history-header h3 {
    margin: 0;
    color: #2c3e50;
}

.usage-info {
    margin-top: 5px;
    font-size: 0.8rem;
    color: #7f8c8d;
}

.usage-progress {
    height: 6px;
    background-color: #ecf0f1;
    border-radius: 3px;
    margin-top: 3px;
    overflow: hidden;
}

.usage-bar {
    height: 100%;
    background-color: #3498db;
    border-radius: 3px;
}

.usage-bar.warning {
    background-color: #f39c12;
}

.usage-bar.danger {
    background-color: #e74c3c;
}

.chat-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.chat-item {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
    position: relative;
}

.chat-item:hover {
    background-color: #f8f9fa;
}

.chat-item.active {
    background-color: #e8f4ff;
}

.chat-item h4 {
    margin: 0 0 4px 0;
    font-size: 0.9rem;
    color: #2c3e50;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-item p {
    margin: 0;
    font-size: 0.8rem;
    color: #666;
}

.chat-item .delete-btn {
    position: absolute;
    right: 10px;
    top: 10px;
    opacity: 0;
    transition: opacity 0.2s;
    color: #e74c3c;
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px;
}

.chat-item:hover .delete-btn {
    opacity: 1;
}

.new-chat-btn {
    padding: 12px;
    text-align: center;
    margin-top: 10px;
}

.chat-main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.ai-options-container {
    display: flex;
    gap: 24px;
    padding: 20px;
    justify-content: center;
}

.ai-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    width: 400px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    cursor: pointer;
}

.ai-card:hover {
    transform: translateY(-5px);
}

.ai-card h3 {
    color: #2c3e50;
    margin-bottom: 16px;
    font-size: 1.5rem;
}

.ai-card p {
    color: #666;
    margin-bottom: 24px;
    line-height: 1.6;
}

.ai-card .button {
    width: 100%;
    text-align: center;
}

.beta-badge {
    background: #e74c3c;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    margin-left: 8px;
}

.chat-interface {
    display: none;
    flex-direction: column;
    height: 100%;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chat-interface.active {
    display: flex;
}

.chat-header {
    padding: 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-title {
    margin: 0;
    color: #2c3e50;
    max-width: 70%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.edit-title-btn {
    background: none;
    border: none;
    color: #7f8c8d;
    cursor: pointer;
    padding: 5px;
    margin-left: 10px;
    transition: color 0.2s;
}

.edit-title-btn:hover {
    color: #2c3e50;
}

.chat-title-form {
    display: none;
    flex: 1;
}

.chat-title-form.active {
    display: flex;
}

.chat-title-form input {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-right: 8px;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background-color: #f5f7fa;
}

.message {
    margin-bottom: 16px;
    max-width: 80%;
    display: flex;
    flex-direction: column;
}

.message.user {
    margin-left: auto;
    align-items: flex-end;
}

.message-content {
    padding: 12px 16px;
    border-radius: 12px;
    background: white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    position: relative;
}

.message.user .message-content {
    background: #4a6fdc;
    color: white;
    border-bottom-right-radius: 2px;
}

.message.assistant .message-content {
    background: white;
    color: #2c3e50;
    border-bottom-left-radius: 2px;
}

.message-time {
    font-size: 0.7rem;
    color: #95a5a6;
    margin-top: 5px;
}

.message-role {
    font-size: 0.8rem;
    color: #7f8c8d;
    margin-bottom: 5px;
    text-transform: capitalize;
}

.message.user .message-role,
.message.user .message-time {
    text-align: right;
}

.typing-indicator {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}

.typing-indicator span {
    height: 8px;
    width: 8px;
    margin: 0 2px;
    background-color: #bdc3c7;
    border-radius: 50%;
    display: inline-block;
    animation: typing 1.4s infinite both;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
    100% { transform: translateY(0); }
}

.chat-input {
    padding: 16px;
    border-top: 1px solid #eee;
}

.chat-input form {
    display: flex;
    gap: 12px;
}

.chat-input textarea {
    flex: 1;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    resize: none;
    min-height: 24px;
    max-height: 120px;
    outline: none;
    font-family: inherit;
}

.chat-input button {
    padding: 12px 24px;
    background: #4a6fdc;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s;
    height: fit-content;
    align-self: flex-end;
}

.chat-input button:disabled {
    background: #bdc3c7;
    cursor: not-allowed;
}

.chat-input button:hover:not(:disabled) {
    background: #3d5cbe;
}

.alert {
    padding: 12px 16px;
    margin-bottom: 16px;
    border-radius: 6px;
    font-size: 0.9rem;
}

.alert-error {
    background-color: #fde8e8;
    color: #e53e3e;
    border: 1px solid #f8b4b4;
}

.alert-success {
    background-color: #e6fffa;
    color: #047857;
    border: 1px solid #a7f3d0;
}

@media (max-width: 768px) {
    .ai-chat-container {
        flex-direction: column;
        height: auto;
    }
    
    .chat-history-sidebar {
        width: 100%;
        height: 250px;
    }
    
    .ai-options-container {
        flex-direction: column;
        padding: 10px;
    }
    
    .ai-card {
        width: 100%;
    }
}
';

// Add page specific JavaScript
$page_js = '
function startChat(type) {
    window.location.href = "ai-chat.php?new=1&type=" + type;
}

function autoGrow(element) {
    element.style.height = "24px";
    element.style.height = (element.scrollHeight) + "px";
}

function sendMessage(event) {
    event.preventDefault();
    
    const form = event.target;
    const textarea = form.querySelector("textarea");
    const submitBtn = form.querySelector("button[type=submit]");
    const message = textarea.value.trim();
    
    if (message) {
        // Disable form controls
        textarea.disabled = true;
        submitBtn.disabled = true;
        
        // Show typing indicator
        const chatMessages = document.getElementById("chat-messages");
        chatMessages.innerHTML += `
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Submit the form
        form.submit();
    }
}

function editTitle() {
    document.querySelector(".chat-title").style.display = "none";
    document.querySelector(".edit-title-btn").style.display = "none";
    document.querySelector(".chat-title-form").classList.add("active");
    document.querySelector(".chat-title-form input").focus();
}

function cancelEditTitle() {
    document.querySelector(".chat-title").style.display = "inline";
    document.querySelector(".edit-title-btn").style.display = "inline";
    document.querySelector(".chat-title-form").classList.remove("active");
}

function confirmDelete(id, title) {
    if (confirm("Are you sure you want to delete the conversation \'" + title + "\'?")) {
        window.location.href = "ai-chat.php?delete=" + id;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // Auto-resize textarea
    const textarea = document.querySelector(".chat-input textarea");
    if (textarea) {
        textarea.addEventListener("input", function() {
            autoGrow(this);
        });
    }
    
    // Scroll chat to bottom
    const chatMessages = document.getElementById("chat-messages");
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
';
?>

<!-- AI Chat Container -->
<div class="ai-chat-container">
    <!-- Chat History Sidebar -->
    <div class="chat-history-sidebar">
        <div class="history-header">
            <h3>Chat History</h3>
            
            <!-- Monthly usage indicator -->
            <div class="usage-info">
                <?php 
                    $usage_percent = ($monthly_limit > 0) ? ($month_usage / $monthly_limit) * 100 : 0;
                    $usage_class = '';
                    if ($usage_percent >= 90) {
                        $usage_class = 'danger';
                    } else if ($usage_percent >= 70) {
                        $usage_class = 'warning';
                    }
                ?>
                <span><?php echo $month_usage; ?>/<?php echo $monthly_limit; ?> messages this month</span>
                <div class="usage-progress">
                    <div class="usage-bar <?php echo $usage_class; ?>" style="width: <?php echo min(100, $usage_percent); ?>%;"></div>
                </div>
            </div>
        </div>
        
        <div class="chat-list">
            <?php if (empty($conversations)): ?>
                <p style="text-align: center; color: #7f8c8d; font-style: italic;">No conversations yet.</p>
            <?php else: ?>
                <?php foreach ($conversations as $conversation): ?>
                    <div class="chat-item <?php echo ($active_conversation_id == $conversation['id']) ? 'active' : ''; ?>"
                         onclick="window.location.href='ai-chat.php?conversation=<?php echo $conversation['id']; ?>'">
                        
                        <h4><?php echo htmlspecialchars($conversation['title']); ?></h4>
                        <p>
                            <?php echo $conversation['message_count']; ?> messages • 
                            <?php 
                                $updated = strtotime($conversation['updated_at']);
                                $now = time();
                                $diff = $now - $updated;
                                
                                if ($diff < 60) {
                                    echo 'Just now';
                                } elseif ($diff < 3600) {
                                    echo floor($diff / 60) . 'm ago';
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . 'h ago';
                                } elseif ($diff < 604800) {
                                    echo floor($diff / 86400) . 'd ago';
                                } else {
                                    echo date('M j', $updated);
                                }
                            ?>
                        </p>
                        <span class="badge"><?php echo ucfirst($conversation['chat_type']); ?></span>
                        
                        <!-- Delete button -->
                        <button class="delete-btn" onclick="event.stopPropagation(); confirmDelete(<?php echo $conversation['id']; ?>, '<?php echo addslashes(htmlspecialchars($conversation['title'])); ?>');">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- New Chat buttons -->
            <div class="new-chat-btn">
                <button class="button" onclick="startChat('ircc')">New IRCC Chat</button>
            </div>
            <div class="new-chat-btn">
                <button class="button" onclick="startChat('cases')">New Case Law Chat</button>
            </div>
        </div>
    </div>

    <!-- Main Chat Content -->
    <div class="chat-main-content">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($active_conversation_id): ?>
            <!-- Active Chat Interface -->
            <div class="chat-interface active">
                <div class="chat-header">
                    <h3 class="chat-title"><?php echo htmlspecialchars($conversation_data['title']); ?></h3>
                    <button class="edit-title-btn" onclick="editTitle()">
                        <i class="fas fa-edit"></i>
                    </button>
                    
                    <!-- Edit title form -->
                    <form class="chat-title-form" method="post" action="">
                        <input type="hidden" name="conversation_id" value="<?php echo $active_conversation_id; ?>">
                        <input type="text" name="new_title" value="<?php echo htmlspecialchars($conversation_data['title']); ?>" required>
                        <button type="submit" name="update_title" class="button button-small">Save</button>
                        <button type="button" class="button button-small button-secondary" onclick="cancelEditTitle()">Cancel</button>
                    </form>
                </div>
                
                <div class="chat-messages" id="chat-messages">
                    <?php if (empty($messages)): ?>
                        <div class="welcome-message">
                            <?php if ($chat_type == 'ircc'): ?>
                                <h3>IRCC Rules and Regulations Chat</h3>
                                <p>Ask questions about Canadian immigration rules, processes, and official policies.</p>
                                <p>Example questions:</p>
                                <ul>
                                    <li>What are the requirements for Express Entry?</li>
                                    <li>How do I apply for a study permit?</li>
                                    <li>What is the process for permanent residency through Provincial Nominee Program?</li>
                                </ul>
                            <?php else: ?>
                                <h3>Case Laws Chat <span class="beta-badge">Beta</span></h3>
                                <p>Research Canadian immigration cases from Federal Court, Court of Appeal, and other Canadian courts.</p>
                                <p>Example questions:</p>
                                <ul>
                                    <li>Find cases about IRCC procedural fairness in study permit refusals.</li>
                                    <li>What are recent precedents for H&C applications?</li>
                                    <li>Explain key Federal Court decisions on work permit requirements.</li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['role']; ?>">
                                <div class="message-role"><?php echo $message['role']; ?></div>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <form method="post" action="" onsubmit="sendMessage(event)">
                        <input type="hidden" name="conversation_id" value="<?php echo $active_conversation_id; ?>">
                        <textarea 
                            name="message" 
                            placeholder="Type your message here..." 
                            required 
                            oninput="autoGrow(this)"
                            <?php if ($month_usage >= $monthly_limit): ?>disabled<?php endif; ?>
                        ></textarea>
                        <button type="submit" <?php if ($month_usage >= $monthly_limit): ?>disabled<?php endif; ?>>
                            Send
                        </button>
                    </form>
                    
                    <?php if ($month_usage >= $monthly_limit): ?>
                        <p class="limit-message">You've reached your monthly limit of <?php echo $monthly_limit; ?> messages.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Options Container (shown when no conversation is active) -->
            <div class="ai-options-container">
                <!-- IRCC Rules Card -->
                <div class="ai-card">
                    <h3>IRCC Rules and Regulations</h3>
                    <p>Research general Canadian immigration information, policies, processes, and official guidelines.</p>
                    <button class="button" onclick="startChat('ircc')">Start Chat</button>
                </div>
                
                <!-- Case Laws Card -->
                <div class="ai-card">
                    <h3>Case Laws <span class="beta-badge">Beta</span></h3>
                    <p>Research Canadian immigration cases from The Federal Court, Court of Appeal, and other Canadian courts.</p>
                    <button class="button" onclick="startChat('cases')">Start Chat</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once('includes/footer.php');
?> 