<?php
$db = Database::getInstance();
$domains = $db->fetchAll("SELECT * FROM domains ORDER BY created_at DESC");
?>
<div class="page-header">
    <div><h1 class="page-title">Domains</h1><p class="page-subtitle">Manage your domain names</p></div>
    <button class="btn btn-primary" onclick="showAddDomain()">Add Domain</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Domain</th><th>Document Root</th><th>PHP</th><th>Web Server</th><th>SSL</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody id="domainsTable">
                <?php if (empty($domains)): ?>
                    <tr><td colspan="7" class="empty-state"><h3>No domains</h3><p>Add your first domain to get started.</p></td></tr>
                <?php else: foreach ($domains as $d): ?>
                    <tr id="domain-<?= $d['id'] ?>">
                        <td><strong><?= htmlspecialchars($d['domain_name']) ?></strong></td>
                        <td><code style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($d['document_root'] ?? '') ?></code></td>
                        <td><span class="badge badge-info">PHP <?= htmlspecialchars($d['php_version']) ?></span></td>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($d['web_server']) ?></span></td>
                        <td><?= $d['ssl_enabled'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-warning">None</span>' ?></td>
                        <td><?= $d['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Disabled</span>' ?></td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn btn-sm btn-outline" onclick='editDomain(<?= json_encode($d) ?>)'>Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteDomain(<?= $d['id'] ?>)">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showAddDomain() {
    var html = '<form id="domainForm">';
    html += '<div class="form-group"><label class="form-label">Domain Name</label><input type="text" name="domain_name" class="form-control" placeholder="example.com" required></div>';
    html += '<div class="form-row"><div class="form-group"><label class="form-label">Document Root</label><input type="text" name="document_root" class="form-control" placeholder="/var/www/example.com/public"></div>';
    html += '<div class="form-group"><label class="form-label">PHP Version</label><select name="php_version" class="form-control"><?php foreach (PHP_VERSIONS as $v): ?><option value="<?= $v ?>"><?= $v ?></option><?php endforeach; ?></select></div></div>';
    html += '<div class="form-group"><label class="form-label">Web Server</label><select name="web_server" class="form-control"><option value="nginx">Nginx</option><option value="apache">Apache</option></select></div>';
    html += '<button type="submit" class="btn btn-primary" style="width:100%">Add Domain</button></form>';
    openModal('Add Domain', html);
    document.getElementById('domainForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('action', 'add');
        fd.append('csrf_token', getCsrfToken());
        fetch('api/domains.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}

function editDomain(d) {
    var html = '<form id="domainEditForm">';
    html += '<input type="hidden" name="id" value="' + d.id + '">';
    html += '<div class="form-group"><label class="form-label">Domain Name</label><input type="text" name="domain_name" class="form-control" value="' + encodeHtml(d.domain_name) + '"></div>';
    html += '<div class="form-row"><div class="form-group"><label class="form-label">Document Root</label><input type="text" name="document_root" class="form-control" value="' + encodeHtml(d.document_root || '') + '"></div>';
    html += '<div class="form-group"><label class="form-label">PHP Version</label><select name="php_version" class="form-control"><?php foreach (PHP_VERSIONS as $v): ?><option value="<?= $v ?>"><?= $v ?></option><?php endforeach; ?></select></div></div>';
    html += '<div class="form-group"><label class="form-label">Web Server</label><select name="web_server" class="form-control"><option value="nginx"' + (d.web_server === 'nginx' ? ' selected' : '') + '>Nginx</option><option value="apache"' + (d.web_server === 'apache' ? ' selected' : '') + '>Apache</option></select></div>';
    html += '<div class="form-group"><label class="form-label"><input type="checkbox" name="is_active" value="1"' + (d.is_active ? ' checked' : '') + '> Active</label></div>';
    html += '<button type="submit" class="btn btn-primary" style="width:100%">Save Changes</button></form>';
    openModal('Edit Domain', html);
    document.getElementById('domainEditForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('action', 'edit');
        fd.append('csrf_token', getCsrfToken());
        fetch('api/domains.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}

function deleteDomain(id) {
    confirmDelete(function() {
        apiAction('domains', 'delete', {id: id}, function(r) { if (r.success) setTimeout(reloadPage, 500); });
    });
}
</script>
