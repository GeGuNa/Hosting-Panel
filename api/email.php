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
        $domainId = (int)$_POST['domain_id'];
        $username = Helper::sanitize($_POST['username']);
        $pass = $_POST['password'];
        $quota = (int)($_POST['quota_mb'] ?? 1024);
        $domain = $db->fetchOne("SELECT domain_name FROM domains WHERE id = ?", [$domainId]);
        if (!$domain) Helper::json(['error' => 'Domain not found']);
        $email = $username . '@' . $domain['domain_name'];
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->insert('email_accounts', ['domain_id' => $domainId, 'email_address' => $email, 'password' => $hash, 'quota_mb' => $quota, 'is_active' => true]);
        System::exec("echo '{$email}|{$hash}' | postmap -r hash:/etc/postfix/vmailbox 2>/dev/null");
        System::exec("mkdir -p /var/mail/vhosts/{$domain['domain_name']}/{$username}");
        System::exec("chown -R vmail:vmail /var/mail/vhosts/{$domain['domain_name']}/{$username}");
        $db->log(Auth::userId(), 'add', 'email', $email);
        Helper::json(['success' => true, 'message' => 'Email account created: ' . $email]);
        break;
    case 'edit':
        Helper::requirePost();
        $id = (int)$_POST['id'];
        $data = ['quota_mb' => (int)($_POST['quota_mb'] ?? 1024), 'is_active' => isset($_POST['is_active'])];
        $pass = $_POST['password'] ?? '';
        if ($pass) $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
        $db->update('email_accounts', $data, 'id = ?', [$id]);
        $db->log(Auth::userId(), 'edit', 'email', "ID: {$id}");
        Helper::json(['success' => true, 'message' => 'Email account updated']);
        break;
    case 'delete':
        $id = (int)$_POST['id'];
        $email = $db->fetchOne("SELECT email_address FROM email_accounts WHERE id = ?", [$id]);
        $db->delete('email_accounts', 'id = ?', [$id]);
        $db->log(Auth::userId(), 'delete', 'email', $email['email_address'] ?? '');
        Helper::json(['success' => true, 'message' => 'Email account deleted']);
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
?>
