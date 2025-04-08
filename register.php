<?php
header('Content-Type: application/json'); 
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email y contraseña son requeridos'
    ]);
    exit;
}

$email = $data['email'];
$password = $data['password'];

if (empty($email) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email y contraseña no pueden estar vacíos.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El formato del correo no es válido.'
    ]);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

require_once 'Connection.php';
$connection = new Connection();
$pdo = $connection->connect();

try {
    $checkSql = "SELECT verificado FROM usuarios WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
    $checkStmt->execute();
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['verificado'] == 0) {
            echo json_encode([
                'status' => 'warning',
                'message' => 'Este correo ya está registrado pero no ha sido verificado. Revisa tu correo.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'El email ya está registrado.'
            ]);
        }
        exit;
    }

    $token = bin2hex(random_bytes(32));

    $sql = "INSERT INTO usuarios (email, password, verificado, token_verificacion) 
            VALUES (:email, :password, 0, :token)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();

    $verification_link = "http://localhost/api/verificar.php?token=$token";
    $subject = "Verifica tu cuenta";
    $message = "Haz clic en el siguiente enlace para verificar tu cuenta:<br><a href='$verification_link'>$verification_link</a>";
    $headers = "Content-type:text/html;charset=UTF-8" . "\r\n";

    $enviado = mail($email, $subject, $message, $headers);

    if (!$enviado) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo enviar el correo de verificación.'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Usuario registrado. Revisa tu correo para verificar tu cuenta.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>
