/**
 * Beautiful Dashboard Enhancements
 * Real-time stats, charts, and quick actions
 */

class Dashboard {
  constructor() {
    this.stats = null;
    this.charts = {};
    this.updateInterval = null;
    this.init();
  }

  async init() {
    await this.loadStats();
    this.setupAutoRefresh();
    this.setupQuickActions();
  }

  async loadStats() {
    try {
      // Try enhanced stats first, fallback to regular stats
      let data;
      try {
        data = await apiFetch("admin_stats_enhanced.php");
        this.stats = data.stats || data;
      } catch (e) {
        data = await apiFetch("admin_stats.php");
        this.stats = data.stats || data;
      }
      this.renderStats();
      this.renderCharts();
    } catch (e) {
      console.error("Failed to load stats:", e);
      Toast.error("Failed to load statistics");
    }
  }

  renderStats() {
    if (!this.stats) return;

    const statsContainer = document.getElementById("dashboardStats");
    if (!statsContainer) return;

    statsContainer.innerHTML = `
      <div class="stats-grid">
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(79, 125, 249, 0.1) 0%, rgba(79, 125, 249, 0.05) 100%); border-color: rgba(79, 125, 249, 0.3);">
          <div class="stat-label">👥 Totaal Gebruikers</div>
          <div class="stat-value">${this.stats.total_users || 0}</div>
          <div class="small" style="color: var(--success);">+${this.stats.new_users_today || 0} vandaag</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); border-color: rgba(16, 185, 129, 0.3);">
          <div class="stat-label">📱 Actieve Devices</div>
          <div class="stat-value">${this.stats.active_devices || 0}</div>
          <div class="small" style="color: var(--success);">${this.stats.total_devices || 0} totaal</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); border-color: rgba(245, 158, 11, 0.3);">
          <div class="stat-label">💳 Actieve Abonnementen</div>
          <div class="stat-value">${this.stats.active_subscriptions || 0}</div>
          <div class="small" style="color: var(--warning);">${this.stats.expired_subscriptions || 0} verlopen</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%); border-color: rgba(239, 68, 68, 0.3);">
          <div class="stat-label">🚫 Geblokkeerde Requests</div>
          <div class="stat-value">${this.formatNumber(this.stats.blocked_requests_today || 0)}</div>
          <div class="small" style="color: var(--danger);">Vandaag</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%); border-color: rgba(139, 92, 246, 0.3);">
          <div class="stat-label">📊 Activity Logs</div>
          <div class="stat-value">${this.formatNumber(this.stats.total_logs || 0)}</div>
          <div class="small" style="color: var(--muted);">${this.stats.logs_today || 0} vandaag</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%); border-color: rgba(59, 130, 246, 0.3);">
          <div class="stat-label">✅ Whitelist Entries</div>
          <div class="stat-value">${this.stats.whitelist_count || 0}</div>
          <div class="small" style="color: var(--info);">Actief</div>
        </div>
      </div>
    `;
  }

  renderCharts() {
    if (!this.stats) return;

    // Activity Chart
    const activityCtx = document.getElementById("activityChart");
    if (activityCtx && typeof Chart !== 'undefined') {
      if (this.charts.activity) {
        this.charts.activity.destroy();
      }
      
      this.charts.activity = new Chart(activityCtx, {
        type: 'line',
        data: {
          labels: this.stats.activity_labels || ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'],
          datasets: [{
            label: 'Geblokkeerde Requests',
            data: this.stats.activity_data || [0, 0, 0, 0, 0, 0, 0],
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              labels: { color: '#e8ecf3' }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { color: '#8b9dc3' },
              grid: { color: 'rgba(139, 157, 195, 0.1)' }
            },
            x: {
              ticks: { color: '#8b9dc3' },
              grid: { color: 'rgba(139, 157, 195, 0.1)' }
            }
          }
        }
      });
    }
  }

  setupAutoRefresh() {
    // Auto-refresh stats every 30 seconds
    this.updateInterval = setInterval(() => {
      this.loadStats();
    }, 30000);
  }

  setupQuickActions() {
    const quickActions = document.getElementById("quickActions");
    if (!quickActions) return;

    quickActions.innerHTML = `
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 20px;">
        <button class="quick-action-btn" onclick="quickAddDevice()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
          <span style="font-size: 24px;">➕</span>
          <div>
            <strong>Device Toevoegen</strong>
            <div class="small">Automatisch configureren</div>
          </div>
        </button>
        <button class="quick-action-btn" onclick="quickAddUser()" style="background: linear-gradient(135deg, #4f7df9 0%, #3d6ae8 100%);">
          <span style="font-size: 24px;">👤</span>
          <div>
            <strong>Gebruiker Toevoegen</strong>
            <div class="small">Nieuw account</div>
          </div>
        </button>
        <button class="quick-action-btn" onclick="quickAddSubscription()" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
          <span style="font-size: 24px;">💳</span>
          <div>
            <strong>Abonnement Toevoegen</strong>
            <div class="small">Voor gebruiker</div>
          </div>
        </button>
        <button class="quick-action-btn" onclick="runHealthCheck()" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
          <span style="font-size: 24px;">🏥</span>
          <div>
            <strong>System Health</strong>
            <div class="small">Status check</div>
          </div>
        </button>
      </div>
    `;
  }

  formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
  }

  destroy() {
    if (this.updateInterval) {
      clearInterval(this.updateInterval);
    }
    Object.values(this.charts).forEach(chart => {
      if (chart) chart.destroy();
    });
  }
}

// Quick action functions
function quickAddDevice() {
  document.querySelector('[data-tab="devices"]').click();
  setTimeout(() => {
    document.getElementById("newDeviceUser")?.focus();
  }, 100);
}

function quickAddUser() {
  document.querySelector('[data-tab="users"]').click();
  setTimeout(() => {
    document.getElementById("newUserEmail")?.focus();
  }, 100);
}

function quickAddSubscription() {
  document.querySelector('[data-tab="subscriptions"]').click();
  setTimeout(() => {
    document.getElementById("newSubUser")?.focus();
  }, 100);
}

// Initialize dashboard when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById("dashboardStats")) {
      window.dashboard = new Dashboard();
    }
  });
} else {
  if (document.getElementById("dashboardStats")) {
    window.dashboard = new Dashboard();
  }
}
