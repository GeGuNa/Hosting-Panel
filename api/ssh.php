<?php
require_once '../config.php';
require_once '../core/Database.php';
require_once '../core/Auth.php';
require_once '../core/CSRF.php';
require_once '../core/System.php';
require_once '../core/Helper.php';


if (!Auth::check()) Helper::json(['error' => 'Unauthorized'], 401);
$db = Database::getInstance();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        Helper::requirePost();
        $name = Helper::sanitize($_POST['name']);
        $pubkey = trim($_POST['public_key']);
        if (!$name || !$pubkey) Helper::json(['error' => 'All fields required']);
        $fp = System::exec("echo '{$pubkey}' | ssh-keygen -lf - 2>/dev/null");
        $db->insert('ssh_keys', ['name' => $name, 'public_key' => $pubkey, 'fingerprint' => trim($fp), 'is_active' => true]);
        System::exec("echo '{$pubkey}' >> " . SSH_KEYS_DIR);
        $db->log(Auth::userId(), 'add', 'ssh', $name);
        Helper::json(['success' => true, 'message' => 'SSH key added']);
        break;
    case 'toggle':
        $id = (int)$_POST['id'];
        $key = $db->fetchOne("SELECT * FROM ssh_keys WHERE id = ?", [$id]);
        if ($key) {
            $newStatus = !$key['is_active'];
            $db->update('ssh_keys', ['is_active' => $newStatus], 'id = ?', [$id]);
            $allKeys = $db->fetchAll("SELECT public_key FROM ssh_keys WHERE is_active = true");
            $content = implode("\n", array_column($allKeys, 'public_key')) . "\n";
            System::writeFile(SSH_KEYS_DIR, $content);
        }
        $db->log(Auth::userId(), 'toggle', 'ssh', "ID: {$id}");
        Helper::json(['success' => true, 'message' => 'Key status toggled']);
        break;
    case 'delete':
        $id = (int)$_POST['id'];
        $db->delete('ssh_keys', 'id = ?', [$id]);
        $allKeys = $db->fetchAll("SELECT public_key FROM ssh_keys WHERE is_active = true");
        $content = implode("\n", array_column($allKeys, 'public_key')) . "\n";
        System::writeFile(SSH_KEYS_DIR, $content);
        $db->log(Auth::userId(), 'delete', 'ssh', "ID: {$id}");
        Helper::json(['success' => true, 'message' => 'SSH key deleted']);
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
?>
