<?php
// Set page variables
$page_title = "AI Chat";
$page_header = "Visafy AI Chat";

// Include header
include_once('includes/header.php');

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
}

.history-header h3 {
    margin: 0;
    color: #2c3e50;
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
}

.chat-item p {
    margin: 0;
    font-size: 0.8rem;
    color: #666;
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

.chat-header h3 {
    margin: 0;
    color: #2c3e50;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.message {
    margin-bottom: 16px;
    max-width: 80%;
}

.message.user {
    margin-left: auto;
}

.message-content {
    padding: 12px 16px;
    border-radius: 12px;
    background: #f8f9fa;
}

.message.user .message-content {
    background: #4a6fdc;
    color: white;
}

.chat-input {
    padding: 16px;
    border-top: 1px solid #eee;
}

.chat-input form {
    display: flex;
    gap: 12px;
}

.chat-input input {
    flex: 1;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.chat-input button {
    padding: 12px 24px;
    background: #4a6fdc;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.chat-input button:hover {
    background: #3d5cbe;
}
';

// Add page specific JavaScript
$page_js = '
function startChat(type) {
    // Hide options container and show chat interface
    document.querySelector(".ai-options-container").style.display = "none";
    document.querySelector(".chat-interface").classList.add("active");
    
    // Update chat header based on type
    const header = document.querySelector(".chat-header h3");
    if (type === "ircc") {
        header.innerHTML = "IRCC Rules and Regulations Chat";
    } else {
        header.innerHTML = "Case Laws Chat <span class=\'beta-badge\'>Beta</span>";
    }
    
    // Focus on input
    document.querySelector(".chat-input input").focus();
}

function sendMessage(event) {
    event.preventDefault();
    const input = document.querySelector(".chat-input input");
    const message = input.value.trim();
    
    if (message) {
        // Add message to chat
        const chatMessages = document.querySelector(".chat-messages");
        chatMessages.innerHTML += `
            <div class="message user">
                <div class="message-content">${message}</div>
            </div>
        `;
        
        // Clear input
        input.value = "";
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // TODO: Add AI response handling here
    }
}
';
?>

<div class="ai-chat-container">
    <!-- Chat History Sidebar -->
    <div class="chat-history-sidebar">
        <div class="history-header">
            <h3>Chat History</h3>
        </div>
        <div class="chat-list">
            <div class="chat-item active">
                <h4>IRCC Regulations Research</h4>
                <p>Last chat: 2 hours ago</p>
            </div>
            <div class="chat-item">
                <h4>Case Law Analysis</h4>
                <p>Last chat: Yesterday</p>
            </div>
            <div class="chat-item">
                <h4>Immigration Policy</h4>
                <p>Last chat: 3 days ago</p>
            </div>
        </div>
    </div>

    <!-- Main Chat Content -->
    <div class="chat-main-content">
        <!-- Options Container -->
        <div class="ai-options-container">
            <!-- IRCC Rules Card -->
            <div class="ai-card" onclick="startChat('ircc')">
                <h3>IRCC Rules and Regulations</h3>
                <p>Research general Canadian immigration information</p>
                <button class="button">Start Chat</button>
            </div>
            
            <!-- Case Laws Card -->
            <div class="ai-card" onclick="startChat('cases')">
                <h3>Case Laws <span class="beta-badge">Beta Feature</span></h3>
                <p>Research Canadian immigration cases (from The Federal Court, Court of Appeal, etc.)</p>
                <button class="button">Start Chat</button>
            </div>
        </div>

        <!-- Chat Interface -->
        <div class="chat-interface">
            <div class="chat-header">
                <h3>Chat</h3>
                <button onclick="document.querySelector('.chat-interface').classList.remove('active'); document.querySelector('.ai-options-container').style.display = 'flex';" class="button button-secondary">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chat-messages">
                <!-- Messages will be added here dynamically -->
            </div>
            <div class="chat-input">
                <form onsubmit="sendMessage(event)">
                    <input type="text" placeholder="Type your message here..." required>
                    <button type="submit">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once('includes/footer.php');
?> 