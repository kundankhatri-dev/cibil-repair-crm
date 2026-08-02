// /assets/js/email-admin.js

let currentTemplateKey = null;

// ── LOAD TEMPLATES ──
async function loadTemplates() {
    console.log('📧 Loading email templates...');
    
    const container = document.getElementById('templatesContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-cell"><div class="spinner"></div></div>';
    
    try {
        const response = await fetch('/api/email/templates.php?action=get');
        const data = await response.json();
        
        if (data.success) {
            renderTemplates(data.data);
            console.log('✅ Loaded', data.total, 'templates');
        } else {
            container.innerHTML = `<div class="empty-state"><p>Error: ${data.error}</p></div>`;
        }
    } catch (e) {
        console.error('Error loading templates:', e);
        container.innerHTML = `<div class="empty-state"><p>Error loading templates</p></div>`;
    }
}

// ── RENDER TEMPLATES ──
function renderTemplates(templates) {
    const container = document.getElementById('templatesContainer');
    if (!container) return;
    
    if (!templates || templates.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-envelope" style="font-size:40px;color:var(--text-muted);"></i>
                <p>No email templates found</p>
                <button class="btn btn-primary" onclick="showAddTemplate()">
                    <i class="fas fa-plus"></i> Create Template
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = templates.map(t => `
        <div class="template-card" style="border:1px solid var(--border);border-radius:var(--r-md);padding:16px;margin-bottom:12px;background:var(--bg-surface);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div>
                    <strong style="font-size:16px;">${esc(t.template_key)}</strong>
                    <span class="badge badge-gray" style="font-size:10px;margin-left:8px;">${esc(t.subject)}</span>
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-ghost btn-xs" onclick="previewTemplate('${t.template_key}')" title="Preview">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-ghost btn-xs" onclick="editTemplate(${t.id}, '${t.template_key}')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deleteTemplate(${t.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div style="font-size:13px;color:var(--text-secondary);max-height:60px;overflow:hidden;">
                ${t.body.substring(0, 200)}${t.body.length > 200 ? '...' : ''}
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:8px;">
                Created: ${new Date(t.created_at).toLocaleDateString('en-IN')}
            </div>
        </div>
    `).join('');
}

// ── SHOW ADD TEMPLATE ──
function showAddTemplate() {
    document.getElementById('templateForm').reset();
    document.getElementById('templateId').value = '';
    document.getElementById('templateModalTitle').textContent = 'Create Email Template';
    document.getElementById('templateKey').value = '';
    document.getElementById('templateKey').disabled = false;
    openModal('templateModal');
}

// ── EDIT TEMPLATE ──
async function editTemplate(id, key) {
    document.getElementById('templateId').value = id;
    document.getElementById('templateKey').value = key;
    document.getElementById('templateKey').disabled = true;
    document.getElementById('templateModalTitle').textContent = 'Edit Email Template';
    
    try {
        const response = await fetch(`/api/email/templates.php?action=get_one&key=${key}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('templateSubject').value = data.data.subject;
            document.getElementById('templateBody').value = data.data.body;
            openModal('templateModal');
        } else {
            toast('Error loading template', 'error');
        }
    } catch (e) {
        toast('Error loading template', 'error');
    }
}

// ── SAVE TEMPLATE ──
async function saveTemplate() {
    const id = document.getElementById('templateId').value;
    const key = document.getElementById('templateKey').value.trim();
    const subject = document.getElementById('templateSubject').value.trim();
    const body = document.getElementById('templateBody').value.trim();
    
    if (!key || !subject || !body) {
        toast('All fields are required', 'error');
        return;
    }
    
    const isEdit = id && id !== '';
    
    const data = {
        id: id || 0,
        template_key: key,
        subject: subject,
        body: body
    };
    
    const action = isEdit ? 'update' : 'create';
    const url = `/api/email/templates.php?action=${action}`;
    
    const btn = document.querySelector('#templateModal .btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Saving...';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast('Template saved!', 'success');
            closeModal('templateModal');
            loadTemplates();
        } else {
            toast(result.error || 'Failed to save', 'error');
        }
    } catch (e) {
        toast('Error saving template', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Template';
    }
}

// ── DELETE TEMPLATE ──
async function deleteTemplate(id) {
    if (!confirm('Are you sure you want to delete this template?')) return;
    
    try {
        const response = await fetch('/api/email/templates.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast('Template deleted', 'success');
            loadTemplates();
        } else {
            toast(result.error || 'Failed to delete', 'error');
        }
    } catch (e) {
        toast('Error deleting template', 'error');
    }
}

// ── PREVIEW TEMPLATE ──
async function previewTemplate(key) {
    try {
        const response = await fetch(`/api/email/templates.php?action=get_one&key=${key}`);
        const data = await response.json();
        
        if (data.success) {
            const previewWindow = window.open('', '_blank', 'width=600,height=500');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Template Preview</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
                        .header { background: #0b2a23; color: #fff; padding: 20px; text-align: center; }
                        .body { padding: 20px; border: 1px solid #ddd; }
                        .footer { background: #f4f4f4; padding: 10px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h3>${esc(data.data.subject)}</h3>
                    </div>
                    <div class="body">
                        ${data.data.body}
                    </div>
                    <div class="footer">
                        Preview - CIBIL Repair
                    </div>
                </body>
                </html>
            `);
            previewWindow.document.close();
        }
    } catch (e) {
        toast('Error previewing template', 'error');
    }
}

// ── SEND TEST EMAIL ──
async function sendTestEmail() {
    const email = document.getElementById('testEmail').value.trim();
    const templateKey = document.getElementById('testTemplate').value;
    
    if (!email) {
        toast('Please enter an email address', 'error');
        return;
    }
    
    if (!filter_var(email, FILTER_VALIDATE_EMAIL)) {
        toast('Please enter a valid email address', 'error');
        return;
    }
    
    const btn = document.getElementById('sendTestBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Sending...';
    
    try {
        const response = await fetch('/api/email/queue.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                to: email,
                template_key: templateKey,
                variables: { name: 'Test User' },
                priority: 1
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast('Test email added to queue!', 'success');
        } else {
            toast(result.error || 'Failed to send test email', 'error');
        }
    } catch (e) {
        toast('Error sending test email', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Test';
    }
}

// ── EMAIL HISTORY ──
async function loadEmailHistory() {
    const container = document.getElementById('emailHistory');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-cell"><div class="spinner"></div></div>';
    
    try {
        const response = await fetch('/api/email/history.php');
        const data = await response.json();
        
        if (data.success) {
            renderHistory(data.data);
        } else {
            container.innerHTML = `<div class="empty-state"><p>${data.error || 'No history found'}</p></div>`;
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state"><p>Error loading history</p></div>';
    }
}

function renderHistory(history) {
    const container = document.getElementById('emailHistory');
    if (!container) return;
    
    if (!history || history.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-history" style="font-size:40px;color:var(--text-muted);"></i>
                <p>No email history found</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Recipient</th>
                        <th>Template</th>
                        <th>Subject</th>
                        <th>Sent At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${history.map((h, i) => `
                        <tr>
                            <td>${i + 1}</td>
                            <td>${esc(h.recipient_email)}</td>
                            <td><span class="badge badge-gray">${esc(h.template_key || '—')}</span></td>
                            <td>${esc(h.subject)}</td>
                            <td>${new Date(h.sent_at).toLocaleString('en-IN')}</td>
                            <td>${statusBadge(h.status)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}