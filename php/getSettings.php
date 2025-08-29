<?php

require_once "db_connect.php";

header("Content-Type: application/json");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname",$user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM settings");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Ошибка базы данных: " . $e->getMessage()
    ]);
}

?>