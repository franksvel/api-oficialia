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

// Simulamos una lista de roles en el backend
$roles = [
    ['nombre' => 'SuperAdministrador'],
    ['nombre' => 'Administrador'],
    ['nombre' => 'Usuario']
];

// Manejo de acciones (agregar, editar, eliminar)
$action = $_POST['action'] ?? null;

switch ($action) {
    case 'add':
        $roleName = $_POST['role_name'] ?? '';
        if ($roleName) {
            $roles[] = ['nombre' => $roleName];
            echo json_encode(['status' => 'success', 'message' => 'Rol agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del rol es obligatorio.']);
        }
        break;
    
    case 'edit':
        $index = $_POST['index'] ?? -1;
        $roleName = $_POST['role_name'] ?? '';
        if ($index >= 0 && $roleName) {
            $roles[$index]['nombre'] = $roleName;
            echo json_encode(['status' => 'success', 'message' => 'Rol editado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Índice de rol no válido o nombre de rol vacío.']);
        }
        break;
    
    case 'delete':
        $index = $_POST['index'] ?? -1;
        if ($index >= 0 && isset($roles[$index])) {
            array_splice($roles, $index, 1);
            echo json_encode(['status' => 'success', 'message' => 'Rol eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Índice de rol no válido.']);
        }
        break;
    
    case 'get':
    default:
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
