/**
 * Design System JavaScript - UI/UX Enhancements
 * Provides helper functions for loading states, animations, and UI feedback
 */

(function() {
  'use strict';

  /**
   * Loading States Management
   */
  const LoadingStates = {
    /**
     * Show loading overlay
     * @param {string} message - Optional loading message
     * @param {boolean} dark - Use dark overlay (default: false)
     */
    show: function(message, dark = false) {
      const overlay = document.createElement('div');
      overlay.className = `ds-loading-overlay ${dark ? 'ds-loading-overlay-dark' : ''}`;
      overlay.id = 'ds-loading-overlay';
      overlay.innerHTML = `
        <div class="ds-loading-content">
          <div class="ds-spinner ds-loading-spinner"></div>
          ${message ? `<div class="ds-loading-text">${message}</div>` : ''}
        </div>
      `;
      document.body.appendChild(overlay);
      document.body.style.overflow = 'hidden';
    },

    /**
     * Hide loading overlay
     */
    hide: function() {
      const overlay = document.getElementById('ds-loading-overlay');
      if (overlay) {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 300ms ease-in-out';
        setTimeout(() => {
          overlay.remove();
          document.body.style.overflow = '';
        }, 300);
      }
    },

    /**
     * Show button loading state
     * @param {HTMLElement} button - Button element
     * @param {string} originalText - Original button text
     */
    showButtonLoading: function(button, originalText) {
      button.disabled = true;
      button.dataset.originalText = originalText || button.innerHTML;
      button.innerHTML = `<span class="ds-spinner ds-spinner-sm"></span> Loading...`;
    },

    /**
     * Hide button loading state
     * @param {HTMLElement} button - Button element
     */
    hideButtonLoading: function(button) {
      button.disabled = false;
      button.innerHTML = button.dataset.originalText || 'Submit';
      delete button.dataset.originalText;
    }
  };

  /**
   * Toast Notifications
   */
  const Toast = {
    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds (default: 5000)
     */
    show: function(message, type = 'info', duration = 5000) {
      const toast = document.createElement('div');
      toast.className = `alert alert-${type} alert-dismissible fade show ds-toast`;
      toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        max-width: 400px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        animation: ds-slide-down 0.3s ease-out;
      `;
      toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      
      document.body.appendChild(toast);
      
      if (duration > 0) {
        setTimeout(() => {
          toast.style.opacity = '0';
          setTimeout(() => toast.remove(), 300);
        }, duration);
      }
    },

    success: function(message, duration) {
      this.show(message, 'success', duration);
    },

    error: function(message, duration) {
      this.show(message, 'danger', duration);
    },

    warning: function(message, duration) {
      this.show(message, 'warning', duration);
    },

    info: function(message, duration) {
      this.show(message, 'info', duration);
    }
  };

  /**
   * Smooth Scroll to Element
   */
  const scrollTo = function(element, offset = 0) {
    const target = typeof element === 'string' ? document.querySelector(element) : element;
    if (target) {
      const targetPosition = target.offsetTop - offset;
      window.scrollTo({
        top: targetPosition,
        behavior: 'smooth'
      });
    }
  };

  /**
   * Form Validation Enhancement
   */
  const FormValidation = {
    /**
     * Validate form fields on blur
     */
    init: function() {
      const forms = document.querySelectorAll('.needs-validation');
      forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
          input.addEventListener('blur', function() {
            this.checkValidity();
          });
          
          input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
              this.classList.remove('is-invalid');
            }
          });
        });

        form.addEventListener('submit', function(e) {
          if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            
            // Focus first invalid field
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
              firstInvalid.focus();
              scrollTo(firstInvalid, 100);
            }
          }
          form.classList.add('was-validated');
        });
      });
    }
  };

  /**
   * Auto-hide alerts after delay
   */
  const autoHideAlerts = function() {
    const alerts = document.querySelectorAll('.alert[data-auto-hide]');
    alerts.forEach(alert => {
      const delay = parseInt(alert.dataset.autoHide) || 5000;
      setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
      }, delay);
    });
  };

  /**
   * Initialize animations on page load
   */
  const initAnimations = function() {
    // Animate cards on scroll
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('ds-fade-in');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    document.querySelectorAll('.card, .widget-flat').forEach(card => {
      observer.observe(card);
    });
  };

  /**
   * Enhanced DataTable initialization
   */
  const initDataTables = function() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
      // Add loading state to DataTables
      $.fn.dataTable.ext.errMode = 'throw';
      
      // Show loading overlay during DataTable processing
      const originalDataTable = $.fn.DataTable;
      $.fn.DataTable = function(settings) {
        LoadingStates.show('Loading data...');
        const table = originalDataTable.apply(this, arguments);
        table.on('processing.dt', function(e, settings, processing) {
          if (!processing) {
            LoadingStates.hide();
          }
        });
        return table;
      };
    }
  };

  /**
   * Initialize all enhancements
   */
  const init = function() {
    // Initialize form validation
    FormValidation.init();
    
    // Auto-hide alerts
    autoHideAlerts();
    
    // Initialize animations
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initAnimations);
    } else {
      initAnimations();
    }
    
    // Initialize DataTables enhancements
    initDataTables();
  };

  // Initialize on page load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Export to global scope
  window.DesignSystem = {
    Loading: LoadingStates,
    Toast: Toast,
    scrollTo: scrollTo,
    FormValidation: FormValidation
  };

})();

/**
 * jQuery Plugin for Loading States (if jQuery is available)
 */
if (typeof $ !== 'undefined') {
  $.fn.showLoading = function(message) {
    return this.each(function() {
      if (this.tagName === 'BUTTON' || this.tagName === 'INPUT') {
        DesignSystem.Loading.showButtonLoading(this, $(this).html());
      } else {
        const originalContent = $(this).html();
        $(this).data('original-content', originalContent);
        $(this).html(`<div class="text-center p-4"><div class="ds-spinner ds-spinner-lg mx-auto mb-3"></div>${message || 'Loading...'}</div>`);
      }
    });
  };

  $.fn.hideLoading = function() {
    return this.each(function() {
      if (this.tagName === 'BUTTON' || this.tagName === 'INPUT') {
        DesignSystem.Loading.hideButtonLoading(this);
      } else {
        const originalContent = $(this).data('original-content');
        if (originalContent) {
          $(this).html(originalContent);
          $(this).removeData('original-content');
        }
      }
    });
  };
}

