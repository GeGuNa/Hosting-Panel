<?php
require_once '../config.php';
require_once '../core/Database.php';
require_once '../core/Auth.php';
require_once '../core/CSRF.php';
require_once '../core/System.php';
require_once '../core/Helper.php';
if (!Auth::check()) Helper::json(['error' => 'Unauthorized'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') Helper::json(['error' => 'Method not allowed'], 405);
if (!CSRF::verify()) Helper::json(['error' => 'Invalid CSRF token'], 403);
$dir = $_POST['dir'] ?? VHOST_WEBROOT;
$db = Database::getInstance();
$uploaded = 0;
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $i => $name) {
        $tmp = $_FILES['files']['tmp_name'][$i];
        $dest = $dir . '/' . basename($name);
        if (move_uploaded_file($tmp, $dest)) {
            $uploaded++;
        }
    }
}
$db->log(Auth::userId(), 'upload', 'filemanager', $uploaded . ' file(s) to ' . $dir);
Helper::json(['success' => true, 'message' => $uploaded . ' file(s) uploaded']);
?>
