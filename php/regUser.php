<?php

header('Content-Type: application/json');

require_once "db_connect.php";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (isset($data)) {

            if (!filter_var($data["regEmail"], FILTER_VALIDATE_EMAIL)) {
                echo json_encode("Введите корректный Email");
                exit;
            }

            $firstName = $data["firstName"];
            $lastName = $data["lastName"];
            $regEmail = $data["regEmail"];
            $regPassHash = password_hash($data["regPass"], PASSWORD_DEFAULT);
            $role = $data["role"];

            $availabilityQuery = 'SHOW TABLES LIKE "users";';
            $availability = $pdo->query($availabilityQuery);

            if ($availability->rowCount() != 0) {
                $query = 'INSERT INTO users (role, email, password_hash, first_name, last_name)
                            VALUES (:role, :email, :password_hash, :first_name, :last_name);';

            } else {
                $query = 'CREATE TABLE users (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            role VARCHAR(100),
                            email VARCHAR(255) NOT NULL UNIQUE,
                            password_hash VARCHAR(255) NOT NULL,
                            first_name VARCHAR(100),
                            last_name VARCHAR(100),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);
                            INSERT INTO users (role, email, password_hash, first_name, last_name)
                            VALUES (:role, :email, :password_hash, :first_name, :last_name);';
            }

            $stmt = $pdo->prepare($query);

            $stmt->bindValue(":role", $role);
            $stmt->bindValue(":email", $regEmail);
            $stmt->bindValue(":password_hash", $regPassHash);
            $stmt->bindValue(":first_name", $firstName);
            $stmt->bindValue(":last_name", $lastName);

            $stmt->execute();

            echo json_encode("Пользователь создан");

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
