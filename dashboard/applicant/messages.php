<?php
// Set page variables
$page_title = "Messages";
$page_header = "Professional Messages";

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Initialize variables
$current_conversation_id = null;
$conversations = [];
$messages = [];
$current_professional = null;

// Get all confirmed bookings for this client
$bookings_query = "SELECT DISTINCT b.professional_id, u.name as professional_name, 
                   u.email as professional_email, u.profile_picture,
                   p.bio, p.years_experience
                   FROM bookings b
                   JOIN users u ON b.professional_id = u.id
                   JOIN professionals p ON b.professional_id = p.user_id
                   WHERE b.client_id = ? 
                   AND b.status = 'confirmed'
                   AND b.deleted_at IS NULL";

$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$professionals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if specific professional is requested
$professional_id = isset($_GET['professional_id']) ? (int)$_GET['professional_id'] : null;

// Get all conversations for this client
$conversations_query = "SELECT c.id, c.professional_id, c.case_id, c.created_at,
                       u.name as professional_name, u.email as professional_email, 
                       u.profile_picture, p.bio, p.years_experience,
                       COALESCE(ca.reference_number, '') as case_reference,
                       (SELECT COUNT(*) FROM chat_messages 
                        WHERE conversation_id = c.id AND is_read = 0 
                        AND sender_id != ?) as unread_count,
                       (SELECT created_at FROM chat_messages 
                        WHERE conversation_id = c.id 
                        ORDER BY created_at DESC LIMIT 1) as last_message_time,
                       (SELECT content FROM chat_messages 
                        WHERE conversation_id = c.id 
                        ORDER BY created_at DESC LIMIT 1) as last_message
                       FROM conversations c
                       JOIN users u ON c.professional_id = u.id
                       JOIN professionals p ON c.professional_id = p.user_id
                       LEFT JOIN case_applications ca ON c.case_id = ca.id
                       WHERE c.client_id = ?
                       ORDER BY CASE WHEN last_message_time IS NULL THEN 0 ELSE 1 END DESC, 
                       last_message_time DESC";

$stmt = $conn->prepare($conversations_query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// If professional_id is specified and has a confirmed booking, find or create conversation
if ($professional_id) {
    // Verify booking exists
    $booking_check = "SELECT COUNT(*) as has_booking FROM bookings 
                     WHERE client_id = ? AND professional_id = ? 
                     AND status = 'confirmed' AND deleted_at IS NULL";
    $stmt = $conn->prepare($booking_check);
    $stmt->bind_param("ii", $user_id, $professional_id);
    $stmt->execute();
    $has_booking = $stmt->get_result()->fetch_assoc()['has_booking'] > 0;

    if ($has_booking) {
        // Get professional details
        $prof_query = "SELECT u.id, u.name, u.email, u.profile_picture, p.bio, p.years_experience
                      FROM users u 
                      JOIN professionals p ON u.id = p.user_id
                      WHERE u.id = ? AND u.deleted_at IS NULL";
        $stmt = $conn->prepare($prof_query);
        $stmt->bind_param("i", $professional_id);
        $stmt->execute();
        $current_professional = $stmt->get_result()->fetch_assoc();
        
        if ($current_professional) {
            // Check if conversation exists
            $conv_check = "SELECT id FROM conversations 
                          WHERE client_id = ? AND professional_id = ?";
            $stmt = $conn->prepare($conv_check);
            $stmt->bind_param("ii", $user_id, $professional_id);
            $stmt->execute();
            $conv_result = $stmt->get_result();
            
            if ($conv_result->num_rows > 0) {
                $current_conversation_id = $conv_result->fetch_assoc()['id'];
            } else {
                // Create new conversation
                $create_conv = "INSERT INTO conversations (client_id, professional_id, created_at) 
                              VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($create_conv);
                $stmt->bind_param("ii", $user_id, $professional_id);
                $stmt->execute();
                $current_conversation_id = $conn->insert_id;
            }
        }
    }
} else if (!empty($conversations)) {
    // If no specific professional, use first conversation
    $current_conversation_id = $conversations[0]['id'];
    $professional_id = $conversations[0]['professional_id'];
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
    $messages_query = "SELECT cm.*, u.name as sender_name, 
                      CASE WHEN cm.sender_id = ? THEN 'sent' ELSE 'received' END as message_type
                      FROM chat_messages cm
                      JOIN users u ON cm.sender_id = u.id
                      WHERE cm.conversation_id = ? AND cm.deleted_at IS NULL 
                      ORDER BY cm.created_at ASC";
    $stmt = $conn->prepare($messages_query);
    $stmt->bind_param("ii", $user_id, $current_conversation_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle message send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $conv_id = (int)$_POST['conversation_id'];
    
    if (!empty($message) && $conv_id > 0) {
        // Verify conversation belongs to user
        $verify_conv = "SELECT id FROM conversations 
                       WHERE id = ? AND client_id = ?";
        $stmt = $conn->prepare($verify_conv);
        $stmt->bind_param("ii", $conv_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Insert message
            $insert_msg = "INSERT INTO chat_messages (conversation_id, sender_id, content, created_at) 
                          VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_msg);
            $stmt->bind_param("iis", $conv_id, $user_id, $message);
            
            if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                header("Location: messages.php?professional_id=$professional_id");
                exit;
            }
        }
    }
}
?>

<div class="messages-container">
    <!-- Conversations Sidebar -->
    <div class="conversations-sidebar">
        <div class="sidebar-header">
            <h3>Your Conversations</h3>
        </div>
        
        <div class="conversations-list">
            <?php if (empty($conversations)): ?>
                <div class="no-conversations">
                    <p>No conversations found.</p>
                    <?php if (!empty($professionals)): ?>
                        <p>You can start a conversation with your confirmed professionals:</p>
                        <?php foreach ($professionals as $prof): ?>
                            <a href="?professional_id=<?php echo $prof['professional_id']; ?>" 
                               class="professional-item">
                                <img src="<?php echo !empty($prof['profile_picture']) ? 
                                    '../../uploads/profiles/' . $prof['profile_picture'] : 
                                    '../../assets/images/default-avatar.png'; ?>" 
                                     alt="Profile" class="professional-avatar">
                                <div class="professional-info">
                                    <div class="professional-name"><?php echo htmlspecialchars($prof['professional_name']); ?></div>
                                    <div class="professional-exp"><?php echo $prof['years_experience']; ?> years experience</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="?professional_id=<?php echo $conv['professional_id']; ?>" 
                       class="conversation-item <?php echo $conv['id'] == $current_conversation_id ? 'active' : ''; ?>">
                        <img src="<?php echo !empty($conv['profile_picture']) ? 
                            '../../uploads/profiles/' . $conv['profile_picture'] : 
                            '../../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="conversation-avatar">
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <?php echo htmlspecialchars($conv['professional_name']); ?>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($conv['case_reference'])): ?>
                                <div class="case-reference">Case: <?php echo htmlspecialchars($conv['case_reference']); ?></div>
                            <?php endif; ?>
                            <div class="last-message">
                                <?php 
                                if (!empty($conv['last_message'])) {
                                    echo htmlspecialchars(substr($conv['last_message'], 0, 30)) . 
                                        (strlen($conv['last_message']) > 30 ? '...' : '');
                                } else {
                                    echo 'No messages yet';
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
        <?php if ($current_conversation_id && $current_professional): ?>
            <div class="chat-header">
                <div class="professional-info">
                    <img src="<?php echo !empty($current_professional['profile_picture']) ? 
                        '../../uploads/profiles/' . $current_professional['profile_picture'] : 
                        '../../assets/images/default-avatar.png'; ?>" 
                         alt="Profile" class="professional-avatar">
                    <div>
                        <h3><?php echo htmlspecialchars($current_professional['name']); ?></h3>
                        <p><?php echo $current_professional['years_experience']; ?> years experience</p>
                    </div>
                </div>
            </div>
            
            <div class="message-list" id="messageList">
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $last_date = '';
                    foreach ($messages as $message): 
                        $message_date = date('Y-m-d', strtotime($message['created_at']));
                        if ($message_date != $last_date):
                            $last_date = $message_date;
                    ?>
                        <div class="date-separator">
                            <span><?php echo date('F j, Y', strtotime($message_date)); ?></span>
                        </div>
                    <?php endif; ?>
                        <div class="message <?php echo $message['message_type']; ?>">
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
            
            <form method="post" class="message-form">
                <input type="hidden" name="conversation_id" value="<?php echo $current_conversation_id; ?>">
                <div class="message-input-wrapper">
                    <textarea name="message" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="send-button">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="no-chat-selected">
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Select a Conversation</h3>
                    <p>Choose a professional from the list to start chatting</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.messages-container {
    display: flex;
    gap: 20px;
    margin: 20px;
    height: calc(100vh - 140px);
    min-height: 500px;
}

.conversations-sidebar {
    flex: 0 0 300px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    padding: 10px;
}

.conversation-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.2s;
}

.conversation-item:hover {
    background-color: #f5f7fa;
}

.conversation-item.active {
    background-color: #e3f2fd;
}

.conversation-avatar,
.professional-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    object-fit: cover;
}

.conversation-info {
    flex: 1;
}

.conversation-name {
    font-weight: 600;
    margin-bottom: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.unread-badge {
    background: #e74c3c;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 12px;
}

.case-reference {
    font-size: 12px;
    color: #3498db;
    margin-bottom: 5px;
}

.last-message {
    color: #7f8c8d;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-area {
    flex: 1;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.chat-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.professional-info {
    display: flex;
    align-items: center;
}

.professional-info h3 {
    margin: 0 0 5px;
}

.professional-info p {
    margin: 0;
    color: #7f8c8d;
}

.message-list {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f5f7fa;
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
    background: #e0e0e0;
}

.date-separator span {
    background: #f5f7fa;
    padding: 0 10px;
    color: #95a5a6;
    font-size: 13px;
    position: relative;
}

.message {
    margin-bottom: 20px;
    display: flex;
}

.message.sent {
    justify-content: flex-end;
}

.message-content {
    max-width: 70%;
    padding: 12px 15px;
    border-radius: 12px;
    position: relative;
}

.message.received .message-content {
    background: white;
    color: #2c3e50;
    border-bottom-left-radius: 2px;
}

.message.sent .message-content {
    background: #3498db;
    color: white;
    border-bottom-right-radius: 2px;
}

.message-time {
    font-size: 11px;
    margin-top: 5px;
    opacity: 0.7;
}

.message-form {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
}

.message-input-wrapper {
    display: flex;
    gap: 10px;
}

.message-input-wrapper textarea {
    flex: 1;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 10px;
    resize: none;
    height: 40px;
    font-family: inherit;
}

.send-button {
    background: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0 20px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.send-button:hover {
    background: #2980b9;
}

.no-chat-selected,
.no-messages {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
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
    margin-bottom: 20px;
    color: #bdc3c7;
}

.professional-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    text-decoration: none;
    color: inherit;
    background: #f5f7fa;
    transition: background-color 0.2s;
}

.professional-item:hover {
    background: #e3f2fd;
}

.professional-info {
    flex: 1;
}

.professional-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.professional-exp {
    font-size: 13px;
    color: #7f8c8d;
}

@media (max-width: 768px) {
    .messages-container {
        flex-direction: column;
        height: auto;
    }
    
    .conversations-sidebar {
        flex: none;
        height: 300px;
    }
    
    .chat-area {
        height: 500px;
    }
}
</style>

<script>
// Auto-scroll to bottom of messages
function scrollToBottom() {
    const messageList = document.getElementById('messageList');
    if (messageList) {
        messageList.scrollTop = messageList.scrollHeight;
    }
}

// Auto-resize textarea
function autoResizeTextarea() {
    const textarea = document.querySelector('.message-input-wrapper textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    autoResizeTextarea();
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
