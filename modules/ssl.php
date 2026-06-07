<?php
$db = Database::getInstance();
$certs = $db->fetchAll("SELECT s.*, d.domain_name FROM ssl_certificates s LEFT JOIN domains d ON s.domain_id = d.id ORDER BY s.created_at DESC");
$domains = $db->fetchAll("SELECT id, domain_name FROM domains ORDER BY domain_name");
?>
<div class="page-header">
    <div><h1 class="page-title">SSL Certificates</h1><p class="page-subtitle">Manage SSL/TLS certificates</p></div>
    <button class="btn btn-primary" onclick="showAddSsl()">Add Certificate</button>
</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Domain</th><th>Issuer</th><th>Valid From</th><th>Valid To</th><th>Auto-Renew</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($certs)): ?>
                    <tr><td colspan="6" class="empty-state"><h3>No SSL certificates</h3></td></tr>
                <?php else: foreach ($certs as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['domain_name'] ?? 'N/A') ?></strong></td>
                        <td><?= htmlspecialchars($c['issuer'] ?? 'Self-signed') ?></td>
                        <td><?= $c['valid_from'] ? date('M d, Y', strtotime($c['valid_from'])) : '-' ?></td>
                        <td><?= $c['valid_to'] ? date('M d, Y', strtotime($c['valid_to'])) : '-' ?></td>
                        <td><?= $c['auto_renew'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>' ?></td>
                        <td><div class="actions-cell">
                            <button class="btn btn-sm btn-outline" onclick="viewSsl(<?= $c['id'] ?>)">View</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSsl(<?= $c['id'] ?>)">Delete</button>
                        </div></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function showAddSsl() {
    var h = '<form id="sslForm">';
    h += '<div class="tabs" style="margin-bottom:16px"><button type="button" class="tab active" onclick="switchSslTab(this,\'letsencrypt\')">Let\'s Encrypt</button><button type="button" class="tab" onclick="switchSslTab(this,\'selfsigned\')">Self-Signed</button><button type="button" class="tab" onclick="switchSslTab(this,\'custom\')">Custom</button></div>';
    h += '<div id="tab-letsencrypt"><div class="form-group"><label class="form-label">Domain</label><select name="domain_id" class="form-control" required><option value="">Select</option><?php foreach ($domains as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option><?php endforeach; ?></select></div>';
    h += '<button type="button" class="btn btn-success" style="width:100%" onclick="issueLetsencrypt(this)">Issue Certificate</button></div>';
    h += '<div id="tab-selfsigned" style="display:none"><div class="form-group"><label class="form-label">Domain</label><select name="ss_domain_id" class="form-control"><option value="">Select</option><?php foreach ($domains as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option><?php endforeach; ?></select></div>';
    h += '<button type="button" class="btn btn-primary" style="width:100%" onclick="generateSelfSigned(this)">Generate Self-Signed</button></div>';
    h += '<div id="tab-custom" style="display:none"><div class="form-group"><label class="form-label">Domain</label><select name="cs_domain_id" class="form-control"><option value="">Select</option><?php foreach ($domains as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option><?php endforeach; ?></select></div>';
    h += '<div class="form-group"><label class="form-label">Certificate (PEM)</label><textarea name="cert" class="form-control" rows="5" placeholder="-----BEGIN CERTIFICATE-----"></textarea></div>';
    h += '<div class="form-group"><label class="form-label">Private Key (PEM)</label><textarea name="key" class="form-control" rows="5" placeholder="-----BEGIN PRIVATE KEY-----"></textarea></div>';
    h += '<div class="form-group"><label class="form-label">CA Bundle (optional)</label><textarea name="ca" class="form-control" rows="3"></textarea></div>';
    h += '<button type="button" class="btn btn-primary" style="width:100%" onclick="uploadCustomSsl(this)">Upload Certificate</button></div>';
    h += '</form>';
    openModal('SSL Certificate', h);
}
function switchSslTab(btn, tab) {
    btn.parentElement.querySelectorAll('.tab').forEach(function(t){t.classList.remove('active');});
    btn.classList.add('active');
    ['letsencrypt','selfsigned','custom'].forEach(function(t){document.getElementById('tab-'+t).style.display = t===tab?'block':'none';});
}
function issueLetsencrypt(btn) {
    var domainId = btn.closest('div').querySelector('select').value;
    if (!domainId) { showToast('danger','Select a domain'); return; }
    btn.disabled = true; btn.textContent = 'Issuing...';
    apiAction('ssl', 'letsencrypt', {domain_id: domainId}, function(r) {
        btn.disabled = false; btn.textContent = 'Issue Certificate';
        if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 1000); }
    });
}
function generateSelfSigned(btn) {
    var domainId = btn.closest('div').querySelector('select').value;
    if (!domainId) { showToast('danger','Select a domain'); return; }
    apiAction('ssl', 'selfsigned', {domain_id: domainId}, function(r) {
        if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
    });
}
function uploadCustomSsl(btn) {
    var container = btn.closest('div');
    var domainId = container.querySelector('select').value;
    var cert = container.querySelector('textarea[name="cert"]').value;
    var key = container.querySelector('textarea[name="key"]').value;
    var ca = container.querySelector('textarea[name="ca"]').value;
    if (!domainId || !cert || !key) { showToast('danger','Fill in required fields'); return; }
    ajaxPost('api/ssl.php', {action:'custom', domain_id:domainId, cert:cert, key:key, ca:ca, csrf_token:getCsrfToken()}, function(r) {
        if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
        else showToast('danger', r.error || 'Failed');
    });
}
function viewSsl(id) {
    ajaxPost('api/ssl.php', {action:'view', id:id, csrf_token:getCsrfToken()}, function(r) {
        if (r.cert) {
            openModal('Certificate Details', '<pre style="font-family:var(--font-mono);font-size:12px;white-space:pre-wrap;background:var(--bg);padding:16px;border-radius:8px;max-height:400px;overflow-y:auto">' + encodeHtml(r.cert) + '</pre>', true);
        }
    });
}
function deleteSsl(id) { confirmDelete(function(){apiAction('ssl','delete',{id:id},function(r){if(r.success)setTimeout(reloadPage,500);});}); }
</script>
