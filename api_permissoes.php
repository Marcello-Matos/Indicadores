<?php
// api_permissoes.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

// Conexão com o SRV-03
$host = 'srv-03';
$username = 'root'; 
$password = '';
$database = 'dbkalpa';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão']);
    exit();
}

require_once 'gerenciar_permissoes.php';

$usuario_id = $_SESSION['usuario_id'];
$permissoes = new SistemaPermissoes($conn, $usuario_id);

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'get_user_permissions':
        $user_id = $_GET['user_id'] ?? 0;
        
        $modulos = $permissoes->getTodosModulos();
        $user_perms = $permissoes->getPermissoesUsuario($user_id);
        
        echo json_encode([
            'success' => true,
            'modulos' => $modulos,
            'permissoes' => $user_perms
        ]);
        break;
        
    case 'save_permissions':
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $input['user_id'] ?? 0;
        $permissoes_data = $input['permissoes'] ?? [];
        
        $success = $permissoes->atualizarPermissoes($user_id, $permissoes_data);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Permissões salvas com sucesso' : 'Erro ao salvar permissões'
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
?>