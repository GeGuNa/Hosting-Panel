<?php
require_once '../config.php';
require_once '../core/Database.php';
require_once '../core/Auth.php';
require_once '../core/CSRF.php';
require_once '../core/System.php';
require_once '../core/Helper.php';
if (!Auth::check()) Helper::json(['error' => 'Unauthorized'], 401);
$db = Database::getInstance();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'restart':
        $server = Helper::sanitize($_POST['server'] ?? 'nginx');
        $service = $server === 'nginx' ? 'nginx' : 'apache2';
        $result = System::serviceRestart($service);
        $db->log(Auth::userId(), 'restart', 'webserver', $service);
        Helper::json(['success' => true, 'message' => ucfirst($server) . ' restarted']);
        break;
    case 'test':
        $result = System::exec("nginx -t 2>&1 || apache2ctl configtest 2>&1");
        Helper::json(['success' => true, 'output' => $result]);
        break;
    case 'generate':
        $domainId = (int)$_POST['domain_id'];
        $server = Helper::sanitize($_POST['server'] ?? 'nginx');
        $domain = $db->fetchOne("SELECT * FROM domains WHERE id = ?", [$domainId]);
        if (!$domain) Helper::json(['error' => 'Domain not found']);
        if ($server === 'nginx') {
            $conf = "server {\n    listen 80;\n    server_name {$domain['domain_name']} www.{$domain['domain_name']};\n    root {$domain['document_root']};\n    index index.php index.html;\n\n    location / {\n        try_files \$uri \$uri/ /index.php?\$args;\n    }\n\n    location ~ \\.php\$ {\n        include snippets/fastcgi-php.conf;\n        fastcgi_pass unix:/run/php/php{$domain['php_version']}-fpm.sock;\n    }\n\n    location ~ /\\.ht {\n        deny all;\n    }\n\n    access_log /var/log/nginx/{$domain['domain_name']}_access.log;\n    error_log /var/log/nginx/{$domain['domain_name']}_error.log;\n}";
            $file = NGINX_CONF_DIR . '/' . $domain['domain_name'] . '.conf';
            System::writeFile($file, $conf);
            @symlink($file, NGINX_ENABLED_DIR . '/' . $domain['domain_name'] . '.conf');
        } else {
            $conf = "<VirtualHost *:80>\n    ServerName {$domain['domain_name']}\n    ServerAlias www.{$domain['domain_name']}\n    DocumentRoot {$domain['document_root']}\n\n    <Directory {$domain['document_root']}>\n        AllowOverride All\n        Require all granted\n    </Directory>\n\n    ErrorLog \${APACHE_LOG_DIR}/{$domain['domain_name']}_error.log\n    CustomLog \${APACHE_LOG_DIR}/{$domain['domain_name']}_access.log combined\n</VirtualHost>";
            $file = APACHE_CONF_DIR . '/' . $domain['domain_name'] . '.conf';
            System::writeFile($file, $conf);
            System::exec("a2ensite {$domain['domain_name']}.conf 2>/dev/null");
        }
        $db->log(Auth::userId(), 'generate_vhost', 'webserver', $domain['domain_name']);
        Helper::json(['success' => true, 'message' => 'Virtual host generated']);
        break;
    case 'read':
        $domain = Helper::sanitize($_POST['domain'] ?? $_GET['domain'] ?? '');
        $server = Helper::sanitize($_POST['server'] ?? $_GET['server'] ?? 'nginx');
        $confDir = $server === 'nginx' ? NGINX_CONF_DIR : APACHE_CONF_DIR;
        $file = $confDir . '/' . $domain . '.conf';
        $content = System::readFile($file) ?: '';
        Helper::json(['content' => $content]);
        break;
    case 'save':
        Helper::requirePost();
        $domain = Helper::sanitize($_POST['domain']);
        $server = Helper::sanitize($_POST['server']);
        $content = $_POST['content'];
        $confDir = $server === 'nginx' ? NGINX_CONF_DIR : APACHE_CONF_DIR;
        $file = $confDir . '/' . $domain . '.conf';
        System::writeFile($file, $content);
        $db->log(Auth::userId(), 'edit_vhost', 'webserver', $domain);
        Helper::json(['success' => true, 'message' => 'Configuration saved']);
        break;
    case 'delete':
        $domain = Helper::sanitize($_POST['domain']);
        $server = Helper::sanitize($_POST['server']);
        if ($server === 'nginx') {
            @unlink(NGINX_ENABLED_DIR . '/' . $domain . '.conf');
            @unlink(NGINX_CONF_DIR . '/' . $domain . '.conf');
        } else {
            System::exec("a2dissite {$domain}.conf 2>/dev/null");
            @unlink(APACHE_CONF_DIR . '/' . $domain . '.conf');
        }
        $db->log(Auth::userId(), 'delete_vhost', 'webserver', $domain);
        Helper::json(['success' => true, 'message' => 'Virtual host removed']);
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
?>
