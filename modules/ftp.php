<?php
$db = Database::getInstance();
$accounts = $db->fetchAll("SELECT f.*, d.domain_name FROM ftp_accounts f LEFT JOIN domains d ON f.domain_id = d.id ORDER BY f.created_at DESC");
$domains = $db->fetchAll("SELECT id, domain_name FROM domains ORDER BY domain_name");
?>
<div class="page-header">
    <div><h1 class="page-title">FTP Accounts</h1><p class="page-subtitle">Manage FTP access</p></div>
    <button class="btn btn-primary" onclick="showAddFtp()">Add FTP Account</button>
</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Username</th><th>Home Directory</th><th>Domain</th><th>Quota</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($accounts)): ?>
                    <tr><td colspan="6" class="empty-state"><h3>No FTP accounts</h3><p>Create your first FTP account.</p></td></tr>
                <?php else: foreach ($accounts as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['username']) ?></strong></td>
                        <td><code style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($a['home_dir']) ?></code></td>
                        <td><?= htmlspecialchars($a['domain_name'] ?? 'N/A') ?></td>
                        <td><?= $a['quota_mb'] ?>MB</td>
                        <td><?= $a['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Disabled</span>' ?></td>
                        <td><div class="actions-cell">
                            <button class="btn btn-sm btn-outline" onclick='editFtp(<?= json_encode($a) ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteFtp(<?= $a['id'] ?>)">Delete</button>
                        </div></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
var domains = <?= json_encode($domains) ?>;
function domainOptions(selected) {
    var h = '<option value="">None</option>';
    domains.forEach(function(d) { h += '<option value="' + d.id + '"' + (d.id == selected ? ' selected' : '') + '>' + encodeHtml(d.domain_name) + '</option>'; });
    return h;
}
function showAddFtp() {
    var html = '<form id="ftpForm">';
    html += '<div class="form-row"><div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>';
    html += '<div class="form-group"><label class="form-label">Password</label><input type="text" name="password" class="form-control" value="' + Math.random().toString(36).slice(-12) + '"></div></div>';
    html += '<div class="form-row"><div class="form-group"><label class="form-label">Home Directory</label><input type="text" name="home_dir" class="form-control" placeholder="/var/www/example.com"></div>';
    html += '<div class="form-group"><label class="form-label">Domain</label><select name="domain_id" class="form-control">' + domainOptions('') + '</select></div></div>';
    html += '<div class="form-group"><label class="form-label">Quota (MB)</label><input type="number" name="quota_mb" class="form-control" value="1024"></div>';
    html += '<button type="submit" class="btn btn-primary" style="width:100%">Create FTP Account</button></form>';
    openModal('Add FTP Account', html);
    document.getElementById('ftpForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action', 'add'); fd.append('csrf_token', getCsrfToken());
        fetch('api/ftp.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}
function editFtp(a) {
    var html = '<form id="ftpEditForm"><input type="hidden" name="id" value="' + a.id + '">';
    html += '<div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" value="' + encodeHtml(a.username) + '"></div>';
    html += '<div class="form-group"><label class="form-label">New Password (leave blank to keep)</label><input type="text" name="password" class="form-control" placeholder="Leave blank to keep current"></div>';
    html += '<div class="form-row"><div class="form-group"><label class="form-label">Home Directory</label><input type="text" name="home_dir" class="form-control" value="' + encodeHtml(a.home_dir) + '"></div>';
    html += '<div class="form-group"><label class="form-label">Domain</label><select name="domain_id" class="form-control">' + domainOptions(a.domain_id) + '</select></div></div>';
    html += '<div class="form-group"><label class="form-label">Quota (MB)</label><input type="number" name="quota_mb" class="form-control" value="' + a.quota_mb + '"></div>';
    html += '<div class="form-group"><label class="form-label"><input type="checkbox" name="is_active" value="1"' + (a.is_active ? ' checked' : '') + '> Active</label></div>';
    html += '<button type="submit" class="btn btn-primary" style="width:100%">Save</button></form>';
    openModal('Edit FTP Account', html);
    document.getElementById('ftpEditForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action', 'edit'); fd.append('csrf_token', getCsrfToken());
        fetch('api/ftp.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}
function deleteFtp(id) { confirmDelete(function() { apiAction('ftp', 'delete', {id: id}, function(r) { if (r.success) setTimeout(reloadPage, 500); }); }); }
</script>
