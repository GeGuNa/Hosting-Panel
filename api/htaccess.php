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

	case 'save':
		Helper::requirePost();
		$domain = Helper::sanitize($_POST['domain']);
		$content = $_POST['content'];
		$path = VHOST_WEBROOT . '/' . $domain . '/.htaccess';
		System::writeFile($path, $content);
		$db->log(Auth::userId(), 'edit', 'htaccess', $domain);
		Helper::json(['success' => true, 'message' => '.htaccess saved']);
	break;
	
default: Helper::json(['error' => 'Invalid action'], 400);


}
?>
