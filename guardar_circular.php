<?php
header('Content-Type: application/json'); 
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtén el cuerpo de la solicitud y decodifica el JSON
$data = json_decode(file_get_contents("php://input"), true);

// Verifica que los datos estén presentes antes de usarlos
if (!isset($data['titulo']) || !isset($data['fecha']) || !isset($data['descripcion']) || !isset($data['destinatario'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Faltan datos requeridos (titulo, fecha, descripcion, destinatario)'
    ]);
    exit;
}

$titulo = $data['titulo'];
$fecha = $data['fecha'];
$descripcion = $data['descripcion'];
$destinatario = $data['destinatario'];

// Verifica que los datos no estén vacíos
if (empty($titulo) || empty($fecha) || empty($descripcion) || empty($destinatario)) {
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

// Consulta para insertar un nuevo oficio
$sql = "INSERT INTO oficios (titulo, fecha, descripcion, destinatario) VALUES (:titulo, :fecha, :descripcion, :destinatario)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
$stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
$stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
$stmt->bindParam(':destinatario', $destinatario, PDO::PARAM_STR);

try {
    $stmt->execute();
    // Respuesta de éxito
    echo json_encode([
        'status' => 'success',
        'message' => 'Oficio guardado correctamente'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>
