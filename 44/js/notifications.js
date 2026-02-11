/**
 * Beautiful Notification System
 * Toast notifications with animations and icons
 */

class NotificationSystem {
  constructor() {
    this.container = null;
    this.initialized = false;
    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.createContainer());
    } else {
      this.createContainer();
    }
  }

  createContainer() {
    // Check if body exists
    if (!document.body) {
      // If body still doesn't exist, wait a bit more
      setTimeout(() => this.createContainer(), 100);
      return;
    }

    // Check if container already exists
    const existing = document.getElementById('notification-container');
    if (existing) {
      this.container = existing;
      this.initialized = true;
      return;
    }

    // Create notification container
    this.container = document.createElement('div');
    this.container.id = 'notification-container';
    this.container.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 10000;
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-width: 400px;
      pointer-events: none;
    `;
    document.body.appendChild(this.container);
    this.initialized = true;
  }

  show(message, type = 'info', duration = 5000) {
    // Ensure container is initialized
    if (!this.initialized || !this.container) {
      this.createContainer();
      // If still not ready, wait a bit
      if (!this.container) {
        setTimeout(() => this.show(message, type, duration), 100);
        return null;
      }
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icons = {
      success: '✅',
      error: '❌',
      warning: '⚠️',
      info: 'ℹ️'
    };

    const colors = {
      success: { bg: 'rgba(16, 185, 129, 0.95)', border: '#10b981' },
      error: { bg: 'rgba(239, 68, 68, 0.95)', border: '#ef4444' },
      warning: { bg: 'rgba(245, 158, 11, 0.95)', border: '#f59e0b' },
      info: { bg: 'rgba(59, 130, 246, 0.95)', border: '#3b82f6' }
    };

    notification.style.cssText = `
      background: ${colors[type].bg};
      border-left: 4px solid ${colors[type].border};
      color: white;
      padding: 16px 20px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
      pointer-events: auto;
      animation: slideInRight 0.3s ease;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      line-height: 1.5;
    `;

    notification.innerHTML = `
      <span style="font-size: 20px;">${icons[type]}</span>
      <div style="flex: 1;">${message}</div>
      <button class="notification-close" style="background: none; border: none; color: white; cursor: pointer; font-size: 18px; opacity: 0.8; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">×</button>
    `;

    // Add close button handler
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.onclick = () => this.remove(notification);

    if (this.container) {
      this.container.appendChild(notification);
    } else {
      console.error('Notification container not available');
      return null;
    }

    // Auto remove after duration
    if (duration > 0) {
      setTimeout(() => {
        this.remove(notification);
      }, duration);
    }

    return notification;
  }

  remove(notification) {
    notification.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }

  success(message, duration = 5000) {
    return this.show(message, 'success', duration);
  }

  error(message, duration = 7000) {
    return this.show(message, 'error', duration);
  }

  warning(message, duration = 6000) {
    return this.show(message, 'warning', duration);
  }

  info(message, duration = 5000) {
    return this.show(message, 'info', duration);
  }
}

// Add CSS animations
function addStyles() {
  // Check if styles already added
  if (document.getElementById('notification-styles')) {
    return;
  }

  const style = document.createElement('style');
  style.id = 'notification-styles';
  style.textContent = `
    @keyframes slideInRight {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }
    .quick-action-btn {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px 20px;
      border: none;
      border-radius: 12px;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      font-size: 14px;
      text-align: left;
    }
    .quick-action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
    .quick-action-btn:active {
      transform: translateY(0);
    }
    .notification-close:hover {
      opacity: 1 !important;
      background: rgba(255, 255, 255, 0.2) !important;
      border-radius: 50% !important;
    }
  `;
  
  if (document.head) {
    document.head.appendChild(style);
  } else {
    // Wait for head to be available
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        if (document.head && !document.getElementById('notification-styles')) {
          document.head.appendChild(style);
        }
      });
    }
  }
}

// Initialize styles
addStyles();

// Initialize notification system when DOM is ready
function initNotificationSystem() {
  if (document.body) {
    window.Notifications = new NotificationSystem();
    
    // Enhanced Toast functions
    window.Toast = {
      success: (msg) => window.Notifications?.success(msg) || console.log('✅', msg),
      error: (msg) => window.Notifications?.error(msg) || console.error('❌', msg),
      warning: (msg) => window.Notifications?.warning(msg) || console.warn('⚠️', msg),
      info: (msg) => window.Notifications?.info(msg) || console.info('ℹ️', msg)
    };
  } else {
    // Wait for body to be available
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initNotificationSystem);
    } else {
      // If DOMContentLoaded already fired, wait a bit
      setTimeout(initNotificationSystem, 100);
    }
  }
}

// Start initialization
initNotificationSystem();
