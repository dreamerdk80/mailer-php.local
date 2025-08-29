<?php

header('Content-Type: multipart/form-data');

// Настройки CORS (если React на другом домене)
/* header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type'); */

// Разрешаем загрузку больших файлов
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', 300);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Проверяем, был ли отправлен файл
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Файл не был загружен или произошла ошибка');
        }

        $file = $_FILES['file'];

        // Валидация
        $allowedTypes = ['application/json'];
        $maxFileSize = 50 * 1024 * 1024; // 50MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Недопустимый тип файла');
        }

        if ($file['size'] > $maxFileSize) {
            throw new Exception('Файл слишком большой');
        }

        // Создаем папку для загрузок, если её нет
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Генерируем уникальное имя файла
        /* $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename; */

        $filename = "mailer.json";
        $destination = $uploadDir . $filename;

        // Перемещаем файл
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Получаем дополнительные данные
            $additionalData = $_POST['additionalData'] ?? '';

            echo json_encode([
                'success' => true,
                'message' => 'Файл успешно загружен',
                'filename' => $filename,
                'originalName' => $file['name'],
                'size' => $file['size'],
                'additionalData' => $additionalData
            ]);
        } else {
            throw new Exception('Ошибка при сохранении файла');
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешен'
    ]);
}
?>