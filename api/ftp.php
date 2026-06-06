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
        $user = Helper::sanitize($_POST['username']);
        $pass = $_POST['password'];
        $home = Helper::sanitize($_POST['home_dir']);
        $domainId = (int)($_POST['domain_id'] ?: 0);
        $quota = (int)($_POST['quota_mb'] ?? 1024);
        if (!$user || !$pass || !$home) Helper::json(['error' => 'All fields required']);
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->insert('ftp_accounts', ['username' => $user, 'password' => $hash, 'home_dir' => $home, 'domain_id' => $domainId ?: null, 'is_active' => true, 'quota_mb' => $quota]);
        System::exec("useradd -d {$home} -s /bin/false -M {$user} 2>/dev/null");
        System::exec("echo '{$user}:{$pass}' | chpasswd 2>/dev/null");
        System::exec("mkdir -p {$home} && chown {$user}:www-data {$home}");
        $db->log(Auth::userId(), 'add', 'ftp', $user);
        Helper::json(['success' => true, 'message' => 'FTP account created']);
        break;
    case 'edit':
        Helper::requirePost();
        $id = (int)$_POST['id'];
        $data = ['username' => Helper::sanitize($_POST['username']), 'home_dir' => Helper::sanitize($_POST['home_dir']), 'domain_id' => (int)($_POST['domain_id'] ?: 0) ?: null, 'quota_mb' => (int)($_POST['quota_mb'] ?? 1024), 'is_active' => isset($_POST['is_active'])];
        $pass = $_POST['password'] ?? '';
        if ($pass) $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
        $db->update('ftp_accounts', $data, 'id = ?', [$id]);
        if ($pass) {
            $u = $db->fetchOne("SELECT username FROM ftp_accounts WHERE id = ?", [$id]);
            System::exec("echo '{$u['username']}:{$pass}' | chpasswd 2>/dev/null");
        }
        $db->log(Auth::userId(), 'edit', 'ftp', "ID: {$id}");
        Helper::json(['success' => true, 'message' => 'FTP account updated']);
        break;
    case 'delete':
        $id = (int)$_POST['id'];
        $u = $db->fetchOne("SELECT username FROM ftp_accounts WHERE id = ?", [$id]);
        $db->delete('ftp_accounts', 'id = ?', [$id]);
        if ($u) System::exec("userdel {$u['username']} 2>/dev/null");
        $db->log(Auth::userId(), 'delete', 'ftp', $u['username'] ?? '');
        Helper::json(['success' => true, 'message' => 'FTP account deleted']);
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
?>
