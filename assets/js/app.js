(function() {
    const html = document.documentElement;
    const stored = localStorage.getItem('sv-theme');
    if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        html.setAttribute('data-theme', 'dark');
        updateThemeIcon('dark');
    }

    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function() {
            const current = html.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('sv-theme', next);
            updateThemeIcon(next);
        });
    }

    function updateThemeIcon(theme) {
        const sun = document.querySelector('.icon-sun');
        const moon = document.querySelector('.icon-moon');
        if (sun && moon) {
            sun.style.display = theme === 'dark' ? 'none' : 'block';
            moon.style.display = theme === 'dark' ? 'block' : 'none';
        }
    }

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && e.target !== sidebarToggle && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    const flash = document.querySelector('[data-flash]');
    if (flash) {
        showToast(flash.dataset.flash, flash.dataset.flashMsg);
    }
})();

function openModal(title, content, wide) {
    const overlay = document.getElementById('modalOverlay');
    const modal = document.getElementById('modal');
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    if (wide) modal.classList.add('modal-wide');
    else modal.classList.remove('modal-wide');
    overlay.classList.add('active');
    document.getElementById('modalClose').onclick = closeModal;
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
}

function showToast(type, message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(function() { toast.style.opacity = '0'; toast.style.transform = 'translateX(40px)'; setTimeout(function() { toast.remove(); }, 300); }, 4000);
}

function ajaxPost(url, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                const resp = JSON.parse(xhr.responseText);
                callback(resp, xhr.status);
            } catch (e) {
                callback({ error: 'Invalid response' }, xhr.status);
            }
        }
    };
    const params = Object.keys(data).map(function(k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
    }).join('&');
    xhr.send(params);
}

function apiAction(module, action, params, callback) {
    params.action = action;
    ajaxPost('api/' + module + '.php', params, function(resp, status) {
        if (resp.error) {
            showToast('danger', resp.error);
        } else if (resp.success) {
            showToast('success', resp.message || 'Action completed');
        }
        if (callback) callback(resp, status);
    });
}

function confirmDelete(callback) {
    openModal('Confirm Delete', '<p style="margin-bottom:20px;color:var(--text-secondary)">Are you sure you want to delete this item? This action cannot be undone.</p><div class="btn-group"><button class="btn btn-danger" id="confirmYes">Delete</button><button class="btn btn-outline" onclick="closeModal()">Cancel</button></div>');
    setTimeout(function() {
        var btn = document.getElementById('confirmYes');
        if (btn) btn.onclick = function() { closeModal(); callback(); };
    }, 50);
}

function reloadPage() {
    window.location.reload();
}

function encodeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function getCsrfToken() {
    var el = document.querySelector('input[name="csrf_token"]');
    return el ? el.value : '';
}
