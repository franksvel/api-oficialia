<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Content-Disposition, Content-Length");

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$uploadDirectory = 'uploads/';

// Verificar si el directorio de carga existe, si no, crearlo
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Verificar que se ha recibido un archivo y un ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['id'])) {
    $file = $_FILES['file'];
    $id = $_POST['id'];

    // Depuración: Verificar qué datos se están recibiendo
    if (!isset($id) || empty($id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'El ID es obligatorio.']);
        exit;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Error al cargar el archivo.', 'error_code' => $file['error']]);
        exit;
    }

    // Validar tipo de archivo
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tipo de archivo no permitido. Solo se permiten archivos PDF, JPG y PNG.']);
        exit;
    }

    // Validar tamaño de archivo
    if ($file['size'] > MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'El archivo excede el tamaño máximo permitido (5MB).']);
        exit;
    }

    // Generar un nombre único para el archivo
    $fileName = uniqid('file_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

    // Intentar mover el archivo al servidor
    if (move_uploaded_file($file['tmp_name'], $uploadDirectory . $fileName)) {
        $host = 'localhost';
        $dbname = 'oficialia';
        $username = 'root';
        $password = '';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificar si el ID existe
            $sql = "SELECT id FROM oficios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Si el ID existe, actualizar el registro
                $sql = "UPDATE oficios SET archivo = :archivo WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':archivo', $fileName, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Archivo actualizado correctamente.', 'fileName' => $fileName]);
                } else {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el registro en la base de datos.']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID no encontrado en la base de datos.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Hubo un error al mover el archivo al servidor.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se ha enviado un archivo o falta el ID.']);
}
?>
