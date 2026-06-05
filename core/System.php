<?php
class System {
    public static function exec($command, &$returnCode = 0) {
        $output = [];
        exec(escapeshellcmd($command) . ' 2>&1', $output, $returnCode);
        return implode("\n", $output);
    }

    public static function execAsUser($command, $user = 'root') {
        return self::exec("sudo -u {$user} " . escapeshellcmd($command));
    }

    public static function serviceStatus($service) {
        $result = self::exec("systemctl is-active {$service}");
        return trim($result) === 'active';
    }

    public static function serviceRestart($service) {
        return self::exec("systemctl restart {$service}");
    }

    public static function serviceStart($service) {
        return self::exec("systemctl start {$service}");
    }

    public static function serviceStop($service) {
        return self::exec("systemctl stop {$service}");
    }

    public static function writeFile($path, $content) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($path, $content) !== false;
    }

    public static function readFile($path) {
        return file_exists($path) ? file_get_contents($path) : false;
    }

    public static function deleteFile($path) {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), ['.', '..']);
            foreach ($files as $file) {
                self::deleteFile($path . '/' . $file);
            }
            return rmdir($path);
        }
        return unlink($path);
    }

    public static function diskUsage($path) {
        $result = self::exec("du -sm {$path} 2>/dev/null | awk '{print $1}'");
        return (int) trim($result);
    }

    public static function totalDiskSpace() {
        $result = self::exec("df -BM / | tail -1 | awk '{print $2}'");
        return (int) trim($result);
    }

    public static function usedDiskSpace() {
        $result = self::exec("df -BM / | tail -1 | awk '{print $3}'");
        return (int) trim($result);
    }

    public static function availableDiskSpace() {
        $result = self::exec("df -BM / | tail -1 | awk '{print $4}'");
        return (int) trim($result);
    }

    public static function getMemoryUsage() {
        $total = (int) trim(self::exec("free -m | awk '/Mem:/{print $2}'"));
        $used = (int) trim(self::exec("free -m | awk '/Mem:/{print $3}'"));
        return ['total' => $total, 'used' => $used, 'free' => $total - $used];
    }

    public static function getCpuUsage() {
        $result = self::exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2}'");
        return (float) trim($result);
    }

    public static function getUptime() {
        return self::exec("uptime -p 2>/dev/null || uptime");
    }

    public static function getLoadAverage() {
        $result = self::exec("cat /proc/loadavg | awk '{print $1, $2, $3}'");
        return trim($result);
    }

    public static function runningProcesses() {
        $result = self::exec("ps aux --no-heading | wc -l");
        return (int) trim($result);
    }
}

?>
