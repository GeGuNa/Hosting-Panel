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
    case 'read_file':
        $path = $_POST['path'] ?? '';
        $content = System::readFile($path);
        if ($content === false) Helper::json(['error' => 'Cannot read file']);
        if (strlen($content) > 2097152) $content = '[File too large to edit (' . Helper::formatBytes(strlen($content)) . ')]';
        Helper::json(['content' => $content]);
        break;
    case 'save_file':
        Helper::requirePost();
        $path = $_POST['path'];
        $content = $_POST['content'];
        if (System::writeFile($path, $content)) {
            $db->log(Auth::userId(), 'edit_file', 'filemanager', $path);
            Helper::json(['success' => true, 'message' => 'File saved']);
        } else {
            Helper::json(['error' => 'Failed to save file']);
        }
        break;
    case 'create_file':
        $dir = $_POST['dir'];
        $name = Helper::sanitize($_POST['name']);
        $path = $dir . '/' . $name;
        if (file_exists($path)) Helper::json(['error' => 'File already exists']);
        System::writeFile($path, '');
        $db->log(Auth::userId(), 'create_file', 'filemanager', $path);
        Helper::json(['success' => true, 'message' => 'File created']);
        break;
    case 'create_folder':
        $dir = $_POST['dir'];
        $name = Helper::sanitize($_POST['name']);
        $path = $dir . '/' . $name;
        if (mkdir($path, 0755)) {
            $db->log(Auth::userId(), 'create_folder', 'filemanager', $path);
            Helper::json(['success' => true, 'message' => 'Folder created']);
        } else {
            Helper::json(['error' => 'Failed to create folder']);
        }
        break;
    case 'chmod':
        $path = $_POST['path'];
        $mode = octdec($_POST['mode']);
        if (chmod($path, $mode)) {
            $db->log(Auth::userId(), 'chmod', 'filemanager', $path . ' ' . $_POST['mode']);
            Helper::json(['success' => true, 'message' => 'Permissions changed']);
        } else {
            Helper::json(['error' => 'Failed to change permissions']);
        }
        break;
    case 'delete':
        $path = $_POST['path'];
        if (System::deleteFile($path)) {
            $db->log(Auth::userId(), 'delete', 'filemanager', $path);
            Helper::json(['success' => true, 'message' => 'Deleted successfully']);
        } else {
            Helper::json(['error' => 'Failed to delete']);
        }
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
?>
