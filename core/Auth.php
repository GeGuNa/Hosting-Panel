<?php
class Auth {
    public static function attempt($username, $password) {
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM admin_users WHERE username = ? AND is_active = true", [$username]);
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_activity'] = time();
            $db->update('admin_users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            $db->log($user['id'], 'login', 'auth', 'Successful login');
            return true;
        }
        return false;
    }

    public static function check() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function user() {
        if (!self::check()) return null;
        return Database::getInstance()->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$_SESSION['user_id']]);
    }

    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            Database::getInstance()->log($_SESSION['user_id'], 'logout', 'auth');
        }
        session_destroy();
        header('Location: index.php?m=login');
        exit;
    }

    public static function generateResetToken($email) {
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT id FROM admin_users WHERE email = ?", [$email]);
        if (!$user) return false;
        $token = bin2hex(random_bytes(32));
        $db->update('admin_users', [
            'reset_token' => password_hash($token, PASSWORD_DEFAULT),
            'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ], 'id = ?', [$user['id']]);
        return $token;
    }

    public static function resetPassword($email, $token, $newPassword) {
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM admin_users WHERE email = ? AND reset_expires > NOW()", [$email]);
        if (!$user || !password_verify($token, $user['reset_token'])) {
            return false;
        }
        $db->update('admin_users', [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_expires' => null
        ], 'id = ?', [$user['id']]);
        return true;
    }

    public static function userId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function username() {
        return $_SESSION['username'] ?? null;
    }
}
?>
