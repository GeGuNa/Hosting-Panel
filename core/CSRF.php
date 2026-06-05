<?php
class CSRF {
    public static function generate() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function field() {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . self::generate() . '">';
    }

    public static function verify($token = null) {
        $token = $token ?? $_POST[CSRF_TOKEN_NAME] ?? '';
        return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
    }

    public static function regenerate() {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
}

?>
