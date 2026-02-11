/**
 * Professional Admin Panel Enhancements
 * Advanced features for better admin experience
 */

class ProfessionalAdmin {
  constructor() {
    this.keyboardShortcuts = new Map();
    this.searchCache = new Map();
    this.init();
  }

  init() {
    this.setupKeyboardShortcuts();
    this.setupAdvancedSearch();
    this.setupBulkOperations();
    this.setupDataTables();
    this.setupExportImport();
    this.setupRealTimeUpdates();
    this.setupSystemMonitoring();
    this.setupConfirmationDialogs();
    this.setupLoadingStates();
    this.setupTooltips();
  }

  // Keyboard Shortcuts
  setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      // Ctrl/Cmd + K for search
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        this.focusSearch();
      }
      
      // Ctrl/Cmd + N for new item
      if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        this.quickNew();
      }
      
      // Escape to close modals
      if (e.key === 'Escape') {
        this.closeModals();
      }
      
      // Number keys for tabs (1-9)
      if (e.key >= '1' && e.key <= '9' && !e.ctrlKey && !e.metaKey) {
        const tabs = document.querySelectorAll('.tab');
        const index = parseInt(e.key) - 1;
        if (tabs[index]) {
          tabs[index].click();
        }
      }
    });
  }

  focusSearch() {
    const activeTab = document.querySelector('.tab.active');
    if (activeTab) {
      const tabName = activeTab.dataset.tab;
      const searchInput = document.querySelector(`#${tabName}Search, #${tabName}Search input`);
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }
  }

  quickNew() {
    const activeTab = document.querySelector('.tab.active');
    if (activeTab) {
      const tabName = activeTab.dataset.tab;
      const newBtn = document.querySelector(`#add${tabName.charAt(0).toUpperCase() + tabName.slice(1)}Btn, #new${tabName.charAt(0).toUpperCase() + tabName.slice(1)}Btn`);
      if (newBtn) {
        newBtn.click();
      }
    }
  }

  closeModals() {
    document.querySelectorAll('.modal, .overlay').forEach(el => {
      el.style.display = 'none';
    });
  }

  // Advanced Search with highlighting
  setupAdvancedSearch() {
    const searchInputs = document.querySelectorAll('input[type="text"][id*="Search"]');
    searchInputs.forEach(input => {
      input.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        this.highlightSearchResults(query, e.target);
      });
    });
  }

  highlightSearchResults(query, searchInput) {
    if (!query) {
      document.querySelectorAll('.search-highlight').forEach(el => {
        el.classList.remove('search-highlight');
      });
      return;
    }

    const container = searchInput.closest('.tab-content') || document.body;
    const items = container.querySelectorAll('.item, .list > div');
    
    items.forEach(item => {
      const text = item.textContent.toLowerCase();
      if (text.includes(query)) {
        item.classList.add('search-highlight');
        // Scroll into view if needed
        if (item.getBoundingClientRect().top < 0 || item.getBoundingClientRect().bottom > window.innerHeight) {
          item.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else {
        item.classList.remove('search-highlight');
      }
    });
  }

  // Bulk Operations
  setupBulkOperations() {
    // Select all checkbox
    document.addEventListener('change', (e) => {
      if (e.target.classList.contains('select-all')) {
        const container = e.target.closest('.tab-content');
        const checkboxes = container.querySelectorAll('input[type="checkbox"]:not(.select-all)');
        checkboxes.forEach(cb => {
          cb.checked = e.target.checked;
        });
      }
    });
  }

  // Data Tables with sorting
  setupDataTables() {
    // Add sortable headers
    document.querySelectorAll('.list-header').forEach(header => {
      header.style.cursor = 'pointer';
      header.addEventListener('click', () => {
        this.sortTable(header);
      });
    });
  }

  sortTable(header) {
    const column = header.dataset.column;
    const container = header.closest('.tab-content');
    const items = Array.from(container.querySelectorAll('.item'));
    
    const sorted = items.sort((a, b) => {
      const aVal = a.querySelector(`[data-${column}]`)?.textContent || '';
      const bVal = b.querySelector(`[data-${column}]`)?.textContent || '';
      return aVal.localeCompare(bVal);
    });
    
    const list = container.querySelector('.list');
    sorted.forEach(item => list.appendChild(item));
    
    // Update sort indicator
    container.querySelectorAll('.sort-indicator').forEach(ind => ind.remove());
    const indicator = document.createElement('span');
    indicator.className = 'sort-indicator';
    indicator.textContent = ' ▲';
    header.appendChild(indicator);
  }

  // Export/Import
  setupExportImport() {
    // Enhanced export with formatting
    window.exportData = async (type, format = 'json') => {
      try {
        let data;
        switch(type) {
          case 'users':
            data = await apiFetch('admin_users.php');
            break;
          case 'devices':
            data = await apiFetch('admin_devices.php');
            break;
          case 'subscriptions':
            data = await apiFetch('admin_subscriptions.php');
            break;
          default:
            throw new Error('Unknown export type');
        }
        
        if (format === 'csv') {
          this.exportCSV(data, type);
        } else {
          this.exportJSON(data, type);
        }
        
        Toast.success(`✅ ${type} geëxporteerd als ${format.toUpperCase()}`);
      } catch (e) {
        Toast.error(`❌ Export mislukt: ${e.message}`);
      }
    };
  }

  exportCSV(data, type) {
    const items = data[type] || data.items || [];
    if (items.length === 0) return;
    
    const headers = Object.keys(items[0]);
    const csv = [
      headers.join(','),
      ...items.map(item => headers.map(h => `"${item[h] || ''}"`).join(','))
    ].join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${type}_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }

  exportJSON(data, type) {
    const json = JSON.stringify(data, null, 2);
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${type}_${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);
  }

  // Real-time Updates
  setupRealTimeUpdates() {
    // Auto-refresh stats every 30 seconds
    setInterval(() => {
      if (document.querySelector('.tab.active[data-tab="dashboard"]')) {
        if (window.dashboard) {
          window.dashboard.loadStats();
        }
      }
    }, 30000);
  }

  // System Monitoring
  setupSystemMonitoring() {
    // Check system health periodically
    setInterval(async () => {
      try {
        const health = await apiFetch('admin_health.php');
        this.updateSystemStatus(health);
      } catch (e) {
        console.error('Health check failed:', e);
      }
    }, 60000); // Every minute
  }

  updateSystemStatus(health) {
    const statusIndicator = document.getElementById('systemStatus');
    if (!statusIndicator) return;
    
    if (health.status === 'healthy') {
      statusIndicator.className = 'status-indicator active';
      statusIndicator.title = 'System is healthy';
    } else {
      statusIndicator.className = 'status-indicator blocked';
      statusIndicator.title = 'System issues detected';
      Toast.warning('⚠️ System health check detected issues');
    }
  }

  // Confirmation Dialogs
  setupConfirmationDialogs() {
    window.confirmAction = (message, callback) => {
      const modal = this.createModal('Bevestiging', `
        <p style="margin-bottom: 20px;">${message}</p>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
          <button class="secondary" onclick="this.closest('.modal').remove()">Annuleren</button>
          <button class="primary" onclick="this.closest('.modal').remove(); (${callback.toString()})()">Bevestigen</button>
        </div>
      `);
      document.body.appendChild(modal);
    };
  }

  createModal(title, content) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.3s ease;
    `;
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.cssText = `
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 24px;
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
      animation: slideUp 0.3s ease;
    `;
    
    modal.innerHTML = `
      <h3 style="margin-bottom: 20px;">${title}</h3>
      ${content}
    `;
    
    overlay.appendChild(modal);
    overlay.onclick = (e) => {
      if (e.target === overlay) {
        overlay.remove();
      }
    };
    
    return overlay;
  }

  // Loading States
  setupLoadingStates() {
    // Show loading spinner on async operations
    window.showLoading = (element) => {
      if (!element) return;
      const spinner = document.createElement('div');
      spinner.className = 'loading-spinner';
      spinner.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);';
      element.style.position = 'relative';
      element.appendChild(spinner);
      return spinner;
    };
    
    window.hideLoading = (spinner) => {
      if (spinner && spinner.parentNode) {
        spinner.parentNode.removeChild(spinner);
      }
    };
  }

  // Tooltips
  setupTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(el => {
      el.classList.add('tooltip');
    });
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.professionalAdmin = new ProfessionalAdmin();
  });
} else {
  window.professionalAdmin = new ProfessionalAdmin();
}
