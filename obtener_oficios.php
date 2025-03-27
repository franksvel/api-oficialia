<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
require_once 'Connection.php';
$connection = new Connection();
$pdo = $connection->connect();

// Consulta para obtener todos los oficios almacenados
$sql = "SELECT id, numero, fechaRecepcion, remitente, asunto FROM oficios";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute();
    $oficios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si hay resultados, se devuelven
    if ($oficios) {
        echo json_encode([
            'status' => 'success',
            'data' => $oficios
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se encontraron oficios almacenados'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>
