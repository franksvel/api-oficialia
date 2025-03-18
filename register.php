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
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email y contraseña son requeridos'
    ]);
    exit;
}

$email = $data['email'];
$password = $data['password'];

// Verificar que el email y la contraseña no estén vacíos
if (empty($email) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email y contraseña no pueden estar vacíos.'
    ]);
    exit;
}

// Hashea la contraseña antes de guardarla
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Conexión a la base de datos
require_once 'Connection.php';
$connection = new Connection();
$pdo = $connection->connect();

// Consulta para insertar un nuevo usuario
$sql = "INSERT INTO usuarios (email, password) VALUES (:email, :password)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);

try {
    $stmt->execute();
    // Respuesta de éxito
    echo json_encode([
        'status' => 'success',
        'message' => 'Usuario registrado exitosamente'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>
