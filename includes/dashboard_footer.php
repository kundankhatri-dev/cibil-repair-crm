<?php
// ============================================================
// MASTER DASHBOARD FOOTER - Include at the bottom of EVERY dashboard
// ============================================================
?>
    </div> <!-- Close page-container -->
</div> <!-- Close dashboard-wrapper -->

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ============================================================
// GLOBAL HELPER FUNCTIONS FOR ALL DASHBOARDS
// ============================================================

// Show toast notification
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    
    let icon = '';
    let bgColor = '';
    
    switch(type) {
        case 'success':
            icon = '<i class="fas fa-check-circle" style="color: #059669;"></i>';
            bgColor = 'border-left: 3px solid #059669;';
            break;
        case 'error':
            icon = '<i class="fas fa-times-circle" style="color: #dc2626;"></i>';
            bgColor = 'border-left: 3px solid #dc2626;';
            break;
        case 'warning':
            icon = '<i class="fas fa-exclamation-triangle" style="color: #d97706;"></i>';
            bgColor = 'border-left: 3px solid #d97706;';
            break;
        default:
            icon = '<i class="fas fa-info-circle" style="color: #0d9e78;"></i>';
            bgColor = 'border-left: 3px solid #0d9e78;';
    }
    
    toast.style.cssText = bgColor;
    toast.innerHTML = `${icon} <span>${message}</span>`;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

// Format datetime
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Get status badge HTML
function getStatusBadge(status) {
    const statusMap = {
        'active': 'badge-success',
        'inactive': 'badge-danger',
        'pending': 'badge-warning',
        'completed': 'badge-success',
        'cancelled': 'badge-danger',
        'in_progress': 'badge-info',
        'open': 'badge-warning',
        'closed': 'badge-secondary'
    };
    
    const className = statusMap[status] || 'badge-info';
    const displayText = status.replace(/_/g, ' ').toUpperCase();
    
    return `<span class="badge ${className}">${displayText}</span>`;
}

// API call helper with CSRF
async function apiCall(endpoint, method = 'GET', data = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        credentials: 'include'
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(endpoint, options);
        const result = await response.json();
        
        if (!result.success && result.message) {
            showToast(result.message, 'error');
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast('Network error. Please try again.', 'error');
        return { success: false, error: error.message };
    }
}

// Loader show/hide
function showLoader() {
    let loader = document.getElementById('globalLoader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'globalLoader';
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        loader.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loader);
    }
    loader.style.display = 'flex';
}

function hideLoader() {
    const loader = document.getElementById('globalLoader');
    if (loader) {
        loader.style.display = 'none';
    }
}

// Auto-refresh counter
let autoRefreshInterval = null;
let refreshSeconds = 30;

function startAutoRefresh(callback, seconds = 30) {
    refreshSeconds = seconds;
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    
    autoRefreshInterval = setInterval(() => {
        if (callback) callback();
    }, seconds * 1000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
}

// Export to CSV
function exportToCSV(data, filename) {
    const headers = Object.keys(data[0] || {});
    const csvRows = [];
    
    csvRows.push(headers.join(','));
    
    for (const row of data) {
        const values = headers.map(header => {
            const value = row[header] || '';
            return `"${String(value).replace(/"/g, '""')}"`;
        });
        csvRows.push(values.join(','));
    }
    
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${filename}_${new Date().toISOString().slice(0,19)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    showToast('Export completed!', 'success');
}
</script>

</body>
</html>