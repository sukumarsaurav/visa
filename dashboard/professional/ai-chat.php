<?php
// Load environment variables
function loadEnv($path) {
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
}

// Load the .env file
loadEnv(__DIR__ . '/../../config/.env');

session_start();

// Include database connection
require_once '../../config/db_connect.php';
$db = $conn;

// Assuming user is logged in and we have their professional_id
$professional_id = $_SESSION['user_id'] ?? 1; // Replace with actual session handling

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'get_usage':
                $month = date('Y-m');
                $sql = "SELECT message_count FROM ai_chat_usage WHERE professional_id = $professional_id AND month = '$month'";
                $result = $db->query($sql);
                $usage = $result->fetch_assoc();
                $messages_used = $usage ? $usage['message_count'] : 0;
                echo json_encode(['success' => true, 'usage' => $messages_used]);
                exit;

            case 'create_conversation':
                $title = $db->real_escape_string($_POST['title']);
                $chat_type = $db->real_escape_string($_POST['chat_type']);
                $sql = "INSERT INTO ai_chat_conversations (professional_id, title, chat_type) VALUES ($professional_id, '$title', '$chat_type')";
                if ($db->query($sql)) {
                    echo json_encode(['success' => true, 'conversation_id' => $db->insert_id]);
                } else {
                    echo json_encode(['success' => false, 'error' => $db->error]);
                }
                exit;

            case 'get_conversation':
                $conversation_id = (int)$_POST['conversation_id'];
                $sql = "SELECT * FROM ai_chat_messages WHERE conversation_id = $conversation_id ORDER BY created_at";
                $result = $db->query($sql);
                $messages = [];
                while ($row = $result->fetch_assoc()) {
                    $messages[] = $row;
                }
                echo json_encode(['success' => true, 'messages' => $messages]);
                exit;

            case 'send_message':
                // Check monthly message limit
                $month = date('Y-m');
                $sql = "SELECT message_count FROM ai_chat_usage WHERE professional_id = $professional_id AND month = '$month'";
                $result = $db->query($sql);
                $usage = $result->fetch_assoc();
                
                if ($usage && $usage['message_count'] >= 50) {
                    echo json_encode(['success' => false, 'error' => 'Monthly message limit (50) reached']);
                    exit;
                }

                $message = $_POST['message'] ?? '';
                $conversation_id = (int)$_POST['conversation_id'];
                $api_key = $_ENV['OPENAI_API_KEY'] ?? null;

                if (!empty($message) && $api_key) {
                    // Save user message
                    $content = $db->real_escape_string($message);
                    $sql = "INSERT INTO ai_chat_messages (conversation_id, professional_id, role, content) 
                           VALUES ($conversation_id, $professional_id, 'user', '$content')";
                    $db->query($sql);

                    // Call OpenAI API
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $api_key
                    ]);

                    $data = [
                        'model' => 'gpt-3.5-turbo',
                        'messages' => [
                            ['role' => 'user', 'content' => $message]
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 150
                    ];

                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $result = json_decode($response, true);
                    if (isset($result['choices'][0]['message']['content'])) {
                        $ai_response = $result['choices'][0]['message']['content'];
                        
                        // Save AI response
                        $content = $db->real_escape_string($ai_response);
                        $sql = "INSERT INTO ai_chat_messages (conversation_id, professional_id, role, content) 
                               VALUES ($conversation_id, $professional_id, 'assistant', '$content')";
                        $db->query($sql);

                        // Update usage
                        $sql = "INSERT INTO ai_chat_usage (professional_id, month, message_count) 
                               VALUES ($professional_id, '$month', 1)
                               ON DUPLICATE KEY UPDATE message_count = message_count + 1";
                        $db->query($sql);

                        echo json_encode(['success' => true, 'message' => $ai_response]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to get response from AI']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid message or API key']);
                }
                exit;
        }
    }
}

// Get existing conversations
$sql = "SELECT * FROM ai_chat_conversations WHERE professional_id = $professional_id AND deleted_at IS NULL ORDER BY updated_at DESC";
$conversations = $db->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Assistant</title>
    <link rel="stylesheet" href="assets/css/chatbot.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <div class="new-chat-section">
                <button id="new-chat-btn">New Chat</button>
                <div class="chat-type-selector" style="display: none;">
                    <button class="chat-type-btn" data-type="ircc">IRCC Chat</button>
                    <button class="chat-type-btn" data-type="cases">Cases Chat</button>
                </div>
            </div>
            <div class="conversations-list">
                <?php while ($conv = $conversations->fetch_assoc()): ?>
                <div class="conversation-item" data-id="<?php echo $conv['id']; ?>">
                    <?php echo htmlspecialchars($conv['title']); ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <h2>AI Assistant</h2>
                <div class="usage-info">
                    <?php
                    $month = date('Y-m');
                    $sql = "SELECT message_count FROM ai_chat_usage WHERE professional_id = $professional_id AND month = '$month'";
                    $result = $db->query($sql);
                    $usage = $result->fetch_assoc();
                    $messages_used = $usage ? $usage['message_count'] : 0;
                    ?>
                    <span id="usage-counter">Messages this month: <?php echo $messages_used; ?>/50</span>
                </div>
            </div>
            <div class="chat-messages" id="chat-messages">
                <div class="message bot">
                    Hello! How can I help you today?
                </div>
            </div>
            <div class="chat-input">
                <input type="text" id="user-input" placeholder="Type your message...">
                <button id="send-button">Send</button>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;

        $(document).ready(function() {
            function updateUsageCounter() {
                $.post('ai-chat.php', { action: 'get_usage' })
                    .done(function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            $('#usage-counter').text(`Messages this month: ${data.usage}/50`);
                        }
                    });
            }

            function setActiveConversation(conversationId) {
                currentConversationId = conversationId;
                $('.conversation-item').removeClass('active');
                $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');
            }

            // New Chat button handling
            $('#new-chat-btn').click(function() {
                $('.chat-type-selector').toggle();
            });

            // Chat type selection
            $('.chat-type-btn').click(function() {
                const chatType = $(this).data('type');
                const title = 'New Chat ' + new Date().toLocaleString();
                
                $.post('ai-chat.php', {
                    action: 'create_conversation',
                    title: title,
                    chat_type: chatType
                })
                .done(function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Add new conversation to list
                        $('.conversations-list').prepend(`
                            <div class="conversation-item" data-id="${data.conversation_id}">
                                ${title}
                            </div>
                        `);
                        // Select new conversation
                        setActiveConversation(data.conversation_id);
                        $('#chat-messages').empty().append(`
                            <div class="message bot">
                                Hello! How can I help you today?
                            </div>
                        `);
                        $('.chat-type-selector').hide();
                    }
                });
            });

            // Conversation selection
            $(document).on('click', '.conversation-item', function() {
                const conversationId = $(this).data('id');
                if (currentConversationId === conversationId) return; // Don't reload if already active
                
                setActiveConversation(conversationId);
                
                // Load conversation messages
                $.post('ai-chat.php', {
                    action: 'get_conversation',
                    conversation_id: conversationId
                })
                .done(function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        $('#chat-messages').empty();
                        data.messages.forEach(function(msg) {
                            const messageClass = msg.role === 'user' ? 'user' : 'bot';
                            $('#chat-messages').append(`
                                <div class="message ${messageClass}">
                                    ${msg.content}
                                </div>
                            `);
                        });
                        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                    }
                });
            });

            function sendMessage() {
                if (!currentConversationId) {
                    alert('Please select or create a conversation first');
                    return;
                }

                const message = $('#user-input').val().trim();
                if (message) {
                    // Add user message
                    $('#chat-messages').append(`
                        <div class="message user">
                            ${message}
                        </div>
                    `);
                    
                    // Clear input
                    $('#user-input').val('');
                    
                    // Show typing indicator
                    $('#chat-messages').append(`
                        <div class="message bot typing">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    `);

                    // Scroll to bottom
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);

                    // Send to PHP
                    $.post('ai-chat.php', {
                        action: 'send_message',
                        message: message,
                        conversation_id: currentConversationId
                    })
                    .done(function(response) {
                        const data = JSON.parse(response);
                        // Remove typing indicator
                        $('.typing').remove();
                        
                        if (data.success) {
                            // Add bot response
                            $('#chat-messages').append(`
                                <div class="message bot">
                                    ${data.message}
                                </div>
                            `);
                            // Update usage counter without page reload
                            updateUsageCounter();
                        } else {
                            // Show error message
                            $('#chat-messages').append(`
                                <div class="message bot">
                                    ${data.error}
                                </div>
                            `);
                        }
                        // Scroll to bottom
                        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                    })
                    .fail(function() {
                        // Remove typing indicator
                        $('.typing').remove();
                        
                        // Show error message
                        $('#chat-messages').append(`
                            <div class="message bot">
                                Sorry, there was an error processing your request. Please try again.
                            </div>
                        `);
                        // Scroll to bottom
                        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                    });
                }
            }

            $('#send-button').click(sendMessage);
            $('#user-input').keypress(function(e) {
                if (e.which == 13) {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>
