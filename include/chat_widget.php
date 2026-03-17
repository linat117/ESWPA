<?php
/**
 * Telegram Chat Widget Component
 * Displays a floating chat button and chat widget modal
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */
?>

<!-- Telegram Chat Widget -->
<div id="telegramChatWidget">
    <!-- Floating Chat Button -->
    <button id="telegramChatButton" class="telegram-chat-button" aria-label="Open chat">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.568 8.16l-1.44 6.8c-.108.48-.384.6-.78.375l-2.15-1.584-1.036.996c-.12.12-.22.22-.45.22l.16-2.274 3.984-3.6c.174-.156-.038-.24-.27-.084l-4.92 3.096-2.124-.66c-.462-.144-.474-.462.096-.684l8.316-3.204c.384-.144.72.096.6.684z" fill="currentColor"/>
        </svg>
        <span class="pulse-ring"></span>
    </button>

    <!-- Chat Widget Modal -->
    <div id="telegramChatModal" class="telegram-chat-modal" style="display: none;">
        <div class="telegram-chat-header">
            <div class="chat-header-info">
                <div class="chat-header-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.568 8.16l-1.44 6.8c-.108.48-.384.6-.78.375l-2.15-1.584-1.036.996c-.12.12-.22.22-.45.22l.16-2.274 3.984-3.6c.174-.156-.038-.24-.27-.084l-4.92 3.096-2.124-.66c-.462-.144-.474-.462.096-.684l8.316-3.204c.384-.144.72.096.6.684z" fill="currentColor"/>
                    </svg>
                </div>
                <h3>Chat with Us</h3>
            </div>
            <button class="chat-close-btn" id="closeChatModal" aria-label="Close chat">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="telegram-chat-body">
            <form id="telegramChatForm" class="telegram-chat-form">
                <!-- Honeypot field for spam protection -->
                <input type="text" name="website" id="chatWebsite" style="display: none;" tabindex="-1" autocomplete="off">
                
                <div class="form-row-compact">
                    <div class="form-group-compact">
                        <input type="text" id="chatName" name="name" placeholder="Name (Optional)" autocomplete="name">
                    </div>
                    <div class="form-group-compact">
                        <input type="email" id="chatEmail" name="email" placeholder="Email (Optional)" autocomplete="email">
                        <span class="error-message" id="chatEmailError"></span>
                    </div>
                </div>
                
                <div class="form-group-compact">
                    <input type="tel" id="chatPhone" name="phone" placeholder="Phone (Optional)" autocomplete="tel">
                </div>
                
                <div class="form-group-compact">
                    <textarea id="chatMessage" name="message" rows="3" required placeholder="Your message *" maxlength="1000"></textarea>
                    <span class="error-message" id="chatMessageError"></span>
                    <small class="char-count"><span id="charCount">0</span>/1000</small>
                </div>
                
                <button type="submit" class="chat-send-btn" id="chatSendBtn">
                    <span class="btn-text">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </span>
                    <span class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Sending...
                    </span>
                </button>
                
                <div class="chat-message" id="chatMessageDiv"></div>
            </form>
        </div>
    </div>
</div>

<style>
/* Telegram Chat Button Styles */
.telegram-chat-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
    z-index: 9998;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

/* Hide floating button on desktop (web view) - show only on mobile */
@media (min-width: 768px) {
    .telegram-chat-button {
        display: none;
    }
}

.telegram-chat-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(56, 189, 248, 0.6);
}

.telegram-chat-button svg {
    width: 28px;
    height: 28px;
}

.pulse-ring {
    position: absolute;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(14, 165, 233, 0.4);
    animation: pulse-ring 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes pulse-ring {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

/* Chat Modal Styles */
.telegram-chat-modal {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 360px;
    height: auto;
    max-height: 420px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;
    overflow: hidden;
}

/* Adjust modal position when opened from header (web view) */
@media (min-width: 768px) {
    .telegram-chat-modal {
        bottom: 80px;
        right: 20px;
    }
}

.telegram-chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.chat-header-icon {
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.chat-header-info h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    line-height: 1.2;
}

.chat-header-info p {
    display: none;
}

.chat-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
    font-size: 14px;
}

.chat-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.telegram-chat-body {
    padding: 14px;
    overflow: visible;
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
}

.telegram-chat-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    min-height: 0;
}

.form-row-compact {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.form-group-compact {
    display: flex;
    flex-direction: column;
}

.form-group-compact input,
.form-group-compact textarea {
    padding: 8px 10px;
    border: 1.5px solid #e0e0e0;
    border-radius: 6px;
    font-size: 13px;
    transition: all 0.2s ease;
    font-family: inherit;
    width: 100%;
    box-sizing: border-box;
}

.form-group-compact input:focus,
.form-group-compact textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

.form-group-compact textarea {
    resize: none;
    min-height: 70px;
    max-height: 70px;
}

.error-message {
    color: #e74c3c;
    font-size: 11px;
    margin-top: 2px;
    min-height: 14px;
    line-height: 1.2;
}

.char-count {
    text-align: right;
    font-size: 11px;
    color: #999;
    margin-top: 2px;
}

.chat-send-btn {
    padding: 10px 16px;
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    position: relative;
    min-height: 40px;
    margin-top: 4px;
    flex-shrink: 0;
}

.chat-send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(14, 165, 233, 0.45);
}

.chat-send-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-loader {
    position: absolute;
}

.chat-message {
    margin-top: 6px;
    padding: 8px 10px;
    border-radius: 6px;
    text-align: center;
    font-size: 12px;
    display: none;
    line-height: 1.4;
    flex-shrink: 0;
}

.chat-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    display: block;
}

.chat-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    display: block;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .telegram-chat-button {
        display: flex;
    }
    
    .telegram-chat-modal {
        width: calc(100% - 30px);
        right: 15px;
        left: 15px;
        bottom: 85px;
        max-height: calc(100vh - 110px);
        border-radius: 12px;
    }
    
    .telegram-chat-header {
        padding: 10px 14px;
    }
    
    .chat-header-info h3 {
        font-size: 15px;
    }
    
    .chat-header-icon {
        width: 24px;
        height: 24px;
    }
    
    .chat-close-btn {
        width: 24px;
        height: 24px;
        font-size: 12px;
    }
    
    .telegram-chat-body {
        padding: 12px;
    }
    
    .telegram-chat-form {
        gap: 6px;
    }
    
    .form-row-compact {
        gap: 6px;
    }
    
    .form-group-compact input,
    .form-group-compact textarea {
        padding: 7px 9px;
        font-size: 13px;
    }
    
    .form-group-compact textarea {
        min-height: 65px;
        max-height: 65px;
    }
    
    .chat-send-btn {
        padding: 9px 14px;
        font-size: 13px;
        min-height: 38px;
    }
    
    .telegram-chat-button {
        bottom: 115px;
        right: 15px;
        width: 56px;
        height: 56px;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    // Chat elements
    const chatButton = document.getElementById('telegramChatButton');
    const chatModal = document.getElementById('telegramChatModal');
    const closeBtn = document.getElementById('closeChatModal');
    const chatForm = document.getElementById('telegramChatForm');
    const sendBtn = document.getElementById('chatSendBtn');
    const messageDiv = document.getElementById('chatMessageDiv');
    const charCount = document.getElementById('charCount');
    const messageTextarea = document.getElementById('chatMessage');
    
    // Character counter
    if (messageTextarea && charCount) {
        messageTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
    
    // Show chat modal
    function showChatModal() {
        if (chatModal) {
            chatModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Hide chat modal
    function hideChatModal() {
        if (chatModal) {
            chatModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    
    // Expose functions globally for header button
    window.showTelegramChat = showChatModal;
    window.hideTelegramChat = hideChatModal;
    
    // Chat button click
    if (chatButton) {
        chatButton.addEventListener('click', function() {
            showChatModal();
        });
    }
    
    // Close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            hideChatModal();
        });
    }
    
    // Close on overlay click (if needed)
    if (chatModal) {
        chatModal.addEventListener('click', function(e) {
            if (e.target === chatModal) {
                hideChatModal();
            }
        });
    }
    
    // Form submission
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset messages
            messageDiv.className = 'chat-message';
            messageDiv.style.display = 'none';
            document.getElementById('chatEmailError').textContent = '';
            document.getElementById('chatMessageError').textContent = '';
            
            // Get form data
            const name = document.getElementById('chatName').value.trim();
            const email = document.getElementById('chatEmail').value.trim();
            const phone = document.getElementById('chatPhone').value.trim();
            const message = document.getElementById('chatMessage').value.trim();
            
            // Validation
            let isValid = true;
            
            if (!message) {
                document.getElementById('chatMessageError').textContent = 'Message is required';
                isValid = false;
            }
            
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('chatEmailError').textContent = 'Please enter a valid email address';
                isValid = false;
            }
            
            if (!isValid) {
                return;
            }
            
            // Disable submit button
            sendBtn.disabled = true;
            sendBtn.querySelector('.btn-text').style.display = 'none';
            sendBtn.querySelector('.btn-loader').style.display = 'flex';
            
            // Prepare data
            const formData = {
                name: name,
                email: email,
                phone: phone,
                message: message,
                website: document.getElementById('chatWebsite').value // Honeypot
            };
            
            // Send AJAX request
            fetch('include/telegram_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'chat-message success';
                    messageDiv.textContent = data.message || 'Message sent successfully! We will get back to you soon.';
                    messageDiv.style.display = 'block';
                    
                    // Reset form
                    chatForm.reset();
                    charCount.textContent = '0';
                    
                    // Hide modal after 3 seconds
                    setTimeout(function() {
                        hideChatModal();
                    }, 3000);
                } else {
                    messageDiv.className = 'chat-message error';
                    messageDiv.textContent = data.message || 'Failed to send message. Please try again.';
                    messageDiv.style.display = 'block';
                }
            })
            .catch(error => {
                messageDiv.className = 'chat-message error';
                messageDiv.textContent = 'An error occurred. Please try again later.';
                messageDiv.style.display = 'block';
            })
            .finally(() => {
                // Re-enable submit button
                sendBtn.disabled = false;
                sendBtn.querySelector('.btn-text').style.display = 'flex';
                sendBtn.querySelector('.btn-loader').style.display = 'none';
            });
        });
    }
})();
</script>

