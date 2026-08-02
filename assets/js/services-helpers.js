// ============================================================
// SERVICES HELPERS - assets/js/services-helpers.js
// ============================================================

(function() {
    'use strict';
    
    console.log('🔄 Loading services helpers...');
    
    // Main getServices function
    window.getServices = function() {
        if (window.servicesData && window.servicesData.length > 0) {
            return window.servicesData;
        }
        
        const cards = document.querySelectorAll('.service-card');
        const services = [];
        
        if (cards.length === 0) {
            return [];
        }
        
        cards.forEach((card, index) => {
            let name = '';
            const nameSelectors = ['strong', '.service-name', '[class*="name"]', 'h4', 'h3'];
            for (const selector of nameSelectors) {
                const el = card.querySelector(selector);
                if (el && el.textContent.trim()) {
                    name = el.textContent.trim();
                    break;
                }
            }
            
            if (!name) {
                const firstLine = card.textContent.trim().split('\n')[0].trim();
                if (firstLine && firstLine.length < 50) {
                    name = firstLine;
                }
            }
            
            let price = 0;
            const priceSelectors = ['[style*="font-size:20px"]', '.price', '[class*="price"]'];
            for (const selector of priceSelectors) {
                const el = card.querySelector(selector);
                if (el && el.textContent.trim()) {
                    const priceText = el.textContent.replace(/[₹,]/g, '').trim();
                    if (priceText && !isNaN(parseFloat(priceText))) {
                        price = parseFloat(priceText);
                        break;
                    }
                }
            }
            
            let status = 'active';
            const statusSelectors = ['.badge:last-child', '.badge', '[class*="badge"]'];
            for (const selector of statusSelectors) {
                const el = card.querySelector(selector);
                if (el && el.textContent.trim()) {
                    const statusText = el.textContent.trim().toLowerCase();
                    if (['active', 'inactive', 'draft', 'pending', 'approved'].includes(statusText)) {
                        status = statusText;
                        break;
                    }
                }
            }
            
            let icon = '⭐';
            const iconSelectors = ['span[style*="font-size:32px"]', '.icon', 'span:first-child'];
            for (const selector of iconSelectors) {
                const el = card.querySelector(selector);
                if (el && el.textContent.trim() && el.textContent.trim().length <= 2) {
                    icon = el.textContent.trim();
                    break;
                }
            }
            
            let description = '';
            const descSelectors = ['p', '.description', '[class*="description"]'];
            for (const selector of descSelectors) {
                const el = card.querySelector(selector);
                if (el && el.textContent.trim()) {
                    description = el.textContent.trim();
                    break;
                }
            }
            
            let duration = '30-45 days';
            const durationSelectors = ['[style*="font-size:11px"]', '.duration', '[class*="duration"]'];
            for (const selector of durationSelectors) {
                const el = card.querySelector(selector);
                if (el && el.textContent.trim()) {
                    duration = el.textContent.trim();
                    break;
                }
            }
            
            if (name) {
                services.push({
                    id: index + 1,
                    name: name,
                    price: price,
                    status: status,
                    icon: icon,
                    description: description,
                    duration: duration,
                    is_featured: 0,
                    is_popular: 0,
                    category: status === 'active' ? 'credit_repair' : 'other'
                });
            }
        });
        
        if (services.length > 0) {
            window.servicesData = services;
            window._servicesData = services;
            window.allServices = services;
            if (typeof state !== 'undefined') {
                state.services = services;
            }
        }
        
        return services;
    };
    
    // Helper functions
    window.getServiceById = function(id) {
        return window.getServices().find(s => s.id === id) || null;
    };
    
    window.getActiveServices = function() {
        return window.getServices().filter(s => s.status === 'active' || s.status === 'Active');
    };
    
    window.getServiceNames = function() {
        return window.getServices().map(s => s.name);
    };
    
    window.getServiceCount = function() {
        return window.getServices().length;
    };
    
    window.getServicesByCategory = function(category) {
        return window.getServices().filter(s => s.category === category);
    };
    
    window.getFeaturedServices = function() {
        return window.getServices().filter(s => s.is_featured === 1 || s.is_featured === true);
    };
    
    console.log('✅ Services helpers loaded!');
    console.log('📊 Services found:', window.getServiceCount());
})();
// ── ADMIN DASHBOARD - PERMANENT LABEL FIX ──
(function fixAdminLabels() {
    console.log('🔧 Admin: Auto-fixing labels...');
    
    function generateId(text) {
        return text
            .replace(/\*/g, '')
            .replace(/[\(\)\$]/g, '')
            .replace(/[^\w\s]/g, '')
            .trim()
            .replace(/\s+/g, '_')
            .toLowerCase()
            .substring(0, 30);
    }
    
    function fixLabels() {
        document.querySelectorAll('label').forEach(label => {
            if (label.control) return;
            
            const labelText = label.textContent.trim();
            const cleanId = generateId(labelText);
            let foundInput = null;
            
            // Try to find associated input
            let parent = label.parentElement;
            let depth = 0;
            while (parent && !foundInput && depth < 5) {
                foundInput = parent.querySelector('input, select, textarea');
                if (!foundInput) {
                    parent = parent.parentElement;
                    depth++;
                }
            }
            
            // Check siblings
            if (!foundInput) {
                let next = label.nextElementSibling;
                while (next && !foundInput) {
                    if (['INPUT', 'SELECT', 'TEXTAREA'].includes(next.tagName)) {
                        foundInput = next;
                    }
                    next = next.nextElementSibling;
                }
            }
            
            if (foundInput) {
                if (!foundInput.id) {
                    foundInput.id = `admin_${cleanId}_${Date.now()}`;
                }
                label.setAttribute('for', foundInput.id);
            }
        });
    }
    
    // Run on page load
    document.addEventListener('DOMContentLoaded', fixLabels);
    
    // Run on dynamic content (modals, tabs)
    document.addEventListener('shown.bs.modal', fixLabels);
    document.addEventListener('shown.bs.tab', fixLabels);
    
    console.log('✅ Admin label fix applied!');
})();

// ── ADMIN CLICKABLE LABELS CSS ──
(function addAdminStyles() {
    const style = document.createElement('style');
    style.textContent = `
        /* Admin Dashboard - Clickable Labels */
        label[for] {
            cursor: pointer !important;
            transition: color 0.2s ease, background-color 0.2s ease;
            padding: 2px 4px;
            border-radius: 4px;
            display: inline-block;
        }
        
        label[for]:hover {
            color: #0056b3 !important;
            background-color: rgba(0, 86, 179, 0.05) !important;
            transform: translateX(2px);
        }
        
        label[for]:active {
            transform: scale(0.98);
        }
    `;
    document.head.appendChild(style);
    console.log('✅ Admin clickable labels CSS applied!');
})();