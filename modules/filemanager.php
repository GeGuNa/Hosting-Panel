<?php
$currentDir = $_GET['dir'] ?? VHOST_WEBROOT;
if (!is_dir($currentDir)) $currentDir = VHOST_WEBROOT;
$currentDir = realpath($currentDir);
?>
<div class="page-header">
    <div><h1 class="page-title">File Manager</h1><p class="page-subtitle"><?= htmlspecialchars($currentDir) ?></p></div>
    <div class="btn-group">
        <button class="btn btn-primary" onclick="showUpload()">Upload</button>
        <button class="btn btn-outline" onclick="createFile()">New File</button>
        <button class="btn btn-outline" onclick="createFolder()">New Folder</button>
    </div>
</div>

<div class="breadcrumb">
    <?php
    $parts = explode('/', trim($currentDir, '/'));
    $path = '';
    foreach ($parts as $i => $part) {
        $path .= '/' . $part;
        if ($i === count($parts) - 1) {
            echo '<span class="current">' . htmlspecialchars($part) . '</span>';
        } else {
            echo '<a href="index.php?m=filemanager&dir=' . urlencode($path) . '">' . htmlspecialchars($part) . '</a><span class="sep">/</span>';
        }
    }
    ?>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th style="width:40px"></th><th>Name</th><th>Size</th><th>Permissions</th><th>Modified</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                $parentDir = dirname($currentDir);
                if ($currentDir !== VHOST_WEBROOT && $currentDir !== '/'):
                ?>
                <tr>
                    <td><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></td>
                    <td><a href="index.php?m=filemanager&dir=<?= urlencode($parentDir) ?>"><strong>..</strong></a></td>
                    <td colspan="4"></td>
                </tr>
                <?php endif; ?>
                <?php
                $items = @scandir($currentDir);
                if ($items) {
                    usort($items, function($a, $b) use ($currentDir) {
                        $isDirA = is_dir($currentDir . '/' . $a);
                        $isDirB = is_dir($currentDir . '/' . $b);
                        if ($isDirA !== $isDirB) return $isDirB <=> $isDirA;
                        return strcasecmp($a, $b);
                    });
                    foreach ($items as $item):
                        if ($item === '.' || $item === '..') continue;
                        $fullPath = $currentDir . '/' . $item;
                        $isDir = is_dir($fullPath);
                        $size = $isDir ? '-' : Helper::formatBytes(filesize($fullPath));
                        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
                        $modified = date('M d, Y H:i', filemtime($fullPath));
                ?>
                <tr>
                    <td><?= $isDir ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>' : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>' ?></td>
                    <td>
                        <?php if ($isDir): ?>
                            <a href="index.php?m=filemanager&dir=<?= urlencode($fullPath) ?>"><strong><?= htmlspecialchars($item) ?></strong></a>
                        <?php else: ?>
                            <a href="#" onclick="editFile('<?= urlencode($fullPath) ?>');return false"><?= htmlspecialchars($item) ?></a>
                        <?php endif; ?>
                    </td>
                    <td><?= $size ?></td>
                    <td><code style="font-family:var(--font-mono);font-size:12px"><?= $perms ?></code></td>
                    <td><?= $modified ?></td>
                    <td><div class="actions-cell">
                        <?php if (!$isDir): ?>
                            <button class="btn btn-xs btn-outline" onclick="editFile('<?= urlencode($fullPath) ?>')">Edit</button>
                        <?php endif; ?>
                        <button class="btn btn-xs btn-outline" onclick="chmodItem('<?= urlencode($fullPath) ?>')">Chmod</button>
                        <button class="btn btn-xs btn-danger" onclick="deleteItem('<?= urlencode($fullPath) ?>','<?= $isDir ? 'dir' : 'file' ?>')">Delete</button>
                    </div></td>
                </tr>
                <?php endforeach; } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
var currentDir = '<?= addslashes($currentDir) ?>';
function showUpload() {
    var h = '<form id="uploadForm" enctype="multipart/form-data">';
    h += '<div class="form-group"><label class="form-label">Select Files</label><input type="file" name="files[]" id="uploadFiles" multiple class="form-control"></div>';
    h += '<button type="submit" class="btn btn-primary" style="width:100%">Upload</button></form>';
    openModal('Upload Files', h);
    document.getElementById('uploadForm').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('action', 'upload');
        fd.append('dir', currentDir);
        fd.append('csrf_token', getCsrfToken());
        fetch('api/upload.php', {method:'POST',body:fd}).then(r=>r.json()).then(function(r) {
            if (r.success) { showToast('success', r.message); closeModal(); setTimeout(reloadPage, 500); }
            else showToast('danger', r.error || 'Failed');
        });
    };
}
function createFile() {
    openModal('New File', '<form id="newFileForm"><div class="form-group"><label class="form-label">File Name</label><input type="text" name="name" class="form-control" placeholder="file.txt" required></div><button type="submit" class="btn btn-primary" style="width:100%">Create</button></form>');
    document.getElementById('newFileForm').onsubmit = function(e) {
        e.preventDefault();
        apiAction('filemanager', 'create_file', {dir: currentDir, name: this.name.value}, function(r) {
            if (r.success) { closeModal(); setTimeout(reloadPage, 500); }
        });
    };
}
function createFolder() {
    openModal('New Folder', '<form id="newFolderForm"><div class="form-group"><label class="form-label">Folder Name</label><input type="text" name="name" class="form-control" required></div><button type="submit" class="btn btn-primary" style="width:100%">Create</button></form>');
    document.getElementById('newFolderForm').onsubmit = function(e) {
        e.preventDefault();
        apiAction('filemanager', 'create_folder', {dir: currentDir, name: this.name.value}, function(r) {
            if (r.success) { closeModal(); setTimeout(reloadPage, 500); }
        });
    };
}
function editFile(path) {
    apiAction('filemanager', 'read_file', {path: path}, function(r) {
        if (r.content !== undefined) {
            var h = '<form id="editFileForm"><input type="hidden" name="path" value="'+decodeURIComponent(path)+'">';
            h += '<div class="form-group"><textarea name="content" class="code-editor" rows="20">' + encodeHtml(r.content) + '</textarea></div>';
            h += '<button type="submit" class="btn btn-primary" style="width:100%">Save</button></form>';
            openModal('Edit: ' + decodeURIComponent(path).split('/').pop(), h, true);
            document.getElementById('editFileForm').onsubmit = function(e) {
                e.preventDefault();
                var fd = new FormData(this); fd.append('action','save_file'); fd.append('csrf_token',getCsrfToken());
                fetch('api/filemanager.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(r){
                    if(r.success) { showToast('success',r.message); closeModal(); }
                    else showToast('danger',r.error||'Failed');
                });
            };
        }
    });
}
function chmodItem(path) {
    openModal('Change Permissions', '<form id="chmodForm"><input type="hidden" name="path" value="'+decodeURIComponent(path)+'"><div class="form-group"><label class="form-label">Permissions (e.g. 0755)</label><input type="text" name="mode" class="form-control" value="0644" required></div><button type="submit" class="btn btn-primary" style="width:100%">Apply</button></form>');
    document.getElementById('chmodForm').onsubmit = function(e) {
        e.preventDefault();
        apiAction('filemanager', 'chmod', {path: this.path.value, mode: this.mode.value}, function(r) {
            if (r.success) { closeModal(); setTimeout(reloadPage, 500); }
        });
    };
}
function deleteItem(path, type) {
    confirmDelete(function() {
        apiAction('filemanager', 'delete', {path: path, type: type}, function(r) { if (r.success) setTimeout(reloadPage, 500); });
    });
}
</script>
