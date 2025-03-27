<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener ID desde el parámetro GET o desde el body si se usa POST
$id = $_GET['id'] ?? json_decode(file_get_contents("php://input"), true)['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
    exit;
}

require_once 'Connection.php';

try {
    $connection = new Connection();
    $pdo = $connection->connect();

    $sql = "DELETE FROM oficios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Oficio eliminado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró el oficio']);
    }
} catch (PDOException $e) {
    error_log("Error en la base de datos: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en el servidor']);
}
?>
