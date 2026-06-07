<?php
$db = Database::getInstance();
$domains = $db->fetchAll("SELECT * FROM domains ORDER BY domain_name");
$nginxActive = System::serviceStatus('nginx');
$apacheActive = System::serviceStatus('apache2');
$serverType = $_GET['server'] ?? 'nginx';
?>
<div class="page-header">
    <div><h1 class="page-title">Web Server</h1><p class="page-subtitle">Nginx and Apache configuration</p></div>
    <div class="btn-group">
        <a href="index.php?m=nginx&server=nginx" class="btn <?= $serverType === 'nginx' ? 'btn-primary' : 'btn-outline' ?>">Nginx</a>
        <a href="index.php?m=nginx&server=apache" class="btn <?= $serverType === 'apache' ? 'btn-primary' : 'btn-outline' ?>">Apache</a>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:28px">
    <div class="stat-card">
        <div class="stat-label">Nginx Status</div>
        <div style="margin-top:8px"><span class="badge <?= $nginxActive ? 'badge-success' : 'badge-danger' ?>"><?= $nginxActive ? 'Running' : 'Stopped' ?></span></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Apache Status</div>
        <div style="margin-top:8px"><span class="badge <?= $apacheActive ? 'badge-success' : 'badge-danger' ?>"><?= $apacheActive ? 'Running' : 'Stopped' ?></span></div>
    </div>
    <div class="stat-card">
        <div class="btn-group" style="margin-top:8px">
            <button class="btn btn-sm btn-success" onclick="restartService('<?= $serverType ?>')">Restart <?= ucfirst($serverType) ?></button>
            <button class="btn btn-sm btn-outline" onclick="testConfig()">Test Config</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title"><?= ucfirst($serverType) ?> Virtual Hosts</span>
        <button class="btn btn-sm btn-primary" onclick="generateVhost()">Generate Vhost</button>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Domain</th><th>Root</th><th>PHP</th><th>Config File</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($domains as $d):
                    $confDir = $serverType === 'nginx' ? NGINX_CONF_DIR : APACHE_CONF_DIR;
                    $confFile = $confDir . '/' . $d['domain_name'] . '.conf';
                    $exists = file_exists($confFile);
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['domain_name']) ?></strong></td>
                        <td><code style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($d['document_root']) ?></code></td>
                        <td><span class="badge badge-info">PHP <?= $d['php_version'] ?></span></td>
                        <td><?= $exists ? '<span class="badge badge-success">Exists</span>' : '<span class="badge badge-warning">Missing</span>' ?></td>
                        <td><div class="actions-cell">
                            <button class="btn btn-sm btn-outline" onclick="editVhost('<?= htmlspecialchars($d['domain_name']) ?>','<?= $serverType ?>')">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteVhost('<?= htmlspecialchars($d['domain_name']) ?>','<?= $serverType ?>')">Delete</button>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function restartService(s) {
    apiAction('nginx', 'restart', {server: s}, function(r) {
        if (r.success) setTimeout(reloadPage, 1000);
    });
}
function testConfig() {
    apiAction('nginx', 'test', {}, function(r) {
        if (r.output) openModal('Config Test', '<pre style="font-family:var(--font-mono);font-size:12px;white-space:pre-wrap;background:var(--bg);padding:16px;border-radius:8px">' + encodeHtml(r.output) + '</pre>');
    });
}
function generateVhost() {
    var h = '<form id="vhostForm">';
    h += '<div class="form-group"><label class="form-label">Domain</label><select name="domain_id" class="form-control" required><option value="">Select</option><?php foreach ($domains as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option><?php endforeach; ?></select></div>';
    h += '<button type="submit" class="btn btn-primary" style="width:100%">Generate</button></form>';
    openModal('Generate Virtual Host', h);
    document.getElementById('vhostForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action','generate'); fd.append('server','<?= $serverType ?>'); fd.append('csrf_token', getCsrfToken());
        fetch('api/nginx.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
            if(r.success){showToast('success',r.message);closeModal();setTimeout(reloadPage,500);}
            else showToast('danger',r.error||'Failed');
        });
    };
}
function editVhost(domain, server) {
    apiAction('nginx', 'read', {domain: domain, server: server}, function(r) {
        if (r.content !== undefined) {
            var h = '<form id="vhostEditForm"><input type="hidden" name="domain" value="'+encodeHtml(domain)+'"><input type="hidden" name="server" value="'+server+'">';
            h += '<div class="form-group"><label class="form-label">Configuration File</label><textarea name="content" class="code-editor" rows="20">' + encodeHtml(r.content) + '</textarea></div>';
            h += '<button type="submit" class="btn btn-primary" style="width:100%">Save Configuration</button></form>';
            openModal('Edit: ' + domain + '.conf', h, true);
            document.getElementById('vhostEditForm').onsubmit = function(e) {
                e.preventDefault();
                var fd = new FormData(this); fd.append('action','save'); fd.append('csrf_token', getCsrfToken());
                fetch('api/nginx.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
                    if(r.success){showToast('success',r.message);closeModal();}
                    else showToast('danger',r.error||'Failed');
                });
            };
        }
    });
}
function deleteVhost(domain, server) {
    confirmDelete(function() {
        apiAction('nginx', 'delete', {domain: domain, server: server}, function(r) { if (r.success) setTimeout(reloadPage, 500); });
    });
}
</script>
