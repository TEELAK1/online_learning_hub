<?php
// Simple chat widget component
?>
<div id="chatWidget" class="chat-widget">
    <div class="chat-toggle" onclick="toggleChat()">
        <i class="fas fa-comments"></i>
        <span class="chat-badge" id="chatBadge">0</span>
    </div>
    
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <h6 class="mb-0">
                <i class="fas fa-robot me-2"></i>Learning Assistant
            </h6>
            <button class="btn-close btn-close-white" onclick="toggleChat()"></button>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="message bot-message">
                <div class="message-content">
                    <p>Hello! I'm your learning assistant. How can I help you today?</p>
                    <small class="text-muted">Just now</small>
                </div>
            </div>
        </div>
        
        <div class="chat-input">
            <div class="input-group">
                <input type="text" class="form-control" id="messageInput" placeholder="Type your message..." onkeypress="handleKeyPress(event)">
                <button class="btn btn-primary" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.chat-toggle {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #2563eb, #1e40af);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    transition: all 0.3s ease;
    position: relative;
}

.chat-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
}

.chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc2626;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    display: none;
}

.chat-window {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 350px;
    height: 400px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
}

.chat-header {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 15px;
    display: flex;
    justify-content: between;
    align-items: center;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: #f8f9fa;
}

.message {
    margin-bottom: 15px;
}

.message-content {
    background: white;
    padding: 10px 15px;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.bot-message .message-content {
    background: #e3f2fd;
    border-bottom-left-radius: 4px;
}

.user-message .message-content {
    background: #2563eb;
    color: white;
    margin-left: 50px;
    border-bottom-right-radius: 4px;
}

.chat-input {
    padding: 15px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .chat-window {
        width: 300px;
        height: 350px;
    }
}
</style>

<script>
let chatOpen = false;
let messageCount = 0;

function toggleChat() {
    const chatWindow = document.getElementById('chatWindow');
    const chatToggle = document.querySelector('.chat-toggle');
    
    chatOpen = !chatOpen;
    
    if (chatOpen) {
        chatWindow.style.display = 'flex';
        chatToggle.innerHTML = '<i class="fas fa-times"></i>';
        document.getElementById('messageInput').focus();
    } else {
        chatWindow.style.display = 'none';
        chatToggle.innerHTML = '<i class="fas fa-comments"></i><span class="chat-badge" id="chatBadge">0</span>';
    }
}

function handleKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (message === '') return;
    
    // Add user message
    addMessage(message, 'user');
    input.value = '';
    
    // Simulate bot response
    setTimeout(() => {
        const responses = [
            "I understand you're looking for help with your courses. What specific topic would you like assistance with?",
            "That's a great question! Have you checked the course materials section for additional resources?",
            "For technical issues, please contact your instructor or check the FAQ section.",
            "I recommend reviewing the course content and practicing with the quiz questions.",
            "If you need more help, consider reaching out to your classmates in the discussion forum."
        ];
        
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        addMessage(randomResponse, 'bot');
    }, 1000);
}

function addMessage(text, sender) {
    const messagesContainer = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <p class="mb-1">${text}</p>
            <small class="text-muted">Just now</small>
        </div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    if (sender === 'bot' && !chatOpen) {
        messageCount++;
        const badge = document.getElementById('chatBadge');
        badge.textContent = messageCount;
        badge.style.display = 'flex';
    }
}

// Initialize chat
document.addEventListener('DOMContentLoaded', function() {
    // Add some sample bot messages after a delay
    setTimeout(() => {
        if (!chatOpen) {
            addMessage("Hi! I noticed you're browsing your courses. Need any help?", 'bot');
        }
    }, 5000);
});
</script>
