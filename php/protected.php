<?php

header('Access-Control-Allow-Credentials: true');

require_once 'config.php';
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token required']);
    exit;
}

$token = $matches[1];

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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
}