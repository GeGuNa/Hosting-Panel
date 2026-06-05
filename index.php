<?php
require_once 'config.php';
require_once 'core/Database.php';
require_once 'core/Auth.php';
require_once 'core/CSRF.php';
require_once 'core/System.php';
require_once 'core/Helper.php';

$module = $_GET['m'] ?? 'dashboard';
$publicPages = ['login', 'logout', 'recover', 'reset'];

if (!in_array($module, $publicPages) && !Auth::check()) {
    Helper::redirect('index.php?m=login');
}

if ($module === 'logout') {
    Auth::logout();
}

$moduleFile = __DIR__ . '/modules/' . $module . '.php';
if (!file_exists($moduleFile)) {
    $moduleFile = __DIR__ . '/modules/dashboard.php';
    $module = 'dashboard';
}

if (!in_array($module, $publicPages)) {
    require_once 'includes/header.php';
    require_once 'includes/sidebar.php';
    echo '<main class="main-content" id="mainContent">';
    require_once $moduleFile;
    echo '</main>';
    require_once 'includes/footer.php';
} else {
    require_once $moduleFile;
}

?>
