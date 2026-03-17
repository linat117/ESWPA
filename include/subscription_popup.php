<?php
/**
 * Email Subscription Popup Component
 * Displays a modal popup for email subscription
 * 
 * Agent: agent_dev_1
 * Date: 2025-12-16
 */
?>

<!-- Email Subscription Popup Modal -->
<div id="subscriptionPopup" class="subscription-popup-overlay" style="display: none; pointer-events: none;">
    <div class="subscription-popup-container">
        <button class="subscription-popup-close" id="closeSubscriptionPopup" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="subscription-popup-content">
            <div class="subscription-popup-header">
                <div class="subscription-popup-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h2>Stay Connected</h2>
                <p>Subscribe to our newsletter and never miss an update!</p>
            </div>
            
            <form id="subscriptionForm" class="subscription-form">
                <!-- Honeypot field for spam protection -->
                <input type="text" name="website" id="website" style="display: none;" tabindex="-1" autocomplete="off">
                
                <div class="form-group">
                    <label for="subscriberEmail">Email Address <span class="required">*</span></label>
                    <input type="email" id="subscriberEmail" name="email" required 
                           placeholder="your.email@example.com" autocomplete="email">
                    <span class="error-message" id="emailError"></span>
                </div>
                
                <div class="form-group">
                    <label for="subscriberName">Name (Optional)</label>
                    <input type="text" id="subscriberName" name="name" 
                           placeholder="Your name" autocomplete="name">
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="privacyCheck" name="privacy" required>
                        <span>I agree to receive newsletters and updates. <a href="#" target="_blank">Privacy Policy</a></span>
                    </label>
                    <span class="error-message" id="privacyError"></span>
                </div>
                
                <button type="submit" class="subscription-submit-btn" id="subscriptionSubmitBtn">
                    <span class="btn-text">Subscribe</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
                
                <div class="subscription-message" id="subscriptionMessage"></div>
            </form>
        </div>
    </div>
</div>

<style>
/* Subscription Popup Styles */
.subscription-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: none; /* default hidden; JS will control showing if enabled */
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.subscription-popup-container {
    background: white;
    border-radius: 15px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

.subscription-popup-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
    z-index: 10;
}

.subscription-popup-close:hover {
    background: #f0f0f0;
    color: #333;
}

.subscription-popup-content {
    padding: 40px 30px;
}

.subscription-popup-header {
    text-align: center;
    margin-bottom: 30px;
}

.subscription-popup-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
}

.subscription-popup-header h2 {
    margin: 0 0 10px;
    color: #333;
    font-size: 28px;
    font-weight: 600;
}

.subscription-popup-header p {
    color: #666;
    margin: 0;
    font-size: 16px;
}

.subscription-form {
    margin-top: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-group .required {
    color: #e74c3c;
}

.form-group input[type="email"],
.form-group input[type="text"] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-group input[type="email"]:focus,
.form-group input[type="text"]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.error-message {
    display: block;
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
    min-height: 18px;
}

.checkbox-group {
    margin-bottom: 25px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 3px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-label span {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
}

.checkbox-label a {
    color: #667eea;
    text-decoration: none;
}

.checkbox-label a:hover {
    text-decoration: underline;
}

.subscription-submit-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    min-height: 50px;
}

.subscription-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.subscription-submit-btn:active {
    transform: translateY(0);
}

.subscription-submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-loader {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.subscription-message {
    margin-top: 15px;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    font-size: 14px;
    display: none;
}

.subscription-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    display: block;
}

.subscription-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    display: block;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .subscription-popup-container {
        width: 95%;
        margin: 20px;
    }
    
    .subscription-popup-content {
        padding: 30px 20px;
    }
    
    .subscription-popup-header h2 {
        font-size: 24px;
    }
    
    .subscription-popup-icon {
        width: 60px;
        height: 60px;
        font-size: 28px;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    // Cookie helper functions
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }
    
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    // Popup elements
    const popup = document.getElementById('subscriptionPopup');
    const closeBtn = document.getElementById('closeSubscriptionPopup');
    const form = document.getElementById('subscriptionForm');
    const submitBtn = document.getElementById('subscriptionSubmitBtn');
    const messageDiv = document.getElementById('subscriptionMessage');
    
    // Show popup function
    function showPopup() {
        if (popup) {
            popup.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Hide popup function
    function hidePopup() {
        if (popup) {
            popup.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    
    // Close button event
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            hidePopup();
            setCookie('subscription_popup_shown', 'true', 7); // Don't show for 7 days
        });
    }
    
    // Close on overlay click
    if (popup) {
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                hidePopup();
                setCookie('subscription_popup_shown', 'true', 7);
            }
        });
    }
    
    // Form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset messages
            messageDiv.className = 'subscription-message';
            messageDiv.style.display = 'none';
            document.getElementById('emailError').textContent = '';
            document.getElementById('privacyError').textContent = '';
            
            // Get form data
            const email = document.getElementById('subscriberEmail').value.trim();
            const name = document.getElementById('subscriberName').value.trim();
            const privacy = document.getElementById('privacyCheck').checked;
            
            // Validation
            let isValid = true;
            
            if (!email) {
                document.getElementById('emailError').textContent = 'Email is required';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('emailError').textContent = 'Please enter a valid email address';
                isValid = false;
            }
            
            if (!privacy) {
                document.getElementById('privacyError').textContent = 'You must agree to the privacy policy';
                isValid = false;
            }
            
            if (!isValid) {
                return;
            }
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.querySelector('.btn-text').style.display = 'none';
            submitBtn.querySelector('.btn-loader').style.display = 'block';
            
            // Prepare data
            const formData = {
                email: email,
                name: name,
                source: 'popup',
                website: document.getElementById('website').value // Honeypot
            };
            
            // Send AJAX request
            fetch('include/subscribe_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'subscription-message success';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                    
                    // Reset form
                    form.reset();
                    
                    // Hide popup after 3 seconds
                    setTimeout(function() {
                        hidePopup();
                        setCookie('subscription_popup_shown', 'true', 7);
                    }, 3000);
                } else {
                    messageDiv.className = 'subscription-message error';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                }
            })
            .catch(error => {
                messageDiv.className = 'subscription-message error';
                messageDiv.textContent = 'An error occurred. Please try again later.';
                messageDiv.style.display = 'block';
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.querySelector('.btn-text').style.display = 'block';
                submitBtn.querySelector('.btn-loader').style.display = 'none';
            });
        });
    }
    
    // Show popup on page load (if not shown recently)
    window.addEventListener('load', function() {
        const popupShown = getCookie('subscription_popup_shown');
        
        if (!popupShown) {
            // Show popup after 5 seconds
            setTimeout(function() {
                showPopup();
            }, 5000);
        }
    });
    
    // Allow manual trigger (for testing or other triggers)
    window.showSubscriptionPopup = showPopup;
    window.hideSubscriptionPopup = hidePopup;
})();
</script>

