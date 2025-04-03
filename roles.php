<?php
header('Content-Type: application/json'); 
header("Access-Control-Allow-Origin: http://localhost:4200");  // Cambia la URL si es necesario
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Content-Disposition, Content-Length, Content-Type");
header("Access-Control-Max-Age: 86400");

// Si la petición es de tipo OPTIONS, solo se debe responder con un OK para CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

session_start();

// Configuración de conexión a la base de datos
$host = 'localhost';
$dbname = 'oficialia';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Conexión a la base de datos fallida: ' . $e->getMessage()]);
    exit;
}

// Manejo de acciones (agregar, editar, eliminar)
$action = $_POST['action'] ?? null;

switch ($action) {
    case 'add':
        $roleName = $_POST['role_name'] ?? '';
        $rolePermissions = $_POST['permissions'] ?? [];
        if ($roleName) {
            // Agregar rol
            $stmt = $pdo->prepare("INSERT INTO roles (nombre) VALUES (?)");
            $stmt->execute([$roleName]);
            $roleId = $pdo->lastInsertId();

            // Asociar permisos al rol
            foreach ($rolePermissions as $permiso) {
                $stmt = $pdo->prepare("INSERT INTO roles_permisos (role_id, permiso_id) VALUES (?, ?)");
                $stmt->execute([$roleId, $permiso]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Rol agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del rol es obligatorio.']);
        }
        break;
    
    case 'edit':
        $index = $_POST['index'] ?? -1;
        $roleName = $_POST['role_name'] ?? '';
        $rolePermissions = $_POST['permissions'] ?? [];
        if ($index >= 0 && $roleName) {
            // Actualizar rol
            $stmt = $pdo->prepare("UPDATE roles SET nombre = ? WHERE id = ?");
            $stmt->execute([$roleName, $index]);

            // Eliminar permisos antiguos
            $stmt = $pdo->prepare("DELETE FROM roles_permisos WHERE role_id = ?");
            $stmt->execute([$index]);

            // Asociar nuevos permisos al rol
            foreach ($rolePermissions as $permiso) {
                $stmt = $pdo->prepare("INSERT INTO roles_permisos (role_id, permiso_id) VALUES (?, ?)");
                $stmt->execute([$index, $permiso]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Rol editado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Índice de rol no válido o nombre de rol vacío.']);
        }
        break;
    
    case 'delete':
        $index = $_POST['index'] ?? -1;
        if ($index >= 0) {
            // Eliminar permisos asociados al rol
            $stmt = $pdo->prepare("DELETE FROM roles_permisos WHERE role_id = ?");
            $stmt->execute([$index]);

            // Eliminar rol
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$index]);

            echo json_encode(['status' => 'success', 'message' => 'Rol eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Índice de rol no válido.']);
        }
        break;
    
    case 'get':
    default:
        // Obtener todos los roles con permisos
        $stmt = $pdo->query("SELECT r.id, r.nombre, GROUP_CONCAT(p.nombre) AS permisos FROM roles r 
                             LEFT JOIN roles_permisos rp ON r.id = rp.role_id
                             LEFT JOIN permisos p ON rp.permiso_id = p.id
                             GROUP BY r.id");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'roles' => $roles]);
        break;
}

// Cerrar la sesión si se ha solicitado
if ($_POST['action'] == 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Sesión cerrada correctamente.']);
}
?>
