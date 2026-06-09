<?php
$db = Database::getInstance();
$domains = $db->fetchAll("SELECT * FROM domains ORDER BY domain_name");
$selectedDomain = $_GET['domain'] ?? '';
$content = '';
if ($selectedDomain) {
    $htPath = VHOST_WEBROOT . '/' . $selectedDomain . '/.htaccess';
    $content = System::readFile($htPath) ?: '';
}
?>
<div class="page-header">
    <div><h1 class="page-title">.htaccess Editor</h1><p class="page-subtitle">Edit .htaccess files for your domains</p></div>
</div>
<div class="toolbar">
    <select class="form-control" style="max-width:300px" onchange="window.location='index.php?m=htaccess&domain='+this.value">
        <option value="">Select a domain</option>
        <?php foreach ($domains as $d): ?>
            <option value="<?= htmlspecialchars($d['domain_name']) ?>" <?= $selectedDomain === $d['domain_name'] ? 'selected' : '' ?>><?= htmlspecialchars($d['domain_name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<?php if ($selectedDomain): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title"><?= htmlspecialchars($selectedDomain) ?>/.htaccess</span>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline" onclick="insertSnippet('redirect')">Redirect</button>
            <button class="btn btn-sm btn-outline" onclick="insertSnippet('cache')">Cache</button>
            <button class="btn btn-sm btn-outline" onclick="insertSnippet('protect')">Protect</button>
        </div>
    </div>
    <div class="card-body">
        <form id="htForm">
            <input type="hidden" name="domain" value="<?= htmlspecialchars($selectedDomain) ?>">
            <textarea name="content" id="htContent" class="code-editor"><?= htmlspecialchars($content) ?></textarea>
            <div style="margin-top:16px;display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-primary">Save .htaccess</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('htForm').onsubmit = function(e) {
    e.preventDefault();
    var fd = new FormData(this); fd.append('action', 'save'); fd.append('csrf_token', getCsrfToken());
    fetch('api/htaccess.php', {method:'POST',body:fd}).then(r=>r.json()).then(function(r) {
        if (r.success) showToast('success', r.message);
        else showToast('danger', r.error || 'Failed');
    });
};
var snippets = {
    redirect: '\n# 301 Redirect\nRewriteEngine On\nRewriteRule ^old-page$ /new-page [R=301,L]\n',
    cache: '\n# Enable Caching\n<IfModule mod_expires.c>\n    ExpiresActive On\n    ExpiresByType image/jpeg "access plus 1 year"\n    ExpiresByType text/css "access plus 1 month"\n    ExpiresByType application/javascript "access plus 1 month"\n</IfModule>\n',
    protect: '\n# Protect .htaccess\n<Files .htaccess>\n    Order allow,deny\n    Deny from all\n</Files>\n'
};
function insertSnippet(type) {
    var ta = document.getElementById('htContent');
    ta.value += snippets[type];
    ta.focus();
}
</script>
<?php else: ?>
<div class="empty-state"><h3>Select a domain</h3><p>Choose a domain from the dropdown to edit its .htaccess file.</p></div>
<?php endif; ?>
