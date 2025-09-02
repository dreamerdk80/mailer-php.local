<?php
// Разрешаем запросы с конкретного origin
/* header('Access-Control-Allow-Origin: http://localhost:3000'); */
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Обработка preflight OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getAuthorizationHeader() {
    // Проверяем заголовок в разных местах
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
        if (isset($headers['authorization'])) {
            return $headers['authorization'];
        }
    }

    return '';
}

function verifyJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        return [
            'success' => true,
            'data' => (array) $decoded->data
        ];
    } catch (Firebase\JWT\ExpiredException $e) {
        return ['success' => false, 'error' => 'Token expired'];
    } catch (Firebase\JWT\SignatureInvalidException $e) {
        return ['success' => false, 'error' => 'Invalid signature'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Token verification failed'];
    }
}

// Для проверки токена через GET запрос
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $authHeader = getAuthorizationHeader();

    // Отладочная информация в лог
    error_log("Authorization header: " . $authHeader);

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = trim($matches[1]);

        if (empty($token)) {
            http_response_code(401);
            echo json_encode([
                'authenticated' => false,
                'error' => 'Empty token'
            ]);
            exit;
        }

        $result = verifyJWT($token);

        if ($result['success']) {
            echo json_encode([
                'authenticated' => true,
                'user' => $result['data']
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'authenticated' => false,
                'error' => $result['error']
            ]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            'authenticated' => false,
            'error' => 'Token required',
            'debug' => [
                'auth_header_received' => $authHeader,
                'server_keys' => array_keys($_SERVER)
            ]
        ]);
    }
    exit;
}

// Если метод не GET
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);