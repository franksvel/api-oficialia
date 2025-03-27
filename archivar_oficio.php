<?php
header('Content-Type: application/json'); 
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Content-Disposition, Content-Length, Content-Type");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica si se envió una solicitud de tipo POST con archivos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Verifica que se haya enviado un archivo
    if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se ha enviado ningún archivo o hubo un error en la carga.'
        ]);
        exit;
    }

    // Verifica los parámetros JSON adicionales en el cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['numero']) || !isset($data['fechaRecepcion']) || !isset($data['remitente']) || !isset($data['asunto'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Faltan datos requeridos (numero, fechaRecepcion, remitente, asunto)'
        ]);
        exit;
    }

    $numero = $data['numero'];
    $fechaRecepcion = $data['fechaRecepcion'];
    $remitente = $data['remitente'];
    $asunto = $data['asunto'];

    // Verifica que los datos no estén vacíos
    if (empty($numero) || empty($fechaRecepcion) || empty($remitente) || empty($asunto)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Todos los campos son requeridos y no deben estar vacíos.'
        ]);
        exit;
    }

    // Conexión a la base de datos
    require_once 'Connection.php';
    $connection = new Connection();
    $pdo = $connection->connect();

    // Verifica que el directorio de subida exista, si no lo crea
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);  // Crea la carpeta si no existe
    }

    // Validación de tipo de archivo
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($_FILES['file']['type'], $allowedTypes)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tipo de archivo no permitido. Solo se permiten archivos PDF y de imagen.'
        ]);
        exit;
    }

    // Validación del tamaño máximo del archivo (5 MB)
    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['file']['size'] > $maxSize) {
        echo json_encode([
            'status' => 'error',
            'message' => 'El archivo excede el tamaño máximo permitido (5 MB).'
        ]);
        exit;
    }

    // Generación de un nombre único para el archivo
    $fileName = uniqid('file_', true) . '.' . pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;

    // Mueve el archivo a la carpeta de destino
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        // Guarda la ruta del archivo en la base de datos
        $sql = "INSERT INTO oficios (numero, fechaRecepcion, remitente, asunto, archivo) 
                VALUES (:numero, :fechaRecepcion, :remitente, :asunto, :archivo)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':numero', $numero, PDO::PARAM_STR);
        $stmt->bindParam(':fechaRecepcion', $fechaRecepcion, PDO::PARAM_STR);
        $stmt->bindParam(':remitente', $remitente, PDO::PARAM_STR);
        $stmt->bindParam(':asunto', $asunto, PDO::PARAM_STR);
        $stmt->bindParam(':archivo', $filePath, PDO::PARAM_STR);

        try {
            $stmt->execute();
            echo json_encode([
                'status' => 'success',
                'message' => 'Oficio y archivo guardados correctamente',
                'data' => [
                    'numero' => $numero,
                    'archivo' => $filePath
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Hubo un problema al guardar el archivo.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido.'
    ]);
}
?>
