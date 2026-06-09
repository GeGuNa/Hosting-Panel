<?php
$db = Database::getInstance();
$keys = $db->fetchAll("SELECT * FROM ssh_keys ORDER BY created_at DESC");
?>
<div class="page-header">
    <div><h1 class="page-title">SSH Keys</h1><p class="page-subtitle">Manage authorized SSH keys</p></div>
    <button class="btn btn-primary" onclick="showAddKey()">Add SSH Key</button>
</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Name</th><th>Fingerprint</th><th>Public Key</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($keys)): ?>
                    <tr><td colspan="5" class="empty-state"><h3>No SSH keys</h3></td></tr>
                <?php else: foreach ($keys as $k): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($k['name']) ?></strong></td>
                        <td><code style="font-family:var(--font-mono);font-size:11px"><?= htmlspecialchars($k['fingerprint'] ?? '-') ?></code></td>
                        <td><code style="font-family:var(--font-mono);font-size:11px;max-width:300px;display:block;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars(substr($k['public_key'], 0, 60)) ?>...</code></td>
                        <td><?= $k['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Disabled</span>' ?></td>
                        <td><div class="actions-cell">
                            <button class="btn btn-sm btn-outline" onclick="toggleKey(<?= $k['id'] ?>)"><?= $k['is_active'] ? 'Disable' : 'Enable' ?></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteKey(<?= $k['id'] ?>)">Delete</button>
                        </div></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function showAddKey() {
    var h = '<form id="keyForm"><div class="form-group"><label class="form-label">Name</label><input type="text" name="name" class="form-control" placeholder="my-laptop" required></div>';
    h += '<div class="form-group"><label class="form-label">Public Key</label><textarea name="public_key" class="form-control" rows="4" placeholder="ssh-rsa AAAA..." required></textarea></div>';
    h += '<button type="submit" class="btn btn-primary" style="width:100%">Add Key</button></form>';
    openModal('Add SSH Key', h);
    document.getElementById('keyForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this); fd.append('action','add'); fd.append('csrf_token', getCsrfToken());
        fetch('api/ssh.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
            if(r.success){showToast('success',r.message);closeModal();setTimeout(reloadPage,500);}
            else showToast('danger',r.error||'Failed');
        });
    };
}
function toggleKey(id) { apiAction('ssh', 'toggle', {id: id}, function(r) { if (r.success) setTimeout(reloadPage, 500); }); }
function deleteKey(id) { confirmDelete(function(){apiAction('ssh','delete',{id:id},function(r){if(r.success)setTimeout(reloadPage,500);});}); }
</script>
