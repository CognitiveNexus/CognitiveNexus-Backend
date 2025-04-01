<?php

class AuthMiddleware {
    public static function before() {
        $token = Flight::request()->header('Authorization');
        if (!$token) {
            Flight::jsonHalt(['error' => '用户未登录'], 401);
        }
        $userId = AuthController::checkToken($token);
        if ($userId < 0) {
            if ($userId == AuthController::TOKEN_EXPIRED) {
                Flight::jsonHalt(['error' => '登录已过期，请重新登录'], 401);
            } else if ($userId == AuthController::TOKEN_NOT_EXIST) {
                Flight::jsonHalt(['error' => '登录已失效，请重新登录'], 401);
            } else {
                Flight::jsonHalt(['error' => '由于未知原因登录失败，请重新登录'], 401);
            }
        }
    }
}