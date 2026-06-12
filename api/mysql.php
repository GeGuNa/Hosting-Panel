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
        $dbName = Helper::sanitize($_POST['db_name']);
        $dbUser = Helper::sanitize($_POST['db_user']);
        $dbPass = $_POST['db_password'];
        $domainId = (int)($_POST['domain_id'] ?: 0);
        System::exec("mysql -e \"CREATE DATABASE \\`{$dbName}\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"");
        System::exec("mysql -e \"CREATE USER '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}'; GRANT ALL PRIVILEGES ON \\`{$dbName}\\`.* TO '{$dbUser}'@'localhost'; FLUSH PRIVILEGES;\"");
        $db->insert('databases_mysql', ['db_name' => $dbName, 'db_user' => $dbUser, 'db_password' => password_hash($dbPass, PASSWORD_DEFAULT), 'domain_id' => $domainId ?: null]);
        $db->log(Auth::userId(), 'add', 'mysql', $dbName);
        Helper::json(['success' => true, 'message' => 'MySQL database created']);
        break;
    case 'change_pass':
        Helper::requirePost();
        $id = (int)$_POST['id'];
        $newPass = $_POST['db_password'];
        $record = $db->fetchOne("SELECT * FROM databases_mysql WHERE id = ?", [$id]);
        if (!$record) Helper::json(['error' => 'Database not found']);
        System::exec("mysql -e \"ALTER USER '{$record['db_user']}'@'localhost' IDENTIFIED BY '{$newPass}'; FLUSH PRIVILEGES;\"");
        $db->update('databases_mysql', ['db_password' => password_hash($newPass, PASSWORD_DEFAULT)], 'id = ?', [$id]);
        $db->log(Auth::userId(), 'change_pass', 'mysql', $record['db_name']);
        Helper::json(['success' => true, 'message' => 'Password changed']);
        break;
    case 'delete':
        $id = (int)$_POST['id'];
        $record = $db->fetchOne("SELECT * FROM databases_mysql WHERE id = ?", [$id]);
        if ($record) {
            System::exec("mysql -e \"DROP DATABASE IF EXISTS \\`{$record['db_name']}\\`; DROP USER IF EXISTS '{$record['db_user']}'@'localhost'; FLUSH PRIVILEGES;\"");
        }
        $db->delete('databases_mysql', 'id = ?', [$id]);
        $db->log(Auth::userId(), 'delete', 'mysql', $record['db_name'] ?? '');
        Helper::json(['success' => true, 'message' => 'Database deleted']);
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
