<?php
header('Content-Type: application/json'); 
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtén el cuerpo de la solicitud y decodifica el JSON
$data = json_decode(file_get_contents("php://input"), true);

// Verifica que el número del oficio esté presente
if (!isset($data['numero'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Falta el número del oficio.'
    ]);
    exit;
}

$numero = $data['numero'];

// Verifica que el número no esté vacío
if (empty($numero)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El número del oficio no puede estar vacío.'
    ]);
    exit;
}

// Conexión a la base de datos
require_once 'Connection.php';
$connection = new Connection();
$pdo = $connection->connect();

// Consulta para verificar si el oficio existe
$sql = "SELECT * FROM oficios WHERE numero = :numero";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':numero', $numero, PDO::PARAM_STR);

$stmt->execute();
$oficio = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no se encuentra el oficio
if (!$oficio) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se encontró un oficio con ese número.'
    ]);
    exit;
}

// Consulta para eliminar el oficio
$sql = "DELETE FROM oficios WHERE numero = :numero";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':numero', $numero, PDO::PARAM_STR);

try {
    $stmt->execute();
    // Respuesta de éxito
    echo json_encode([
        'status' => 'success',
        'message' => 'Oficio eliminado correctamente'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>
