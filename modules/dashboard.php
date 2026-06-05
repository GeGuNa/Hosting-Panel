<?php
$db = Database::getInstance();
$domains = $db->fetchOne("SELECT COUNT(*) as c FROM domains")['c'];
$ftp = $db->fetchOne("SELECT COUNT(*) as c FROM ftp_accounts")['c'];
$emails = $db->fetchOne("SELECT COUNT(*) as c FROM email_accounts")['c'];
$dbs = $db->fetchOne("SELECT (SELECT COUNT(*) FROM databases_mysql) + (SELECT COUNT(*) FROM databases_pg) + (SELECT COUNT(*) FROM databases_mongo) as c")['c'];
$ssl = $db->fetchOne("SELECT COUNT(*) as c FROM ssl_certificates WHERE is_active = true")['c'];
$memory = System::getMemoryUsage();
$cpu = System::getCpuUsage();
$diskTotal = System::totalDiskSpace();
$diskUsed = System::usedDiskSpace();
$diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;
$recentLogs = $db->fetchAll("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
$nginxActive = System::serviceStatus('nginx');
$apacheActive = System::serviceStatus('apache2');
$postfixActive = System::serviceStatus('postfix');
$dovecotActive = System::serviceStatus('dovecot');
$bindActive = System::serviceStatus('named') || System::serviceStatus('bind9');
$vsftpdActive = System::serviceStatus('vsftpd');
$mysqlActive = System::serviceStatus('mysql') || System::serviceStatus('mysqld');
$pgActive = System::serviceStatus('postgresql');
$mongoActive = System::serviceStatus('mongod');
$memPercent = $memory['total'] > 0 ? round(($memory['used'] / $memory['total']) * 100) : 0;
$cpuColor = $cpu > 80 ? 'red' : ($cpu > 50 ? 'yellow' : 'green');
$memColor = $memPercent > 80 ? 'red' : ($memPercent > 50 ? 'yellow' : 'green');
$diskColor = $diskPercent > 80 ? 'red' : ($diskPercent > 50 ? 'yellow' : 'blue');
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">System overview and quick actions</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <div class="stat-value"><?= $domains ?></div>
        <div class="stat-label">Domains</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div class="stat-value"><?= $emails ?></div>
        <div class="stat-label">Email Accounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="stat-value"><?= $ftp ?></div>
        <div class="stat-label">FTP Accounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        </div>
        <div class="stat-value"><?= $dbs ?></div>
        <div class="stat-label">Databases</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div class="stat-value"><?= $ssl ?></div>
        <div class="stat-label">SSL Certificates</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px">
    <div class="card">
        <div class="card-header"><span class="card-title">System Resources</span></div>
        <div class="card-body">
            <div style="margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:13px;font-weight:600">CPU</span><span style="font-size:13px;color:var(--text-muted)"><?= round($cpu, 1) ?>%</span></div>
                <div class="progress-bar"><div class="progress-fill <?= $cpuColor ?>" style="width:<?= min($cpu, 100) ?>%"></div></div>
            </div>
            <div style="margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:13px;font-weight:600">Memory</span><span style="font-size:13px;color:var(--text-muted)"><?= $memory['used'] ?>MB / <?= $memory['total'] ?>MB</span></div>
                <div class="progress-bar"><div class="progress-fill <?= $memColor ?>" style="width:<?= $memPercent ?>%"></div></div>
            </div>
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:13px;font-weight:600">Disk</span><span style="font-size:13px;color:var(--text-muted)"><?= $diskUsed ?>MB / <?= $diskTotal ?>MB</span></div>
                <div class="progress-bar"><div class="progress-fill <?= $diskColor ?>" style="width:<?= $diskPercent ?>%"></div></div>
            </div>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between">
                <div><span style="font-size:12px;color:var(--text-muted)">Uptime</span><br><span style="font-size:13px;font-weight:600"><?= htmlspecialchars(System::getUptime()) ?></span></div>
                <div><span style="font-size:12px;color:var(--text-muted)">Load Avg</span><br><span style="font-size:13px;font-weight:600"><?= htmlspecialchars(System::getLoadAverage()) ?></span></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Services</span></div>
        <div class="card-body">
            <div class="services-grid">
                <?php
                $services = [
                    ['Nginx', $nginxActive], ['Apache', $apacheActive],
                    ['Postfix', $postfixActive], ['Dovecot', $dovecotActive],
                    ['BIND9', $bindActive], ['vsftpd', $vsftpdActive],
                    ['MySQL', $mysqlActive], ['PostgreSQL', $pgActive],
                    ['MongoDB', $mongoActive]
                ];
                foreach ($services as $s):
                ?>
                <div class="service-card">
                    <div class="service-name"><?= $s[0] ?></div>
                    <div class="service-status <?= $s[1] ? 'badge badge-success' : 'badge badge-danger' ?>"><?= $s[1] ? 'Running' : 'Stopped' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Recent Activity</span></div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Action</th><th>Module</th><th>Details</th><th>IP</th><th>Time</th></tr></thead>
                <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px">No activity recorded yet</td></tr>
                <?php else: foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td><?= htmlspecialchars($log['module']) ?></td>
                        <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                        <td><code style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($log['ip_address']) ?></code></td>
                        <td><?= Helper::timeAgo($log['created_at']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


