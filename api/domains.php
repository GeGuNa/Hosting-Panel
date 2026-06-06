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
        $name = Helper::sanitize($_POST['domain_name'] ?? '');
        $root = Helper::sanitize($_POST['document_root'] ?? VHOST_WEBROOT . '/' . $name);
        $php = Helper::sanitize($_POST['php_version'] ?? '8.2');
        $server = Helper::sanitize($_POST['web_server'] ?? 'nginx');
        if (!Helper::isValidDomain($name)) { Helper::json(['error' => 'Invalid domain name']); }
        if (!$root) $root = VHOST_WEBROOT . '/' . $name;
        $existing = $db->fetchOne("SELECT id FROM domains WHERE domain_name = ?", [$name]);
        if ($existing) Helper::json(['error' => 'Domain already exists']);
        $id = $db->insert('domains', ['domain_name' => $name, 'document_root' => $root, 'php_version' => $php, 'ssl_enabled' => false, 'is_active' => true, 'web_server' => $server]);
        System::exec("mkdir -p {$root}");
        System::exec("chown -R www-data:www-data {$root}");
        $db->insert('dns_zones', ['domain_id' => $id, 'zone_name' => $name]);
        $db->log(Auth::userId(), 'add', 'domains', $name);
        Helper::json(['success' => true, 'message' => 'Domain added successfully']);
        break;
    case 'edit':
        Helper::requirePost();
        $id = (int)$_POST['id'];
        $name = Helper::sanitize($_POST['domain_name'] ?? '');
        $root = Helper::sanitize($_POST['document_root'] ?? '');
        $php = Helper::sanitize($_POST['php_version'] ?? '8.2');
        $server = Helper::sanitize($_POST['web_server'] ?? 'nginx');
        $active = isset($_POST['is_active']) ? true : false;
        $db->update('domains', ['domain_name' => $name, 'document_root' => $root, 'php_version' => $php, 'web_server' => $server, 'is_active' => $active], 'id = ?', [$id]);
        $db->log(Auth::userId(), 'edit', 'domains', $name);
        Helper::json(['success' => true, 'message' => 'Domain updated']);
        break;
    case 'delete':
        $id = (int)$_POST['id'];
        $domain = $db->fetchOne("SELECT domain_name FROM domains WHERE id = ?", [$id]);
        $db->delete('domains', 'id = ?', [$id]);
        $db->log(Auth::userId(), 'delete', 'domains', $domain['domain_name'] ?? '');
        Helper::json(['success' => true, 'message' => 'Domain deleted']);
        break;
    default:
        Helper::json(['error' => 'Invalid action'], 400);
}

?>
