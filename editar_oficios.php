<?php
header('Content-Type: application/json'); 
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtén el cuerpo de la solicitud y decodifica el JSON
$data = json_decode(file_get_contents("php://input"), true);

// Verifica que los datos estén presentes antes de usarlos
if (!isset($data['id']) || !isset($data['numero']) || !isset($data['fechaRecepcion']) || !isset($data['remitente']) || !isset($data['asunto'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Faltan datos requeridos (id, numero, fechaRecepcion, remitente, asunto)'
    ]);
    exit;
}

$id = $data['id'];
$numero = $data['numero'];
$fechaRecepcion = $data['fechaRecepcion'];
$remitente = $data['remitente'];
$asunto = $data['asunto'];

// Verifica que los datos no estén vacíos
if (empty($id) || empty($numero) || empty($fechaRecepcion) || empty($remitente) || empty($asunto)) {
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

// Primero, obtenemos el oficio existente con el ID proporcionado para comparar los datos
$sqlSelect = "SELECT * FROM oficios WHERE id = :id";
$stmtSelect = $pdo->prepare($sqlSelect);
$stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);

try {
    $stmtSelect->execute();
    $existingOficio = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$existingOficio) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se encontró el oficio con el id proporcionado.'
        ]);
        exit;
    }

    // Compara los datos existentes con los nuevos datos
    if ($existingOficio['numero'] === $numero && $existingOficio['fechaRecepcion'] === $fechaRecepcion && 
        $existingOficio['remitente'] === $remitente && $existingOficio['asunto'] === $asunto) {
        echo json_encode([
            'status' => 'info',
            'message' => 'No hubo cambios en los datos del oficio.'
        ]);
        exit;  // No hacemos nada si los datos no han cambiado
    }

    // Consulta para actualizar el oficio existente
    $sqlUpdate = "UPDATE oficios SET numero = :numero, fechaRecepcion = :fechaRecepcion, remitente = :remitente, asunto = :asunto WHERE id = :id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':numero', $numero, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':fechaRecepcion', $fechaRecepcion, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':remitente', $remitente, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':asunto', $asunto, PDO::PARAM_STR);

    $stmtUpdate->execute();
    
    if ($stmtUpdate->rowCount() > 0) {
        // Respuesta de éxito si la actualización fue exitosa
        echo json_encode([
            'status' => 'success',
            'message' => 'Oficio actualizado correctamente'
        ]);
    } else {
        // Si no se ha actualizado ningún registro, pero no hubo cambios
        echo json_encode([
            'status' => 'info',
            'message' => 'No hubo cambios en el oficio.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>
