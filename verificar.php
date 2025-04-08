<?php
require_once 'PHPMailer/PHPMailerAutoload.php';  // Incluye PHPMailer

$connection = new Connection();
$pdo = $connection->connect();

// Verifica que se haya recibido el token
if (!isset($_GET['token'])) {
    die("Token no proporcionado.");
}

$token = $_GET['token'];

// Buscar usuario con ese token que aún no esté verificado
$sql = "SELECT id, correo FROM usuarios WHERE token_verificacion = :token AND verificado = 0";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':token', $token, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Marcar como verificado y borrar el token
    $updateSql = "UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
    $updateStmt->execute();

    // Enviar correo de verificación a Gmail
    $mail = new PHPMailer;

    try {
        // Configuración de servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tu_correo@gmail.com';  // Tu correo de Gmail
        $mail->Password = 'tu_contraseña';       // Tu contraseña de Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración del mensaje
        $mail->setFrom('tu_correo@gmail.com', 'Nombre del Sistema');
        $mail->addAddress($user['correo']);  // Correo del usuario

        // Asunto y cuerpo del mensaje
        $mail->Subject = 'Verificación de cuenta';
        $mail->Body    = "¡Hola!\n\nTu cuenta ha sido verificada correctamente. Puedes iniciar sesión con tus credenciales.\n\nSaludos.";

        // Enviar correo
        $mail->send();
        echo "✅ Tu cuenta ha sido verificada correctamente y se ha enviado un correo de confirmación.";
    } catch (Exception $e) {
        echo "❌ Hubo un error al enviar el correo: {$mail->ErrorInfo}";
    }
} else {
    echo "❌ Token inválido o cuenta ya verificada.";
}
?>
