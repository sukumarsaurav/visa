<?php
require_once '../../../config/db_connect.php';
session_start();

// Get user_id from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

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

// Load the .env file with explicit error checking
$envPath = __DIR__ . '/../../../config/.env';
if (!file_exists($envPath)) {
    error_log("Error: .env file not found at: " . $envPath);
    echo json_encode(['success' => false, 'error' => 'Configuration file not found']);
    exit;
}

loadEnv($envPath);

// Verify API key is loaded
$api_key = $_ENV['OPENAI_API_KEY'] ?? null;
if (!$api_key) {
    error_log("Error: OPENAI_API_KEY not found in environment variables");
    echo json_encode(['success' => false, 'error' => 'API key not configured']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_conversation':
                $conversation_id = (int)$_POST['conversation_id'];
                $sql = "UPDATE ai_chat_conversations SET deleted_at = NOW() 
                        WHERE id = ? AND professional_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $conversation_id, $user_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                }
                break;

            case 'get_usage':
                $month = date('Y-m');
                $sql = "SELECT message_count FROM ai_chat_usage 
                        WHERE professional_id = ? AND month = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $user_id, $month);
                $stmt->execute();
                $result = $stmt->get_result();
                $usage = $result->fetch_assoc();
                $messages_used = $usage ? $usage['message_count'] : 0;
                echo json_encode(['success' => true, 'usage' => $messages_used]);
                break;

            case 'create_conversation':
                $title = "New Chat";
                $chat_type = isset($_POST['chat_type']) ? $_POST['chat_type'] : 'ircc';
                $sql = "INSERT INTO ai_chat_conversations (professional_id, title, chat_type) 
                        VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $title, $chat_type);
                if ($stmt->execute()) {
                    $conversation_id = $conn->insert_id;
                    
                    // Add initial system message
                    $welcome_msg = "Hello! I'm your visa and immigration consultant assistant. How can I help you today?";
                    $sql = "INSERT INTO ai_chat_messages (conversation_id, professional_id, role, content) 
                            VALUES (?, ?, 'assistant', ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iis", $conversation_id, $user_id, $welcome_msg);
                    $stmt->execute();
                    
                    echo json_encode([
                        'success' => true, 
                        'conversation_id' => $conversation_id,
                        'welcome_message' => $welcome_msg
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                }
                break;

            case 'get_conversation':
                $conversation_id = (int)$_POST['conversation_id'];
                
                // Get conversation details
                $sql = "SELECT * FROM ai_chat_conversations 
                        WHERE id = ? AND professional_id = ? AND deleted_at IS NULL";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $conversation_id, $user_id);
                $stmt->execute();
                $conversation = $stmt->get_result()->fetch_assoc();
                
                if (!$conversation) {
                    echo json_encode(['success' => false, 'error' => 'Conversation not found']);
                    break;
                }
                
                // Get messages
                $sql = "SELECT id, role, content, created_at FROM ai_chat_messages 
                        WHERE conversation_id = ? AND professional_id = ? AND deleted_at IS NULL 
                        ORDER BY created_at ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $conversation_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $messages = [];
                while ($row = $result->fetch_assoc()) {
                    $messages[] = [
                        'id' => $row['id'],
                        'role' => $row['role'],
                        'content' => $row['content'],
                        'created_at' => $row['created_at']
                    ];
                }
                
                echo json_encode([
                    'success' => true, 
                    'conversation' => $conversation,
                    'messages' => $messages
                ]);
                break;

            case 'send_message':
                // Check monthly message limit
                $month = date('Y-m');
                $sql = "SELECT message_count FROM ai_chat_usage 
                        WHERE professional_id = ? AND month = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $user_id, $month);
                $stmt->execute();
                $result = $stmt->get_result();
                $usage = $result->fetch_assoc();
                
                if ($usage && $usage['message_count'] >= 50) {
                    echo json_encode(['success' => false, 'error' => 'Monthly message limit (50) reached']);
                    break;
                }

                $message = $_POST['message'] ?? '';
                $conversation_id = (int)$_POST['conversation_id'];

                if (!empty($message)) {
                    // Verify conversation exists and belongs to user
                    $sql = "SELECT id, chat_type FROM ai_chat_conversations 
                            WHERE id = ? AND professional_id = ? AND deleted_at IS NULL";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $conversation_id, $user_id);
                    $stmt->execute();
                    $conversation = $stmt->get_result()->fetch_assoc();
                    
                    if (!$conversation) {
                        echo json_encode(['success' => false, 'error' => 'Invalid conversation']);
                        break;
                    }

                    // Save user message
                    $sql = "INSERT INTO ai_chat_messages (conversation_id, professional_id, role, content) 
                            VALUES (?, ?, 'user', ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iis", $conversation_id, $user_id, $message);
                    if (!$stmt->execute()) {
                        echo json_encode(['success' => false, 'error' => 'Failed to save message']);
                        break;
                    }

                    // Update conversation title with first message
                    $sql = "UPDATE ai_chat_conversations 
                            SET title = ? 
                            WHERE id = ? AND professional_id = ? 
                            AND (title = 'New Chat' OR title LIKE 'New Chat%')";
                    $stmt = $conn->prepare($sql);
                    $short_title = substr($message, 0, 50) . (strlen($message) > 50 ? "..." : "");
                    $stmt->bind_param("sii", $short_title, $conversation_id, $user_id);
                    $stmt->execute();

                    // Get conversation history
                    $sql = "SELECT role, content FROM ai_chat_messages 
                            WHERE conversation_id = ? AND deleted_at IS NULL
                            ORDER BY created_at DESC LIMIT 10";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $conversation_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $history = [];
                    while ($row = $result->fetch_assoc()) {
                        array_unshift($history, [
                            "role" => $row['role'],
                            "content" => $row['content']
                        ]);
                    }

                    // Call OpenAI API with error handling
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $api_key
                    ]);

                    // Enable verbose error reporting
                    curl_setopt($ch, CURLOPT_VERBOSE, true);
                    $verbose = fopen('php://temp', 'w+');
                    curl_setopt($ch, CURLOPT_STDERR, $verbose);

                    // Prepare messages array with system message and history
                    $system_message = $conversation['chat_type'] === 'cases' 
                        ? "You are a legal assistant specializing in Canadian immigration case law. Provide accurate information about immigration cases, precedents, and court decisions. Be precise and professional."
                        : "You are a friendly visa and immigration consultant assistant. Provide accurate, helpful information about Canadian visa processes, IRCC procedures, and immigration requirements. Be clear and professional.";

                    $messages = [["role" => "system", "content" => $system_message]];
                    $messages = array_merge($messages, $history);

                    $data = [
                        'model' => 'gpt-3.5-turbo',
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 500
                    ];

                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    $response = curl_exec($ch);
                    
                    if ($response === false) {
                        rewind($verbose);
                        $verboseLog = stream_get_contents($verbose);
                        error_log("cURL Error: " . curl_error($ch) . "\n" . $verboseLog);
                        echo json_encode([
                            'success' => false, 
                            'error' => 'Could not connect to the OpenAI API',
                            'details' => curl_error($ch)
                        ]);
                        curl_close($ch);
                        break;
                    }

                    $result = json_decode($response, true);
                    curl_close($ch);

                    if (isset($result['error'])) {
                        error_log("OpenAI API Error: " . json_encode($result['error']));
                        echo json_encode([
                            'success' => false, 
                            'error' => $result['error']['message'] ?? 'An unknown error occurred'
                        ]);
                        break;
                    }

                    if (isset($result['choices'][0]['message']['content'])) {
                        $ai_response = $result['choices'][0]['message']['content'];
                        
                        // Save AI response
                        $sql = "INSERT INTO ai_chat_messages (conversation_id, professional_id, role, content) 
                                VALUES (?, ?, 'assistant', ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iis", $conversation_id, $user_id, $ai_response);
                        if ($stmt->execute()) {
                            // Update usage
                            $sql = "INSERT INTO ai_chat_usage (professional_id, month, message_count) 
                                    VALUES (?, ?, 1)
                                    ON DUPLICATE KEY UPDATE message_count = message_count + 1";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("is", $user_id, $month);
                            $stmt->execute();

                            echo json_encode(['success' => true, 'message' => $ai_response]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'Failed to save AI response']);
                        }
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'error' => 'Invalid response format from AI service',
                            'response' => $response
                        ]);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    }
}
?> 