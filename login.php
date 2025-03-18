<?php
header('Content-Type: application/json'); 
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); 
}

session_start();
require_once 'Connection.php';

$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['email']) && isset($data['password'])) {
    $email = $data['email'];
    $password = $data['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([ 'status' => 'error', 'message' => 'Email inválido' ]);
        exit;
    }

    try {
        // Conexión a la base de datos
        $connection = new Connection();
        $pdo = $connection->connect();

        // Consulta para obtener el usuario por email
        $sql = "SELECT * FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica si el usuario existe
        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Iniciar la sesión del usuario y guardar los datos en la sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role_id'] = $user['role_id'];

                // Respuesta de login exitoso
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login exitoso',
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ]
                ]);
            } else {
                echo json_encode([ 'status' => 'error', 'message' => 'Contraseña incorrecta' ]);
            }
        } else {
            echo json_encode([ 'status' => 'error', 'message' => 'Usuario no encontrado' ]);
        }
    } catch (PDOException $e) {
        // Si ocurre un error con la base de datos
        echo json_encode([ 'status' => 'error', 'message' => 'Error de base de datos. Inténtalo más tarde.' ]);
    }
} else {
    echo json_encode([ 'status' => 'error', 'message' => 'Datos incompletos. El email y la contraseña son requeridos.' ]);
}
?>
