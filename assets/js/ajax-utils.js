/**
 * AJAX Utilities for Member Pages
 * Provides reusable functions for AJAX operations without page reloads
 */

// Show alert message without page reload
function showAlert(message, type = 'info', duration = 3000) {
    // Remove existing auto-dismiss alerts
    const existingAlerts = document.querySelectorAll('.alert-auto-dismiss');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-auto-dismiss`;
    alertDiv.innerHTML = `
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of main content
    const main = document.querySelector('main .container-fluid, main .container');
    if (main) {
        main.insertBefore(alertDiv, main.firstChild);
        
        // Auto-dismiss after duration
        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, duration);
        }
    }
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (text == null) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// AJAX DELETE helper
function ajaxDelete(url, itemId, element, onSuccess) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }
    
    // Show loading state
    const originalHTML = element.innerHTML;
    element.disabled = true;
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Send AJAX request
    fetch(url + '?action=delete&id=' + itemId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(data, element);
            } else {
                // Default: remove the element
                element.closest('.list-group-item, .card, .note-card, tr').remove();
            }
        } else {
            element.disabled = false;
            element.innerHTML = originalHTML;
            showAlert(data.error || 'Failed to delete item', 'danger');
        }
    })
    .catch(error => {
        element.disabled = false;
        element.innerHTML = originalHTML;
        console.error('Error:', error);
        showAlert('An error occurred while deleting the item', 'danger');
    });
}

// AJAX POST helper
function ajaxPost(url, formData, onSuccess, onError) {
    return fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(data);
            }
        } else {
            if (onError && typeof onError === 'function') {
                onError(data);
            } else {
                showAlert(data.error || 'Operation failed', 'danger');
            }
        }
        return data;
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred', 'danger');
        if (onError && typeof onError === 'function') {
            onError({ error: error.message });
        }
    });
}

// Prevent form submission and use AJAX instead
function setupAjaxForm(formId, handlerUrl, onSuccess) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        formData.append('ajax', '1');
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML || submitBtn.value;
            submitBtn.disabled = true;
            if (submitBtn.tagName === 'BUTTON') {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            } else {
                submitBtn.value = 'Processing...';
            }
            
            ajaxPost(handlerUrl, formData, (data) => {
                submitBtn.disabled = false;
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = originalText;
                } else {
                    submitBtn.value = originalText;
                }
                
                showAlert(data.message, 'success');
                if (onSuccess && typeof onSuccess === 'function') {
                    onSuccess(data);
                }
            }, (data) => {
                submitBtn.disabled = false;
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = originalText;
                } else {
                    submitBtn.value = originalText;
                }
            });
        }
    });
}

