<?php
// Добавьте CORS заголовки в самое начало
/* header('Access-Control-Allow-Origin: http://localhost:3000'); */
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
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

// Получаем заголовок через функцию
$authHeader = getAuthorizationHeader();

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Token required',
        'debug' => [
            'auth_header' => $authHeader,
            'server_keys' => array_keys($_SERVER)
        ]
    ]);
    exit;
}

$token = trim($matches[1]);

// Проверка на пустой токен
if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Empty token']);
    exit;
}

try {
    $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
    $userData = (array) $decoded->data;

    // Защищенный endpoint
    echo json_encode([
        'success' => true,
        'message' => 'Protected data',
        'user' => $userData,
        'data' => ['secret' => 'This is protected information']
    ]);

} catch (Firebase\JWT\ExpiredException $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Token expired']);
} catch (Firebase\JWT\SignatureInvalidException $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
}