<?php
$db = Database::getInstance();
$accounts = $db->fetchAll("SELECT e.*, d.domain_name FROM email_accounts e LEFT JOIN domains d ON e.domain_id = d.id ORDER BY e.created_at DESC");
$domains = $db->fetchAll("SELECT id, domain_name FROM domains ORDER BY domain_name");
?>
<div class="page-header">
    <div><h1 class="page-title">Email Accounts</h1><p class="page-subtitle">Manage email accounts for Postfix/Dovecot</p></div>
    <button class="btn btn-primary" onclick="showAddEmail()">Add Email Account</button>
</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Email</th><th>Domain</th><th>Quota</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($accounts)): ?>
                    <tr><td colspan="6" class="empty-state"><h3>No email accounts</h3></td></tr>
                <?php else: foreach ($accounts as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['email_address']) ?></strong></td>
                        <td><?= htmlspecialchars($a['domain_name'] ?? '') ?></td>
                        <td><?= $a['quota_mb'] ?>MB</td>
                        <td><?= $a['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Disabled</span>' ?></td>
                        <td><?= Helper::timeAgo($a['created_at']) ?></td>
                        <td><div class="actions-cell">
                            <button class="btn btn-sm btn-outline" onclick='editEmail(<?= json_encode($a) ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteEmail(<?= $a['id'] ?>)">Delete</button>
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
function showAddEmail() {
    var h = '<form id="emailForm"><div class="form-row"><div class="form-group"><label class="form-label">Domain</label><select name="domain_id" class="form-control" required><option value="">Select</option>';
    domains.forEach(function(d){h+='<option value="'+d.id+'">'+encodeHtml(d.domain_name)+'</option>';});
    h += '</select></div><div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" placeholder="user" required></div></div>';
    h += '<div class="form-row"><div class="form-group"><label class="form-label">Password</label><input type="text" name="password" class="form-control" value="'+Math.random().toString(36).slice(-12)+'"></div>';
    h += '<div class="form-group"><label class="form-label">Quota (MB)</label><input type="number" name="quota_mb" class="form-control" value="1024"></div></div>';
    h += '<button type="submit" class="btn btn-primary" style="width:100%">Create Account</button></form>';
    openModal('Add Email Account', h);
    document.getElementById('emailForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action','add'); fd.append('csrf_token', getCsrfToken());
        fetch('api/email.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
            if(r.success){showToast('success',r.message);closeModal();setTimeout(reloadPage,500);}
            else showToast('danger',r.error||'Failed');
        });
    };
}
function editEmail(a) {
    var h = '<form id="emailEditForm"><input type="hidden" name="id" value="'+a.id+'">';
    h += '<div class="form-group"><label class="form-label">New Password (blank to keep)</label><input type="text" name="password" class="form-control"></div>';
    h += '<div class="form-group"><label class="form-label">Quota (MB)</label><input type="number" name="quota_mb" class="form-control" value="'+a.quota_mb+'"></div>';
    h += '<div class="form-group"><label class="form-label"><input type="checkbox" name="is_active" value="1"'+(a.is_active?' checked':'')+'> Active</label></div>';
    h += '<button type="submit" class="btn btn-primary" style="width:100%">Save</button></form>';
    openModal('Edit Email', h);
    document.getElementById('emailEditForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action','edit'); fd.append('csrf_token', getCsrfToken());
        fetch('api/email.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
            if(r.success){showToast('success',r.message);closeModal();setTimeout(reloadPage,500);}
            else showToast('danger',r.error||'Failed');
        });
    };
}
function deleteEmail(id){confirmDelete(function(){apiAction('email','delete',{id:id},function(r){if(r.success)setTimeout(reloadPage,500);});});}
</script>
