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
        $sql = "SELECT id, email, password, role_id FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica si el usuario existe
        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Regenerar la sesión para mayor seguridad
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role_id'] = $user['role_id'];

                // Solo permitir que el usuario autenticado o el administrador vean los datos
                if ($user['role_id'] == 1) {
                    // Si es administrador, obtiene toda la información de los usuarios
                    $sql = "SELECT id, email, role_id FROM usuarios";
                    $stmt = $pdo->prepare($sql);
                } else {
                    // Si es un usuario normal, solo obtiene su propia información
                    $sql = "SELECT id, email, role_id FROM usuarios WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                }

                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Respuesta de login exitoso con la información que puede ver
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login exitoso',
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ],
                    'users' => $users
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
