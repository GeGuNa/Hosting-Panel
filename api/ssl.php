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
    case 'letsencrypt':
        $domainId = (int)$_POST['domain_id'];
        $domain = $db->fetchOne("SELECT domain_name FROM domains WHERE id = ?", [$domainId]);
        if (!$domain) Helper::json(['error' => 'Domain not found']);
        $result = System::exec("certbot --nginx -d {$domain['domain_name']} -d www.{$domain['domain_name']} --non-interactive --agree-tos --register-unsafely-without-email 2>&1");
        $certPath = "/etc/letsencrypt/live/{$domain['domain_name']}/fullchain.pem";
        $keyPath = "/etc/letsencrypt/live/{$domain['domain_name']}/privkey.pem";
        if (file_exists($certPath)) {
            $db->insert('ssl_certificates', ['domain_id' => $domainId, 'cert_path' => $certPath, 'key_path' => $keyPath, 'issuer' => "Let's Encrypt", 'valid_from' => date('Y-m-d H:i:s'), 'valid_to' => date('Y-m-d H:i:s', strtotime('+90 days')), 'is_active' => true, 'auto_renew' => true]);
            $db->update('domains', ['ssl_enabled' => true], 'id = ?', [$domainId]);
            $db->log(Auth::userId(), 'letsencrypt', 'ssl', $domain['domain_name']);
            Helper::json(['success' => true, 'message' => 'SSL certificate issued']);
        } else {
            Helper::json(['error' => 'Failed to issue certificate: ' . substr($result, -200)]);
        }
        break;
    case 'selfsigned':
        $domainId = (int)$_POST['domain_id'];
        $domain = $db->fetchOne("SELECT domain_name FROM domains WHERE id = ?", [$domainId]);
        if (!$domain) Helper::json(['error' => 'Domain not found']);
        $certDir = SSL_CERT_DIR . '/' . $domain['domain_name'];
        System::exec("mkdir -p {$certDir}");
        System::exec("openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout {$certDir}/privkey.pem -out {$certDir}/fullchain.pem -subj '/CN={$domain['domain_name']}' 2>&1");
        $db->insert('ssl_certificates', ['domain_id' => $domainId, 'cert_path' => "{$certDir}/fullchain.pem", 'key_path' => "{$certDir}/privkey.pem", 'issuer' => 'Self-Signed', 'valid_from' => date('Y-m-d H:i:s'), 'valid_to' => date('Y-m-d H:i:s', strtotime('+365 days')), 'is_active' => true, 'auto_renew' => false]);
        $db->update('domains', ['ssl_enabled' => true], 'id = ?', [$domainId]);
        $db->log(Auth::userId(), 'selfsigned', 'ssl', $domain['domain_name']);
        Helper::json(['success' => true, 'message' => 'Self-signed certificate generated']);
        break;
    case 'custom':
        $domainId = (int)$_POST['domain_id'];
        $cert = $_POST['cert'];
        $key = $_POST['key'];
        $ca = $_POST['ca'] ?? '';
        $domain = $db->fetchOne("SELECT domain_name FROM domains WHERE id = ?", [$domainId]);
        if (!$domain) Helper::json(['error' => 'Domain not found']);
        $certDir = SSL_CERT_DIR . '/' . $domain['domain_name'];
        System::exec("mkdir -p {$certDir}");
        System::writeFile("{$certDir}/fullchain.pem", $cert);
        System::writeFile("{$certDir}/privkey.pem", $key);
        if ($ca) System::writeFile("{$certDir}/ca.pem", $ca);
        $db->insert('ssl_certificates', ['domain_id' => $domainId, 'cert_path' => "{$certDir}/fullchain.pem", 'key_path' => "{$certDir}/privkey.pem", 'ca_path' => $ca ? "{$certDir}/ca.pem" : null, 'issuer' => 'Custom', 'valid_from' => date('Y-m-d H:i:s'), 'valid_to' => null, 'is_active' => true, 'auto_renew' => false]);
        $db->update('domains', ['ssl_enabled' => true], 'id = ?', [$domainId]);
        $db->log(Auth::userId(), 'custom_ssl', 'ssl', $domain['domain_name']);
        Helper::json(['success' => true, 'message' => 'Custom certificate installed']);
        break;
    case 'view':
        $id = (int)$_POST['id'];
        $cert = $db->fetchOne("SELECT * FROM ssl_certificates WHERE id = ?", [$id]);
        $content = $cert ? System::readFile($cert['cert_path']) : '';
        Helper::json(['cert' => $content ?: 'Certificate not found on disk']);
        break;
    case 'delete':
        $id = (int)$_POST['id'];
        $cert = $db->fetchOne("SELECT * FROM ssl_certificates WHERE id = ?", [$id]);
        if ($cert) {
            @unlink($cert['cert_path']);
            @unlink($cert['key_path']);
            if ($cert['ca_path']) @unlink($cert['ca_path']);
            $db->update('domains', ['ssl_enabled' => false], 'id = ?', [$cert['domain_id']]);
        }
        $db->delete('ssl_certificates', 'id = ?', [$id]);
        $db->log(Auth::userId(), 'delete_ssl', 'ssl', "ID: {$id}");
        Helper::json(['success' => true, 'message' => 'Certificate deleted']);
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
?>
