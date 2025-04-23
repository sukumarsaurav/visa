<?php
// Set page variables
$page_title = "AI Document Creation";
$page_header = "Create Document Template";

// Include header
include_once('includes/header.php');

// Add page specific CSS
$page_specific_css = '
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
    margin-bottom: 12px;
}

.ai-card .button-secondary {
    background-color: #ecf0f1;
    color: #2c3e50;
}

.ai-card .button-secondary:hover {
    background-color: #bdc3c7;
}

.ai-icon {
    font-size: 2rem;
    color: #4a6fdc;
    margin-bottom: 16px;
}
';
?>

<div class="container">
    <h1 class="page-title"><?php echo $page_header; ?></h1>
    <p class="section-description">Create custom document templates and auto-fill with your clients information.</p>
    
    <div class="ai-options-container">
        <!-- Use Template Card -->
        <div class="ai-card">
            <i class="fas fa-file-alt ai-icon"></i>
            <h3>Use a Template</h3>
            <p>Use your own document template or start typing one from scratch</p>
            <a href="document-template.php" class="button button-secondary">Continue</a>
        </div>
        
        <!-- AI Creation Card -->
        <div class="ai-card">
            <i class="fas fa-robot ai-icon"></i>
            <h3>Create with Visto Copilot AI</h3>
            <p>Generate a document in seconds using our Visto Copilot AI</p>
            <a href="ai-document-create.php" class="button">Create Document</a>
            <small class="text-muted">Powered by Visto CopilotVistoBot</small>
        </div>
    </div>
</div>

<?php
// Include footer
include_once('includes/footer.php');
?> 