// ========== MODERN JAVASCRIPT FEATURES ==========

// 1. Toast Notification System
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
            <strong>${type.toUpperCase()}</strong>
            <p style="margin: 0; font-size: 14px;">${message}</p>
        </div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">×</button>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// 2. Dark/Light Mode Toggle
function initDarkMode() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        const toggle = document.getElementById('themeToggle');
        if (toggle) toggle.checked = true;
    }
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    showToast(isDark ? 'Dark mode enabled' : 'Light mode enabled', 'success');
}

// 3. Live Clock
function updateLiveClock() {
    const clockElements = document.querySelectorAll('.live-clock');
    if (clockElements.length) {
        const now = new Date();
        const formatted = now.toLocaleString('en-IN', { 
            timeZone: 'Asia/Kolkata',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        clockElements.forEach(el => el.textContent = formatted);
    }
}
setInterval(updateLiveClock, 1000);

// 4. Skeleton Loading
function showSkeleton(containerId, count = 3) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const skeletons = Array(count).fill(0).map(() => 
        `<div class="skeleton" style="height: 60px; margin-bottom: 10px;"></div>`
    ).join('');
    container.innerHTML = skeletons;
}

function hideSkeleton(containerId, content) {
    const container = document.getElementById(containerId);
    if (container) container.innerHTML = content;
}

// 5. Copy to Clipboard
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard!', 'success');
    } catch(err) {
        showToast('Failed to copy', 'error');
    }
}

// 6. Export Data to CSV
function exportToCSV(data, filename = 'export.csv') {
    const headers = Object.keys(data[0]).join(',');
    const rows = data.map(row => Object.values(row).join(',')).join('\n');
    const csv = `${headers}\n${rows}`;
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
    showToast('Export completed!', 'success');
}

// 7. Print Page Section
function printSection(sectionId) {
    const content = document.getElementById(sectionId).innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head><title>Print Report</title>
        <style>body { font-family: Arial, sans-serif; padding: 20px; }</style>
        </head>
        <body>${content}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// 8. Refresh Data with Animation
async function refreshData(apiUrl, targetElementId) {
    const btn = event?.target;
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';
        btn.disabled = true;
    }
    showSkeleton(targetElementId, 3);
    try {
        const response = await fetch(apiUrl);
        const data = await response.json();
        hideSkeleton(targetElementId, JSON.stringify(data));
        showToast('Data refreshed!', 'success');
    } catch(e) {
        showToast('Refresh failed', 'error');
    }
    if (btn) {
        btn.innerHTML = '<i class="fas fa-sync-alt"></i>';
        btn.disabled = false;
    }
}

// 9. Typing Animation for Welcome Text
function typeWriter(elementId, text, speed = 50) {
    const element = document.getElementById(elementId);
    if (!element) return;
    element.innerHTML = '';
    let i = 0;
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    type();
}

// 10. Confetti Effect (for achievements)
function showConfetti() {
    const canvas = document.createElement('canvas');
    canvas.style.position = 'fixed';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.pointerEvents = 'none';
    canvas.style.zIndex = '9999';
    document.body.appendChild(canvas);
    
    const duration = 3000;
    const animationEnd = Date.now() + duration;
    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 10000 };
    
    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }
    
    const interval = setInterval(function() {
        const timeLeft = animationEnd - Date.now();
        if (timeLeft <= 0) {
            clearInterval(interval);
            canvas.remove();
            return;
        }
        const particleCount = 50 * (timeLeft / duration);
        confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.9), y: Math.random() - 0.2 } });
    }, 250);
}

// 11. Avatar with Initials
function getAvatarInitials(name) {
    const words = name.split(' ');
    let initials = '';
    if (words.length >= 2) {
        initials = words[0][0] + words[1][0];
    } else {
        initials = name.substring(0, 2);
    }
    return initials.toUpperCase();
}

function createAvatar(name, size = 40) {
    const initials = getAvatarInitials(name);
    const colors = ['#1f8a72', '#e67e22', '#3498db', '#9b59b6', '#e74c3c'];
    const randomColor = colors[Math.floor(Math.random() * colors.length)];
    return `
        <div style="width: ${size}px; height: ${size}px; background: ${randomColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: ${size/2}px;">
            ${initials}
        </div>
    `;
}

// 12. Animated Counter
function animateCounter(elementId, target, duration = 1000) {
    const element = document.getElementById(elementId);
    if (!element) return;
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.innerText = target.toLocaleString();
            clearInterval(timer);
        } else {
            element.innerText = Math.floor(current).toLocaleString();
        }
    }, 16);
}

// 13. Fullscreen Toggle
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        showToast('Fullscreen mode enabled', 'success');
    } else {
        document.exitFullscreen();
        showToast('Fullscreen mode exited', 'info');
    }
}

// 14. Scroll to Top Button
function initScrollToTop() {
    const btn = document.createElement('div');
    btn.className = 'fab';
    btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    btn.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });
    document.body.appendChild(btn);
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btn.style.display = 'flex';
        } else {
            btn.style.display = 'none';
        }
    });
    btn.style.display = 'none';
}

// 15. Online/Offline Status
function initOnlineStatus() {
    window.addEventListener('online', () => showToast('Back online!', 'success'));
    window.addEventListener('offline', () => showToast('You are offline', 'error'));
}

// Initialize all features when DOM loads
document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    updateLiveClock();
    initScrollToTop();
    initOnlineStatus();
});