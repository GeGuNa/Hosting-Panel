<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'CPNLALT');
define('DB_USER', 'CPNLALT');
define('DB_PASS', 'ChangeThisPassword123!');

define('PANEL_URL', 'http://site.com/panel');
define('PANEL_NAME', 'CPNLALT');
define('PANEL_VERSION', '1.0.0');

define('NGINX_CONF_DIR', '/etc/nginx/sites-available');
define('NGINX_ENABLED_DIR', '/etc/nginx/sites-enabled');
define('APACHE_CONF_DIR', '/etc/apache2/sites-available');
define('APACHE_ENABLED_DIR', '/etc/apache2/sites-enabled');
define('DNS_ZONE_DIR', '/etc/bind/zones');
define('VHOST_WEBROOT', '/var/www');
define('FTP_BASE_DIR', '/var/www');
define('SSL_CERT_DIR', '/etc/ssl/CPNLALT');
define('SSH_KEYS_DIR', '/root/.ssh/authorized_keys');
define('PHP_VERSIONS', ['7.4', '8.0', '8.1', '8.2', '8.3']);

define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600);

?>
