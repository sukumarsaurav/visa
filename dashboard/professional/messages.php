<?php
// Set page variables
$page_title = "Messages";
$page_header = "Client Messages";

// Include header
require_once 'includes/header.php';

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Initialize variables
$current_conversation_id = null;
$conversations = [];
$messages = [];
$current_client = null;

// Check if specific client is requested
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;

// Get all conversations for this professional
$conversations_query = "SELECT c.id, c.client_id, c.case_id, c.created_at,
                       u.name as client_name, u.email as client_email, u.profile_picture,
                       COALESCE(ca.reference_number, '') as case_reference,
                       (SELECT COUNT(*) FROM chat_messages 
                        WHERE conversation_id = c.id AND is_read = 0 AND sender_id != ?) as unread_count,
                       (SELECT MAX(created_at) FROM chat_messages 
                        WHERE conversation_id = c.id) as last_message_time,
                       (SELECT content FROM chat_messages 
                        WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
                       FROM conversations c
                       JOIN users u ON c.client_id = u.id
                       LEFT JOIN case_applications ca ON c.case_id = ca.id
                       WHERE c.professional_id = ?
                       ORDER BY last_message_time DESC";

$stmt = $conn->prepare($conversations_query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

// If client_id is specified, find or create conversation
if ($client_id) {
    // Get client details
    $client_query = "SELECT u.id, u.name, u.email, u.profile_picture 
                    FROM users u 
                    WHERE u.id = ? AND u.deleted_at IS NULL";
    $stmt = $conn->prepare($client_query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $client_result = $stmt->get_result();
    
    if ($client_result->num_rows > 0) {
        $current_client = $client_result->fetch_assoc();
        
        // Check if conversation exists
        $conversation_query = "SELECT id FROM conversations 
                              WHERE professional_id = ? AND client_id = ?";
        $stmt = $conn->prepare($conversation_query);
        $stmt->bind_param("ii", $user_id, $client_id);
        $stmt->execute();
        $conv_result = $stmt->get_result();
        
        if ($conv_result->num_rows > 0) {
            $current_conversation_id = $conv_result->fetch_assoc()['id'];
        } else {
            // Create new conversation
            $create_conversation = "INSERT INTO conversations (professional_id, client_id, created_at) 
                                  VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($create_conversation);
            $stmt->bind_param("ii", $user_id, $client_id);
            $stmt->execute();
            $current_conversation_id = $conn->insert_id;
            
            // Add to conversations array
            $new_conv = [
                'id' => $current_conversation_id,
                'client_id' => $client_id,
                'client_name' => $current_client['name'],
                'client_email' => $current_client['email'],
                'profile_picture' => $current_client['profile_picture'],
                'case_reference' => '',
                'unread_count' => 0,
                'last_message' => '',
                'last_message_time' => date('Y-m-d H:i:s')
            ];
            
            array_unshift($conversations, $new_conv);
        }
    }
} else if (!empty($conversations)) {
    // If no specific client, use first conversation
    $current_conversation_id = $conversations[0]['id'];
    $client_id = $conversations[0]['client_id'];
}

// Get messages if we have a conversation
if ($current_conversation_id) {
    // Mark messages as read
    $update_read = "UPDATE chat_messages 
                  SET is_read = 1, read_at = NOW() 
                  WHERE conversation_id = ? AND sender_id != ? AND is_read = 0";
    $stmt = $conn->prepare($update_read);
    $stmt->bind_param("ii", $current_conversation_id, $user_id);
    $stmt->execute();
    
    // Get messages
    $messages_query = "SELECT cm.*, u.name as sender_name, u.user_type as sender_type 
                      FROM chat_messages cm
                      JOIN users u ON cm.sender_id = u.id
                      WHERE cm.conversation_id = ? AND cm.deleted_at IS NULL 
                      ORDER BY cm.created_at ASC";
    $stmt = $conn->prepare($messages_query);
    $stmt->bind_param("i", $current_conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

// Handle message send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $conversation_id = (int)$_POST['conversation_id'];
    $message_content = trim($_POST['message_content']);
    
    if (!empty($message_content) && $conversation_id > 0) {
        $insert_message = "INSERT INTO chat_messages (conversation_id, sender_id, content, created_at) 
                          VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_message);
        $stmt->bind_param("iis", $conversation_id, $user_id, $message_content);
        
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission
            header("Location: messages.php?conversation_id=$conversation_id");
            exit;
        }
    }
}
?>

<div class="messages-container">
    <!-- Conversations Sidebar -->
    <div class="conversations-sidebar">
        <div class="sidebar-header">
            <h3>Conversations</h3>
        </div>
        
        <div class="conversations-list">
            <?php if (empty($conversations)): ?>
                <div class="no-conversations">
                    <p>No conversations found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conversation): ?>
                    <?php
                    $profile_img = '../assets/img/default-profile.jpg';
                    if (!empty($conversation['profile_picture'])) {
                        if (file_exists('../../uploads/profiles/' . $conversation['profile_picture'])) {
                            $profile_img = '../../uploads/profiles/' . $conversation['profile_picture'];
                        }
                    }
                    $is_active = $conversation['id'] == $current_conversation_id;
                    $has_unread = $conversation['unread_count'] > 0;
                    ?>
                    <a href="?client_id=<?php echo $conversation['client_id']; ?>" 
                       class="conversation-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $has_unread ? 'unread' : ''; ?>">
                        <div class="conversation-avatar">
                            <img src="<?php echo $profile_img; ?>" alt="Profile">
                            <?php if ($has_unread): ?>
                                <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="conversation-info">
                            <div class="conversation-name"><?php echo htmlspecialchars($conversation['client_name']); ?></div>
                            <?php if (!empty($conversation['case_reference'])): ?>
                                <div class="conversation-case">Case: <?php echo htmlspecialchars($conversation['case_reference']); ?></div>
                            <?php endif; ?>
                            <div class="conversation-preview">
                                <?php 
                                if (!empty($conversation['last_message'])) {
                                    echo htmlspecialchars(substr($conversation['last_message'], 0, 30)) . 
                                        (strlen($conversation['last_message']) > 30 ? '...' : '');
                                } else {
                                    echo 'No messages yet';
                                }
                                ?>
                            </div>
                            <div class="conversation-time">
                                <?php 
                                if (!empty($conversation['last_message_time'])) {
                                    $time = strtotime($conversation['last_message_time']);
                                    $now = time();
                                    $diff = $now - $time;
                                    
                                    if ($diff < 60) {
                                        echo 'Just now';
                                    } elseif ($diff < 3600) {
                                        echo floor($diff / 60) . 'm ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . 'h ago';
                                    } else {
                                        echo date('M j', $time);
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Chat Area -->
    <div class="chat-area">
        <?php if ($current_conversation_id): ?>
            <!-- Chat Header -->
            <div class="chat-header">
                <?php
                $current_client = null;
                foreach ($conversations as $conv) {
                    if ($conv['id'] == $current_conversation_id) {
                        $current_client = [
                            'id' => $conv['client_id'],
                            'name' => $conv['client_name'],
                            'email' => $conv['client_email'],
                            'profile_picture' => $conv['profile_picture'],
                            'case_reference' => $conv['case_reference']
                        ];
                        break;
                    }
                }
                
                $profile_img = '../assets/img/default-profile.jpg';
                if (!empty($current_client['profile_picture'])) {
                    if (file_exists('../../uploads/profiles/' . $current_client['profile_picture'])) {
                        $profile_img = '../../uploads/profiles/' . $current_client['profile_picture'];
                    }
                }
                ?>
                <div class="chat-user-info">
                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="chat-avatar">
                    <div>
                        <h3><?php echo htmlspecialchars($current_client['name']); ?></h3>
                        <p><?php echo htmlspecialchars($current_client['email']); ?></p>
                        <?php if (!empty($current_client['case_reference'])): ?>
                            <span class="case-badge">Case: <?php echo htmlspecialchars($current_client['case_reference']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="chat-actions">
                    <a href="client_profile.php?id=<?php echo $current_client['id']; ?>" class="chat-action-btn">
                        <i class="fas fa-user"></i> View Profile
                    </a>
                    <a href="request_document.php?client_id=<?php echo $current_client['id']; ?>" class="chat-action-btn">
                        <i class="fas fa-file-upload"></i> Request Document
                    </a>
                    <a href="documents.php?client_id=<?php echo $current_client['id']; ?>" class="chat-action-btn">
                        <i class="fas fa-folder"></i> Documents
                    </a>
                </div>
            </div>
            
            <!-- Message List -->
            <div class="message-list" id="messageList">
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $prev_date = null;
                    foreach ($messages as $message): 
                        $is_sent = $message['sender_id'] == $user_id;
                        $msg_date = date('Y-m-d', strtotime($message['created_at']));
                        
                        // Show date separator
                        if ($prev_date != $msg_date) {
                            $today = date('Y-m-d');
                            $yesterday = date('Y-m-d', strtotime('-1 day'));
                            
                            if ($msg_date == $today) {
                                $date_display = 'Today';
                            } elseif ($msg_date == $yesterday) {
                                $date_display = 'Yesterday';
                            } else {
                                $date_display = date('F j, Y', strtotime($message['created_at']));
                            }
                            echo '<div class="date-separator"><span>' . $date_display . '</span></div>';
                            $prev_date = $msg_date;
                        }
                    ?>
                        <div class="message <?php echo $is_sent ? 'sent' : 'received'; ?>">
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                <div class="message-time">
                                    <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Input -->
            <div class="message-input-container">
                <form method="post" action="" id="messageForm">
                    <input type="hidden" name="conversation_id" value="<?php echo $current_conversation_id; ?>">
                    <div class="message-input-wrapper">
                        <textarea name="message_content" id="messageInput" placeholder="Type your message here..." required></textarea>
                        <button type="submit" name="send_message" class="send-button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="no-conversation-selected">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>No conversation selected</h3>
                    <p>Select a conversation from the sidebar or start a new one.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .messages-container {
        display: flex;
        height: calc(100vh - 60px);
        min-height: 500px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    /* Conversations Sidebar */
    .conversations-sidebar {
        width: 320px;
        border-right: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .sidebar-header h3 {
        margin: 0;
        color: #2c3e50;
    }
    
    .conversations-list {
        flex: 1;
        overflow-y: auto;
    }
    
    .conversation-item {
        display: flex;
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        text-decoration: none;
        color: #2c3e50;
        transition: background-color 0.2s;
    }
    
    .conversation-item:hover {
        background-color: #f5f7fa;
    }
    
    .conversation-item.active {
        background-color: #e3f2fd;
    }
    
    .conversation-item.unread {
        background-color: #fffde7;
    }
    
    .conversation-avatar {
        position: relative;
        margin-right: 15px;
    }
    
    .conversation-avatar img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .unread-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: #e74c3c;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
    
    .conversation-info {
        flex: 1;
        min-width: 0;
    }
    
    .conversation-name {
        font-weight: 600;
        margin-bottom: 3px;
        color: #2c3e50;
    }
    
    .conversation-case {
        font-size: 11px;
        color: #3498db;
        margin-bottom: 5px;
    }
    
    .conversation-preview {
        color: #7f8c8d;
        font-size: 13px;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .conversation-time {
        font-size: 11px;
        color: #95a5a6;
        text-align: right;
    }
    
    .no-conversations {
        padding: 20px;
        text-align: center;
        color: #7f8c8d;
        font-style: italic;
    }
    
    /* Chat Area */
    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .chat-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .chat-user-info {
        display: flex;
        align-items: center;
    }
    
    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 15px;
        object-fit: cover;
    }
    
    .chat-user-info h3 {
        margin: 0 0 5px;
        color: #2c3e50;
    }
    
    .chat-user-info p {
        margin: 0;
        color: #7f8c8d;
        font-size: 13px;
    }
    
    .case-badge {
        display: inline-block;
        padding: 2px 8px;
        background-color: #e3f2fd;
        color: #2196f3;
        border-radius: 12px;
        font-size: 11px;
        margin-top: 5px;
    }
    
    .chat-actions {
        display: flex;
        gap: 10px;
    }
    
    .chat-action-btn {
        padding: 8px 12px;
        background-color: #f5f7fa;
        color: #2c3e50;
        border-radius: 4px;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
    }
    
    .chat-action-btn:hover {
        background-color: #e3f2fd;
    }
    
    .chat-action-btn i {
        margin-right: 5px;
    }
    
    .message-list {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background-color: #f5f7fa;
    }
    
    .no-messages, .no-conversation-selected {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #95a5a6;
        text-align: center;
    }
    
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #bdc3c7;
        margin-bottom: 20px;
    }
    
    .date-separator {
        text-align: center;
        margin: 20px 0;
        position: relative;
    }
    
    .date-separator::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background-color: #e0e0e0;
        z-index: 1;
    }
    
    .date-separator span {
        background-color: #f5f7fa;
        padding: 0 10px;
        font-size: 13px;
        color: #95a5a6;
        position: relative;
        z-index: 2;
    }
    
    .message {
        display: flex;
        margin-bottom: 15px;
    }
    
    .message.sent {
        justify-content: flex-end;
    }
    
    .message-content {
        max-width: 70%;
        padding: 12px 15px;
        border-radius: 12px;
        position: relative;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .message.received .message-content {
        background-color: white;
        color: #2c3e50;
        border-bottom-left-radius: 2px;
    }
    
    .message.sent .message-content {
        background-color: #3498db;
        color: white;
        border-bottom-right-radius: 2px;
    }
    
    .message-time {
        font-size: 11px;
        margin-top: 5px;
        opacity: 0.7;
        text-align: right;
    }
    
    .message-input-container {
        padding: 15px;
        border-top: 1px solid #e0e0e0;
    }
    
    .message-input-wrapper {
        display: flex;
        align-items: center;
        background-color: #f5f7fa;
        border-radius: 24px;
        padding: 5px 15px;
    }
    
    #messageInput {
        flex: 1;
        border: none;
        background: transparent;
        padding: 10px 0;
        max-height: 100px;
        min-height: 40px;
        resize: none;
        outline: none;
        font-family: inherit;
        font-size: 14px;
    }
    
    .send-button {
        background: none;
        border: none;
        color: #3498db;
        font-size: 18px;
        cursor: pointer;
        padding: 0 5px;
        transition: color 0.2s;
    }
    
    .send-button:hover {
        color: #2980b9;
    }
    
    @media (max-width: 768px) {
        .messages-container {
            flex-direction: column;
            height: auto;
        }
        
        .conversations-sidebar {
            width: 100%;
            height: 300px;
        }
        
        .chat-area {
            height: 500px;
        }
        
        .chat-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .chat-actions {
            margin-top: 10px;
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<script>
// Global variables
let currentConversationId = null;
let isMessageSending = false;

// Auto-scroll to bottom of messages
function scrollToBottom() {
    const messageList = document.getElementById('messageList');
    if (messageList) {
        messageList.scrollTop = messageList.scrollHeight;
    }
}

// Auto-resize textarea
function autoResizeTextarea() {
    const textarea = document.getElementById('messageInput');
    if (textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
        
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
}

// Format timestamp
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Create a new conversation
async function createConversation() {
    try {
        const response = await fetch('ajax/chat_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=create_conversation'
        });
        const data = await response.json();
        if (data.success) {
            currentConversationId = data.conversation_id;
            return currentConversationId;
        }
        throw new Error(data.message || 'Failed to create conversation');
    } catch (error) {
        console.error('Error creating conversation:', error);
        return null;
    }
}

// Send message
async function sendMessage(message) {
    if (isMessageSending) return;
    isMessageSending = true;
    
    const messageList = document.getElementById('messageList');
    
    try {
        if (!currentConversationId) {
            currentConversationId = await createConversation();
            if (!currentConversationId) throw new Error('Failed to create conversation');
        }
        
        // Add user message to UI immediately
        const userMessageDiv = document.createElement('div');
        userMessageDiv.className = 'message sent';
        userMessageDiv.innerHTML = `
            <div class="message-content">
                ${message}
                <div class="message-time">${formatTimestamp(new Date())}</div>
            </div>
        `;
        messageList.appendChild(userMessageDiv);
        scrollToBottom();
        
        const response = await fetch('ajax/chat_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=send_message&conversation_id=${currentConversationId}&message=${encodeURIComponent(message)}`
        });
        
        const data = await response.json();
        if (data.success) {
            // Add AI response to UI
            if (data.response) {
                const aiMessageDiv = document.createElement('div');
                aiMessageDiv.className = 'message received';
                aiMessageDiv.innerHTML = `
                    <div class="message-content">
                        ${data.response}
                        <div class="message-time">${formatTimestamp(new Date())}</div>
                    </div>
                `;
                messageList.appendChild(aiMessageDiv);
                scrollToBottom();
            }
            
            // Clear input and resize
            document.getElementById('messageInput').value = '';
            autoResizeTextarea();
            
            // Update conversation list if needed
            updateConversationList();
        } else {
            throw new Error(data.message || 'Failed to send message');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert(error.message || 'Failed to send message. Please try again.');
        // Remove the user message if it failed
        if (messageList.lastChild) {
            messageList.removeChild(messageList.lastChild);
        }
    } finally {
        isMessageSending = false;
    }
}

// Load conversation messages
async function loadConversation(conversationId) {
    try {
        const response = await fetch(`ajax/chat_handler.php?action=get_conversation&conversation_id=${conversationId}`);
        const data = await response.json();
        if (data.success) {
            const messageList = document.getElementById('messageList');
            messageList.innerHTML = '';
            
            // Update conversation title if available
            const titleElement = document.getElementById('conversationTitle');
            if (titleElement && data.conversation && data.conversation.title) {
                titleElement.textContent = data.conversation.title;
            }
            
            let lastDate = '';
            data.messages.forEach(message => {
                const messageDate = new Date(message.created_at).toLocaleDateString();
                if (messageDate !== lastDate) {
                    const dateDiv = document.createElement('div');
                    dateDiv.className = 'date-separator';
                    dateDiv.innerHTML = `<span>${messageDate}</span>`;
                    messageList.appendChild(dateDiv);
                    lastDate = messageDate;
                }
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${message.role === 'user' ? 'sent' : 'received'}`;
                messageDiv.innerHTML = `
                    <div class="message-content">
                        ${message.content}
                        <div class="message-time">${formatTimestamp(message.created_at)}</div>
                    </div>
                `;
                messageList.appendChild(messageDiv);
            });
            scrollToBottom();
            
            // Enable input after loading
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.disabled = false;
            }
        }
    } catch (error) {
        console.error('Error loading conversation:', error);
        alert('Failed to load conversation. Please try again.');
    }
}

// Update conversation list
async function updateConversationList() {
    try {
        const response = await fetch('ajax/chat_handler.php?action=get_conversations');
        const data = await response.json();
        if (data.success && data.conversations) {
            const conversationList = document.getElementById('conversationList');
            if (conversationList) {
                conversationList.innerHTML = '';
                data.conversations.forEach(conv => {
                    const item = document.createElement('div');
                    item.className = `conversation-item${conv.id === currentConversationId ? ' active' : ''}`;
                    item.dataset.conversationId = conv.id;
                    item.innerHTML = `
                        <div class="conversation-title">${conv.title || 'New Conversation'}</div>
                        <div class="conversation-time">${formatTimestamp(conv.created_at)}</div>
                    `;
                    item.addEventListener('click', () => {
                        currentConversationId = conv.id;
                        loadConversation(conv.id);
                        document.querySelectorAll('.conversation-item').forEach(el => {
                            el.classList.remove('active');
                        });
                        item.classList.add('active');
                    });
                    conversationList.appendChild(item);
                });
            }
        }
    } catch (error) {
        console.error('Error updating conversation list:', error);
    }
}

// Check usage
async function checkUsage() {
    try {
        const response = await fetch('ajax/chat_handler.php?action=get_usage');
        const data = await response.json();
        if (data.success) {
            // Update UI with usage information if needed
            console.log('Current usage:', data.usage);
        }
    } catch (error) {
        console.error('Error checking usage:', error);
    }
}

// Delete conversation
async function deleteConversation(conversationId) {
    try {
        const response = await fetch('ajax/chat_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_conversation&conversation_id=${conversationId}`
        });
        const data = await response.json();
        if (data.success) {
            // Refresh conversation list or handle UI update
            if (currentConversationId === conversationId) {
                currentConversationId = null;
                document.getElementById('messageList').innerHTML = '';
            }
        }
    } catch (error) {
        console.error('Error deleting conversation:', error);
    }
}

// On page load
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    autoResizeTextarea();
    
    // Form submission
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (message !== '') {
                sendMessage(message);
            }
        });
    }
    
    // Initial conversation list load
    updateConversationList();
    
    // Check initial usage
    checkUsage();
    
    // Load last active conversation if available
    const urlParams = new URLSearchParams(window.location.search);
    const conversationId = urlParams.get('conversation_id');
    if (conversationId) {
        currentConversationId = conversationId;
        loadConversation(conversationId);
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>

