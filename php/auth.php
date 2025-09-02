<?php

header('Access-Control-Allow-Credentials: true');

require_once "db_connect.php";
require_once "config.php";
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (isset($data["email"]) && isset($data["password"])) {
            if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
                echo json_encode("Введите корректный Email", JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $authEmail = $data["email"];
            $authPass = $data["password"];

            $query = 'SELECT * FROM users WHERE email = :email;';
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":email", $authEmail);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData || !password_verify($authPass, $userData['password_hash'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Неверные учетные данные'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Генерация JWT токена
            $payload = [
                'iss' => 'http://localhost:3000',
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24), // 24 hours
                'data' => [
                    'id' => $userData['id'],
                    'email' => $userData['email'],
                    'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                    'role' => $userData['role']
                ]
            ];

            $jwt = JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);

            echo json_encode([
                'success' => true,
                'token' => $jwt,
                'user' => [
                    'id' => $userData['id'],
                    'email' => $userData['email'],
                    'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                    'role' => $userData['role']
                ]
            ]);

        } else {
            echo json_encode("Введите данные");
            exit;
        }

    } else {
        echo json_encode("Неверный метод");
        exit;
    }
} catch (PDOException $e) {
    echo json_encode("Ошибка базы данных: " . $e->getMessage());
    exit;
}

?>