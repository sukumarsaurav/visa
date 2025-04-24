<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Load environment variables
$envFile = __DIR__ . '/../../../config/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Helper function to validate conversation ownership
function validateConversation($pdo, $conversationId, $userId) {
    $stmt = $pdo->prepare("SELECT id FROM ai_chat_conversations WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$conversationId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'delete_conversation':
        if (!isset($_POST['conversation_id'])) {
            echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
            exit;
        }
        
        $conversationId = $_POST['conversation_id'];
        if (!validateConversation($pdo, $conversationId, $user_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE ai_chat_conversations SET is_deleted = 1 WHERE id = ?");
        $success = $stmt->execute([$conversationId]);
        echo json_encode(['success' => $success]);
        break;
        
    case 'get_usage':
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM ai_chat_messages 
            WHERE user_id = ? 
            AND role = 'user' 
            AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'usage' => $result['count']]);
        break;
        
    case 'create_conversation':
        $stmt = $pdo->prepare("
            INSERT INTO ai_chat_conversations (user_id, created_at) 
            VALUES (?, NOW())
        ");
        $success = $stmt->execute([$user_id]);
        $conversationId = $pdo->lastInsertId();
        
        // Add initial system message
        $systemMessage = "You are a helpful AI assistant. How can I help you today?";
        $stmt = $pdo->prepare("
            INSERT INTO ai_chat_messages (conversation_id, user_id, role, content, created_at) 
            VALUES (?, ?, 'system', ?, NOW())
        ");
        $stmt->execute([$conversationId, $user_id, $systemMessage]);
        
        echo json_encode(['success' => $success, 'conversation_id' => $conversationId]);
        break;
        
    case 'get_conversation':
        $conversation_id = $_GET['conversation_id'] ?? null;
        
        if (!$conversation_id) {
            echo json_encode(['success' => false, 'message' => 'Conversation ID is required']);
            exit;
        }
        
        // Verify the conversation belongs to the user
        $stmt = $pdo->prepare("SELECT * FROM ai_chat_conversations WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $stmt->execute([$conversation_id, $user_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
            exit;
        }
        
        // Get all messages for the conversation
        $stmt = $pdo->prepare("
            SELECT id, role, content, created_at 
            FROM ai_chat_messages 
            WHERE conversation_id = ? 
            AND is_deleted = 0 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$conversation_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'conversation' => $conversation,
            'messages' => $messages
        ]);
        break;
        
    case 'send_message':
        $conversation_id = $_POST['conversation_id'] ?? null;
        $message = $_POST['message'] ?? '';
        
        if (!$conversation_id || !$message) {
            echo json_encode(['success' => false, 'message' => 'Conversation ID and message are required']);
            exit;
        }
        
        // Verify the conversation exists and belongs to the user
        $stmt = $pdo->prepare("SELECT * FROM ai_chat_conversations WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $stmt->execute([$conversation_id, $user_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
            exit;
        }
        
        // Check monthly message limit
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM ai_chat_messages 
            WHERE user_id = ? 
            AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
            AND role = 'user'
        ");
        $stmt->execute([$user_id]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usage['count'] >= 50) {
            echo json_encode(['success' => false, 'message' => 'Monthly message limit reached']);
            exit;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Save user message
            $stmt = $pdo->prepare("
                INSERT INTO ai_chat_messages (conversation_id, user_id, role, content) 
                VALUES (?, ?, 'user', ?)
            ");
            $stmt->execute([$conversation_id, $user_id, $message]);
            
            // Update conversation title if it's the first message
            if (!$conversation['title']) {
                $title = strlen($message) > 50 ? substr($message, 0, 47) . '...' : $message;
                $stmt = $pdo->prepare("UPDATE ai_chat_conversations SET title = ? WHERE id = ?");
                $stmt->execute([$title, $conversation_id]);
            }
            
            // Prepare conversation history for AI
            $stmt = $pdo->prepare("
                SELECT role, content 
                FROM ai_chat_messages 
                WHERE conversation_id = ? 
                AND is_deleted = 0 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$conversation_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Prepare messages for API
            $messages = [];
            $messages[] = [
                'role' => 'system',
                'content' => 'You are a helpful assistant. Provide clear and concise responses.'
            ];
            
            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
            
            // Call OpenAI API
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $_ENV['OPENAI_API_KEY']
            ]);
            
            $data = [
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1000
            ];
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('API call failed: ' . curl_error($ch));
            }
            
            curl_close($ch);
            $result = json_decode($response, true);
            
            if (!isset($result['choices'][0]['message']['content'])) {
                throw new Exception('Invalid API response');
            }
            
            $ai_response = $result['choices'][0]['message']['content'];
            
            // Save AI response
            $stmt = $pdo->prepare("
                INSERT INTO ai_chat_messages (conversation_id, user_id, role, content) 
                VALUES (?, ?, 'assistant', ?)
            ");
            $stmt->execute([$conversation_id, $user_id, $ai_response]);
            
            // Update usage tracking
            $stmt = $pdo->prepare("
                INSERT INTO ai_chat_usage (user_id, message_count, month, year) 
                VALUES (?, 1, MONTH(CURRENT_DATE()), YEAR(CURRENT_DATE()))
                ON DUPLICATE KEY UPDATE message_count = message_count + 1
            ");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'response' => $ai_response
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error in send_message: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to process message']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?> 