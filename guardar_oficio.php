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

// Consulta para insertar un nuevo oficio
$sql = "INSERT INTO oficios (numero, fechaRecepcion, remitente, asunto) VALUES (:numero, :fechaRecepcion, :remitente, :asunto)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':numero', $numero, PDO::PARAM_STR);
$stmt->bindParam(':fechaRecepcion', $fechaRecepcion, PDO::PARAM_STR);
$stmt->bindParam(':remitente', $remitente, PDO::PARAM_STR);
$stmt->bindParam(':asunto', $asunto, PDO::PARAM_STR);

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
