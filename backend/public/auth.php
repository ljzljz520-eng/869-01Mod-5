<?php
require_once __DIR__ . '/db.php';

session_start();

class Auth
{
    private const SESSION_KEY = 'admin_session';
    private const SESSION_DURATION = 86400;

    public static function check()
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        $session = $_SESSION[self::SESSION_KEY];
        if (!isset($session['admin_id']) || !isset($session['expires_at'])) {
            return false;
        }

        if (time() > $session['expires_at']) {
            self::logout();
            return false;
        }

        return true;
    }

    public static function requireLogin()
    {
        if (!self::check()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'error' => 'unauthorized',
                'message' => '请先登录'
            ]);
            exit;
        }
    }

    public static function login($username, $password)
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }

        if (!password_verify($password, $admin['password_hash'])) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }

        $adminId = $admin['id'];
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $expiresAt = time() + self::SESSION_DURATION;

        $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login_at = :lat, last_login_ip = :lip WHERE id = :id");
        $updateStmt->execute([
            ':lat' => date('Y-m-d H:i:s'),
            ':lip' => $ip,
            ':id' => $adminId
        ]);

        $sessionId = bin2hex(random_bytes(32));
        $insertStmt = $pdo->prepare("INSERT INTO admin_sessions (
            session_id, admin_id, ip_address, user_agent, expires_at
        ) VALUES (
            :sid, :aid, :ip, :ua, :exp
        )");
        $insertStmt->execute([
            ':sid' => $sessionId,
            ':aid' => $adminId,
            ':ip' => $ip,
            ':ua' => $ua,
            ':exp' => date('Y-m-d H:i:s', $expiresAt)
        ]);

        $_SESSION[self::SESSION_KEY] = [
            'admin_id' => $adminId,
            'username' => $admin['username'],
            'display_name' => $admin['display_name'],
            'session_id' => $sessionId,
            'expires_at' => $expiresAt,
            'ip' => $ip
        ];

        return [
            'success' => true,
            'admin' => [
                'id' => $adminId,
                'username' => $admin['username'],
                'display_name' => $admin['display_name']
            ]
        ];
    }

    public static function logout()
    {
        if (isset($_SESSION[self::SESSION_KEY]['session_id'])) {
            try {
                $pdo = Database::connect();
                $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE session_id = :sid");
                $stmt->execute([':sid' => $_SESSION[self::SESSION_KEY]['session_id']]);
            } catch (Exception $e) {
            }
        }
        unset($_SESSION[self::SESSION_KEY]);
        session_destroy();
    }

    public static function currentAdmin()
    {
        if (!self::check()) {
            return null;
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function verifyPassword($adminId, $password)
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $adminId]);
        $hash = $stmt->fetchColumn();
        return $hash && password_verify($password, $hash);
    }

    public static function changePassword($adminId, $oldPassword, $newPassword)
    {
        if (!self::verifyPassword($adminId, $oldPassword)) {
            return ['success' => false, 'message' => '原密码错误'];
        }
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '新密码长度不能少于6位'];
        }
        $pdo = Database::connect();
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :hash WHERE id = :id");
        $stmt->execute([':hash' => $newHash, ':id' => $adminId]);
        return ['success' => true, 'message' => '密码修改成功'];
    }

    public static function cleanupExpiredSessions()
    {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE expires_at < :now");
            $stmt->execute([':now' => date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
        }
    }
}

Auth::cleanupExpiredSessions();
