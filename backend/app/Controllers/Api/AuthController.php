<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Config\Database;
use App\Helpers\JsonResponse;
use App\Helpers\Request;
use mysqli;

final class AuthController
{
    public function login(Request $request): never
    {
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        if ($username === '' || $password === '') {
            JsonResponse::error('VALIDATION_ERROR', 'Username and password are required.', 422, [
                'fields' => array_filter([
                    'username' => $username === '' ? 'required' : null,
                    'password' => $password === '' ? 'required' : null,
                ]),
            ]);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT user_id, username, password_hash, full_name, role, avatar FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $stmt->close();
            JsonResponse::error('INVALID_CREDENTIALS', 'Invalid username or password.', 401);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['role'] = (string) $user['role'];
        $_SESSION['name'] = (string) $user['full_name'];
        $stmt->close();

        JsonResponse::success([
            'user' => [
                'id' => (int) $user['user_id'],
                'username' => $user['username'],
                'name' => $user['full_name'],
                'role' => $user['role'],
                'avatar' => $user['avatar'] ?? null,
            ],
        ]);
    }

    public function logout(): never
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        JsonResponse::success(['logged_out' => true]);
    }

    public function session(): never
    {
        if (empty($_SESSION['user_id'])) {
            JsonResponse::success(['authenticated' => false, 'user' => null]);
        }

        $conn = Database::connection();
        $stmt = $conn->prepare('SELECT user_id, username, full_name, role, avatar FROM users WHERE user_id = ? LIMIT 1');
        $userId = (int) $_SESSION['user_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            JsonResponse::success(['authenticated' => false, 'user' => null]);
        }

        JsonResponse::success([
            'authenticated' => true,
            'user' => [
                'id' => (int) $row['user_id'],
                'username' => $row['username'],
                'name' => $row['full_name'],
                'role' => $row['role'],
                'avatar' => $row['avatar'] ?? null,
            ],
        ]);
    }
}