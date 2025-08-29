<?php
require_once "db_connect.php";

header("Content-Type: application/json");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data)) {

    $response = [];
    $query = "";

    if (empty($data["serverSmtp"]) || empty($data["serverPort"]) || empty($data["name"]) || !filter_var($data["email"], FILTER_VALIDATE_EMAIL) || empty($data["emailPass"])) {
        http_response_code(400);
        echo json_encode(["error" => "Некорректные данные"]);
        exit;
    }

    $response["message"] = "Данные успешно обработаны.";

    try {
        /* $emailPassHash = password_hash($data["emailPass"], PASSWORD_DEFAULT); */

        $dbh = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $response["connect"] = "Успешное подключение к базе данных.";

        $availabilityQuery = 'SHOW TABLES LIKE "settings";';
        $availability = $dbh->query($availabilityQuery);

        if ($availability->rowCount() != 0) {
            $queryTruncate = 'TRUNCATE settings;';
            $stmtTruncate = $dbh->prepare($queryTruncate);
            $stmtTruncate->execute();

            $query = 'INSERT INTO settings (serverSmtp, serverPort, name, email, emailPass)
                        VALUES (:serverSmtp, :serverPort, :name, :email, :emailPass);';

        } else {
            $query = 'CREATE TABLE settings (
                        serverSmtp VARCHAR(255) NOT NULL,
                        serverPort INT NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        emailPass CHAR(255) NOT NULL);
                        INSERT INTO settings (serverSmtp, serverPort, name, email, emailPass)
                        VALUES (:serverSmtp, :serverPort, :name, :email, :emailPass);';

        }

        $stmt = $dbh->prepare($query);

        $stmt->bindValue(":serverSmtp", $data["serverSmtp"]);
        $stmt->bindValue(":serverPort", $data["serverPort"]);
        $stmt->bindValue(":name", $data["name"]);
        $stmt->bindValue(":email", $data["email"]);
        $stmt->bindValue(":emailPass", $data["emailPass"]);

        $stmt->execute();

        $response["result"] = "Настройки сохранены.";

        echo json_encode($response);

    } catch (PDOException $e) {
        echo json_encode("Ошибка базы данных: " . $e->getMessage());
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Недостаточно данных"]);
}

?>