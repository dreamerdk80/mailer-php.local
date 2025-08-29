<?php

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require_once "db_connect.php";

$response = [];

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM settings");
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$mail = new PHPMailer(true);

/* $mail->SMTPKeepAlive = true; // Включаем SMTPKeepAlive */

$mail->CharSet = "UTF-8";
$mail->Encoding = 'base64';
//Enable SMTP debugging.
$mail->SMTPDebug = 0;
//Set PHPMailer to use SMTP.
$mail->isSMTP();
//Set SMTP host name
$mail->Host = $data["serverSmtp"];
//Set this to true if SMTP host requires authentication to send email
$mail->SMTPAuth = true;
//Provide username and password
$mail->Username = $data["email"];
$mail->Password = $data["emailPass"];
//If SMTP requires TLS encryption then set it
/* $mail->SMTPSecure = "tls"; */
//Set TCP port to connect to
$mail->Port = $data["serverPort"];

$mail->From = $data["email"];
$mail->FromName = $data["name"];

$mail->isHTML(true);

/* $mail->AltBody = "This is the plain text version of the email content"; */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    $dataPost = json_decode($input, true);

    if (isset($dataPost)) {
        $theme = $dataPost["theme"];
        $message = $dataPost["message"];

        $mail->Subject = $theme;

        $jsonFile = "../uploads/mailer.json";
        $jsonString = file_get_contents($jsonFile);
        $dataForSend = json_decode($jsonString, true);

        $search = "[x]";
        $result = [];

        for ($i = 0; $i < count($dataForSend); $i++) {
            $messageToSend = $message;
            $count = count($dataForSend[$i]);

            for ($j = 1; $j < $count; $j++) {
                $messageToSend = preg_replace('/' . preg_quote($search, '/') . '/i', $dataForSend[$i][$j], $messageToSend, 1);
            }

            $mail->Body = $messageToSend;
            $emailToSend = $dataForSend[$i][$count];
            $mail->addAddress($emailToSend);

            try {
                $mail->send();

                $result[] = [
                    'success' => true,
                    'message' => "Письмо успешно отправлено: " . $emailToSend
                ];

                $mail->clearAddresses();

                /* echo "Message has been sent successfully"; */

                /* $file_path = "../uploads/log.txt";

                file_put_contents($file_path, "Успешно: " . $emailToSend . PHP_EOL, FILE_APPEND); */

            } catch (Exception $e) {
                /* echo $emailToSend . "<br>" . $mail->ErrorInfo; */
                /* $file_err_path = "../json/err.txt";

                file_put_contents($file_path, "Не отправлено: " . $emailToSend . PHP_EOL, FILE_APPEND); */

                http_response_code(400);
                $result[] = [
                    'success' => false,
                    'message' => $emailToSend . ": " . $e->getMessage()
                ];

                $mail->clearAddresses();
            }
        }
    }
} else {
    http_response_code(405);
    $result[] = [
        'success' => false,
        'message' => 'Метод не разрешен'
    ];
}

echo json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

?>