<?php

class AuthController {
    public const TOKEN_EXPIRED = -1;
    public const TOKEN_NOT_EXIST = -2;

    public static function login() {
        ['username' => $username, 'password' => $password] = Flight::request()->data;
        if (!$username) Flight::jsonHalt(['error' => '未提供用户名'], 406);
        if (!$password) Flight::jsonHalt(['error' => '未提供密码'], 406);

        $user = Flight::db()->fetchRow('SELECT id, username, password FROM users WHERE username = :username', ['username' => $username]);
        if (!$user->count() || !password_verify($password, $user['password'])) {
            Flight::jsonHalt(['error' => '用户名或密码错误'], 406);
        }
        $token = self::generateToken($user['id']);

        Flight::json(['success' => '登录成功', 'username' => $user['username'], 'token' => $token]);
    }

    public static function register() {
        ['username' => $username, 'password' => $password, 'invite_code' => $inviteCode] = Flight::request()->data;

        if (!$username || mb_strlen($username) < 5 || mb_strlen($username) > 15) {
            Flight::jsonHalt(['error' => '用户名长度必须在 5 到 15 个字符之间'], 406);
        }
        if (!$password || strlen($password) < 8 || strlen($password) > 50) {
            Flight::jsonHalt(['error' => '密码长度必须在 8 到 50 个字符之间'], 406);
        }
        if (!$inviteCode) {
            Flight::jsonHalt(['error' => '未提供邀请码'], 406);
        }

        $invite = Flight::db()->fetchRow('SELECT * FROM invite_codes WHERE code = :code AND is_used = FALSE', ['code' => $inviteCode]);
        if (!$invite->count()) {
            Flight::jsonHalt(['error' => '邀请码无效或已被使用'], 406);
        }
        $existingUser = Flight::db()->fetchRow('SELECT * FROM users WHERE username = :username', ['username' => $username]);
        if ($existingUser->count()) {
            Flight::jsonHalt(['error' => '用户名已被注册'], 406);
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        Flight::db()->runQuery('INSERT INTO users (username, password) VALUES (:username, :password)', [
            'username' => $username,
            'password' => $hashedPassword,
        ]);
        Flight::db()->runQuery('UPDATE invite_codes SET is_used = TRUE WHERE code = :code', ['code' => $inviteCode]);
        $userId = Flight::db()->lastInsertId();
        $token = self::generateToken($userId, true);

        Flight::json(['message' => '注册成功', 'username' => $username, 'token' => $token], 200);
    }

    public static function checkToken($token) {
        $tokenInfo = Flight::db()->fetchRow('SELECT user_id, expired_at FROM auth_tokens WHERE token = :token', ['token' => $token]);
        if (!$tokenInfo->count()) {
            return self::TOKEN_NOT_EXIST;
        } else if (strtotime($tokenInfo['expired_at']) < time()) {
            return self::TOKEN_EXPIRED;
        } else {
            return $tokenInfo['user_id'];
        }
    }

    public static function generateInviteCode() {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
        $code = '';
        for ($i = 0; $i < 25; $i++) {
            if ($i > 0 && $i % 5 === 0) {
                $code .= '-';
            }
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        Flight::db()->runQuery('INSERT INTO invite_codes (code) VALUES (:code)', ['code' => $code]);
        return $code;
    }
    private static function generateToken($userId, $insert = false) {
        $token = bin2hex(random_bytes(16));
        if ($insert) {
            $query = 'INSERT INTO auth_tokens (user_id, token, expired_at) VALUES (:user_id, :token, :expired_at)';
        } else {
            $query = 'UPDATE auth_tokens SET token = :token, expired_at = :expired_at WHERE user_id = :user_id';
        }
        Flight::db()->runQuery($query, [
            'user_id' => $userId,
            'token' => $token,
            'expired_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);
        return $token;
    }
}
