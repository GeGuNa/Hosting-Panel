<?php
$db = Database::getInstance();
$databases = $db->fetchAll("SELECT m.*, d.domain_name FROM databases_mysql m LEFT JOIN domains d ON m.domain_id = d.id ORDER BY m.created_at DESC");
$domains = $db->fetchAll("SELECT id, domain_name FROM domains ORDER BY domain_name");
?>
<div class="page-header">
    <div><h1 class="page-title">MySQL Databases</h1><p class="page-subtitle">Manage MySQL databases and users</p></div>
    <button class="btn btn-primary" onclick="showAddMysql()">Create Database</button>
</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Database</th><th>Username</th><th>Domain</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($databases)): ?>
                    <tr><td colspan="5" class="empty-state"><h3>No MySQL databases</h3></td></tr>
                <?php else: foreach ($databases as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['db_name']) ?></strong></td>
                        <td><?= htmlspecialchars($d['db_user']) ?></td>
                        <td><?= htmlspecialchars($d['domain_name'] ?? 'N/A') ?></td>
                        <td><?= Helper::timeAgo($d['created_at']) ?></td>
                        <td><div class="actions-cell">
                            <button class="btn btn-sm btn-outline" onclick="changeMysqlPass(<?= $d['id'] ?>)">Change Pass</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteMysql(<?= $d['id'] ?>)">Delete</button>
                        </div></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function showAddMysql() {
    var h = '<form id="mysqlForm"><div class="form-row"><div class="form-group"><label class="form-label">Database Name</label><input type="text" name="db_name" class="form-control" required></div>';
    h += '<div class="form-group"><label class="form-label">Username</label><input type="text" name="db_user" class="form-control" required></div></div>';
    h += '<div class="form-row"><div class="form-group"><label class="form-label">Password</label><input type="text" name="db_password" class="form-control" value="'+Math.random().toString(36).slice(-14)+'"></div>';
    h += '<div class="form-group"><label class="form-label">Domain</label><select name="domain_id" class="form-control"><option value="">None</option><?php foreach ($domains as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option><?php endforeach; ?></select></div></div>';
    h += '<button type="submit" class="btn btn-primary" style="width:100%">Create Database</button></form>';
    openModal('Create MySQL Database', h);
    document.getElementById('mysqlForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action','add'); fd.append('csrf_token',getCsrfToken());
        fetch('api/mysql.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
            if(r.success){showToast('success',r.message);closeModal();setTimeout(reloadPage,500);}
            else showToast('danger',r.error||'Failed');
        });
    };
}
function changeMysqlPass(id) {
    openModal('Change Password', '<form id="mysqlPassForm"><input type="hidden" name="id" value="'+id+'"><div class="form-group"><label class="form-label">New Password</label><input type="text" name="db_password" class="form-control" value="'+Math.random().toString(36).slice(-14)+'"></div><button type="submit" class="btn btn-primary" style="width:100%">Change</button></form>');
    document.getElementById('mysqlPassForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action','change_pass'); fd.append('csrf_token',getCsrfToken());
        fetch('api/mysql.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
            if(r.success){showToast('success',r.message);closeModal();}
            else showToast('danger',r.error||'Failed');
        });
    };
}
function deleteMysql(id) { confirmDelete(function(){apiAction('mysql','delete',{id:id},function(r){if(r.success)setTimeout(reloadPage,500);});}); }
</script>
