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
function rebuildZoneFile($db, $zoneId) {
    $zone = $db->fetchOne("SELECT z.*, d.domain_name FROM dns_zones z LEFT JOIN domains d ON z.domain_id = d.id WHERE z.id = ?", [$zoneId]);
    if (!$zone) return;
    $records = $db->fetchAll("SELECT * FROM dns_records WHERE zone_id = ?", [$zoneId]);
    $serial = date('Ymd') . '01';
    $content = "\$TTL 86400\n@ IN SOA ns1.{$zone['zone_name']}. admin.{$zone['zone_name']}. (\n    {$serial} ; Serial\n    3600 ; Refresh\n    1800 ; Retry\n    604800 ; Expire\n    86400 ; Minimum TTL\n)\n\n";
    foreach ($records as $r) {
        $prio = $r['priority'] ? $r['priority'] . ' ' : '';
        $host = $r['host'] === '@' ? $zone['zone_name'] : $r['host'] . '.' . $zone['zone_name'];
        $content .= "{$host}. {$r['ttl']} IN {$r['record_type']} {$prio}{$r['value']}\n";
    }
    $file = DNS_ZONE_DIR . '/' . $zone['zone_name'] . '.zone';
    System::writeFile($file, $content);
    System::exec("rndc reload {$zone['zone_name']} 2>/dev/null || systemctl reload bind9 2>/dev/null");
}
switch ($action) {
    case 'add_zone':
        Helper::requirePost();
        $domainId = (int)$_POST['domain_id'];
        $zoneName = Helper::sanitize($_POST['zone_name']);
        if (!$zoneName) {
            $d = $db->fetchOne("SELECT domain_name FROM domains WHERE id = ?", [$domainId]);
            $zoneName = $d['domain_name'];
        }
        $db->insert('dns_zones', ['domain_id' => $domainId, 'zone_name' => $zoneName]);
        $db->log(Auth::userId(), 'add_zone', 'dns', $zoneName);
        Helper::json(['success' => true, 'message' => 'DNS zone created']);
        break;
    case 'add_record':
        Helper::requirePost();
        $data = [
            'zone_id' => (int)$_POST['zone_id'],
            'record_type' => Helper::sanitize($_POST['record_type']),
            'host' => Helper::sanitize($_POST['host']),
            'value' => Helper::sanitize($_POST['value']),
            'ttl' => (int)($_POST['ttl'] ?? 3600),
            'priority' => !empty($_POST['priority']) ? (int)$_POST['priority'] : null
        ];
        $db->insert('dns_records', $data);
        rebuildZoneFile($db, $data['zone_id']);
        $db->log(Auth::userId(), 'add_record', 'dns', $data['record_type'] . ' ' . $data['host']);
        Helper::json(['success' => true, 'message' => 'DNS record added']);
        break;
    case 'edit_record':
        Helper::requirePost();
        $id = (int)$_POST['id'];
        $data = [
            'record_type' => Helper::sanitize($_POST['record_type']),
            'host' => Helper::sanitize($_POST['host']),
            'value' => Helper::sanitize($_POST['value']),
            'ttl' => (int)($_POST['ttl'] ?? 3600),
            'priority' => !empty($_POST['priority']) ? (int)$_POST['priority'] : null
        ];
        $rec = $db->fetchOne("SELECT zone_id FROM dns_records WHERE id = ?", [$id]);
        $db->update('dns_records', $data, 'id = ?', [$id]);
        if ($rec) rebuildZoneFile($db, $rec['zone_id']);
        $db->log(Auth::userId(), 'edit_record', 'dns', "ID: {$id}");
        Helper::json(['success' => true, 'message' => 'DNS record updated']);
        break;
    case 'delete_record':
        $id = (int)$_POST['id'];
        $rec = $db->fetchOne("SELECT zone_id FROM dns_records WHERE id = ?", [$id]);
        $db->delete('dns_records', 'id = ?', [$id]);
        if ($rec) rebuildZoneFile($db, $rec['zone_id']);
        $db->log(Auth::userId(), 'delete_record', 'dns', "ID: {$id}");
        Helper::json(['success' => true, 'message' => 'DNS record deleted']);
        break;
    default: Helper::json(['error' => 'Invalid action'], 400);
}
?>
