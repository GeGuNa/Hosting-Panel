<?php
$db = Database::getInstance();
$zones = $db->fetchAll("SELECT z.*, d.domain_name FROM dns_zones z LEFT JOIN domains d ON z.domain_id = d.id ORDER BY z.created_at DESC");
$domains = $db->fetchAll("SELECT id, domain_name FROM domains ORDER BY domain_name");
$selectedZone = (int)($_GET['zone'] ?? 0);
$records = [];
if ($selectedZone) {
    $records = $db->fetchAll("SELECT * FROM dns_records WHERE zone_id = ? ORDER BY record_type, host", [$selectedZone]);
}
?>
<div class="page-header">
    <div><h1 class="page-title">DNS Management</h1><p class="page-subtitle">Bind9 zone records</p></div>
    <div class="btn-group">
        <button class="btn btn-outline" onclick="showAddZone()">Add Zone</button>
        <?php if ($selectedZone): ?><button class="btn btn-primary" onclick="showAddRecord()">Add Record</button><?php endif; ?>
    </div>
</div>

<div class="tabs">
    <a href="index.php?m=dns" class="tab <?= !$selectedZone ? 'active' : '' ?>">All Zones</a>
    <?php foreach ($zones as $z): ?>
        <a href="index.php?m=dns&zone=<?= $z['id'] ?>" class="tab <?= $selectedZone == $z['id'] ? 'active' : '' ?>"><?= htmlspecialchars($z['zone_name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <?php if ($selectedZone && !empty($records)): ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Type</th><th>Host</th><th>Value</th><th>TTL</th><th>Priority</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($r['record_type']) ?></span></td>
                        <td><code style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($r['host']) ?></code></td>
                        <td><code style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($r['value']) ?></code></td>
                        <td><?= $r['ttl'] ?></td>
                        <td><?= $r['priority'] ?? '-' ?></td>
                        <td><div class="actions-cell">
                            <button class="btn btn-sm btn-outline" onclick='editRecord(<?= json_encode($r) ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteRecord(<?= $r['id'] ?>)">Delete</button>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($selectedZone): ?>
            <div class="empty-state"><h3>No records</h3><p>Add your first DNS record.</p></div>
        <?php else: ?>
            <div class="empty-state"><h3>Select a zone</h3><p>Click on a zone tab above to manage its records.</p></div>
        <?php endif; ?>
    </div>
</div>
<script>
function showAddZone() {
    var html = '<form id="zoneForm"><div class="form-group"><label class="form-label">Domain</label><select name="domain_id" class="form-control" required><option value="">Select domain</option><?php foreach ($domains as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option><?php endforeach; ?></select></div>';
    html += '<div class="form-group"><label class="form-label">Zone Name</label><input type="text" name="zone_name" class="form-control" placeholder="example.com"></div>';
    html += '<button type="submit" class="btn btn-primary" style="width:100%">Create Zone</button></form>';
    openModal('Add DNS Zone', html);
    document.getElementById('zoneForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action', 'add_zone'); fd.append('csrf_token', getCsrfToken());
        fetch('api/dns.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}
function showAddRecord() {
    var html = '<form id="recordForm"><input type="hidden" name="zone_id" value="<?= $selectedZone ?>">';
    html += '<div class="form-row"><div class="form-group"><label class="form-label">Type</label><select name="record_type" class="form-control"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option><option>NS</option><option>SRV</option><option>CAA</option></select></div>';
    html += '<div class="form-group"><label class="form-label">TTL</label><input type="number" name="ttl" class="form-control" value="3600"></div></div>';
    html += '<div class="form-group"><label class="form-label">Host</label><input type="text" name="host" class="form-control" placeholder="@ or subdomain"></div>';
    html += '<div class="form-group"><label class="form-label">Value</label><input type="text" name="value" class="form-control" placeholder="192.168.1.1"></div>';
    html += '<div class="form-group"><label class="form-label">Priority (MX/SRV)</label><input type="number" name="priority" class="form-control" placeholder="10"></div>';
    html += '<button type="submit" class="btn btn-primary" style="width:100%">Add Record</button></form>';
    openModal('Add DNS Record', html);
    document.getElementById('recordForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action', 'add_record'); fd.append('csrf_token', getCsrfToken());
        fetch('api/dns.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}
function editRecord(r) {
    var html = '<form id="recordEditForm"><input type="hidden" name="id" value="' + r.id + '">';
    html += '<div class="form-row"><div class="form-group"><label class="form-label">Type</label><select name="record_type" class="form-control">';['A','AAAA','CNAME','MX','TXT','NS','SRV','CAA'].forEach(function(t){html+='<option'+(t===r.record_type?' selected':'')+'>'+t+'</option>';});html += '</select></div>';
    html += '<div class="form-group"><label class="form-label">TTL</label><input type="number" name="ttl" class="form-control" value="' + r.ttl + '"></div></div>';
    html += '<div class="form-group"><label class="form-label">Host</label><input type="text" name="host" class="form-control" value="' + encodeHtml(r.host) + '"></div>';
    html += '<div class="form-group"><label class="form-label">Value</label><input type="text" name="value" class="form-control" value="' + encodeHtml(r.value) + '"></div>';
    html += '<div class="form-group"><label class="form-label">Priority</label><input type="number" name="priority" class="form-control" value="' + (r.priority || '') + '"></div>';
    html += '<button type="submit" class="btn btn-primary" style="width:100%">Save</button></form>';
    openModal('Edit DNS Record', html);
    document.getElementById('recordEditForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action', 'edit_record'); fd.append('csrf_token', getCsrfToken());
        fetch('api/dns.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}
function deleteRecord(id) { confirmDelete(function(){ apiAction('dns', 'delete_record', {id: id}, function(r){ if(r.success) setTimeout(reloadPage, 500); }); }); }
</script>
