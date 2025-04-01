<?php
// Habilitar CORS
header("Access-Control-Allow-Origin: http://localhost:4200"); // Puedes cambiar el * por un dominio específico si es necesario.
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Content-Disposition, Content-Length"); // Corrige la redundancia

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar si la solicitud es una opción (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;  // Salir si es una solicitud preflight
}

// Ruta para guardar los archivos
$uploadDirectory = 'uploads/';

// Verificar si el directorio de carga existe, si no, crearlo
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

// Validar el tamaño máximo del archivo (5MB)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Verificar si se ha enviado un archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // Verificar si el archivo se subió sin errores
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Hubo un error al cargar el archivo.']);
        exit;
    }

    // Validar tipo de archivo (PDF, JPG, PNG)
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

    // Mover el archivo a la carpeta de carga
    if (move_uploaded_file($file['tmp_name'], $uploadDirectory . $fileName)) {
        // Conectar a la base de datos (reemplazar con tus credenciales)
        $host = 'localhost';
        $dbname = 'oficialia';
        $username = 'root';
        $password = '';

        // Obtener los valores adicionales del formulario (estos deben ser enviados en el cuerpo de la solicitud)
        $numero = $_POST['numero'] ?? '';  // Asegúrate de enviar el campo 'numero'
        $fechaRecepcion = $_POST['fechaRecepcion'] ?? date('Y-m-d');  // Asignar la fecha de recepción o la fecha actual si no se envía
        $remitente = $_POST['remitente'] ?? '';  // Asegúrate de enviar el campo 'remitente'
        $asunto = $_POST['asunto'] ?? '';  // Asegúrate de enviar el campo 'asunto'

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Preparar la consulta para insertar el archivo y los datos adicionales
            $sql = "INSERT INTO oficios (archivo, numero, fechaRecepcion, remitente, asunto) 
                    VALUES (:archivo, :numero, :fechaRecepcion, :remitente, :asunto)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':archivo', $fileName);
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':fechaRecepcion', $fechaRecepcion);
            $stmt->bindParam(':remitente', $remitente);
            $stmt->bindParam(':asunto', $asunto);

            // Ejecutar la consulta
            if ($stmt->execute()) {
                // Responder con éxito
                echo json_encode(['status' => 'success', 'message' => 'Archivo cargado correctamente y registrado en la base de datos', 'fileName' => $fileName]);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Error al guardar la ruta del archivo en la base de datos.']);
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
    echo json_encode(['status' => 'error', 'message' => 'No se ha enviado ningún archivo.']);
}
?>
