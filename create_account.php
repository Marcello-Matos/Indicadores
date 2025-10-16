<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// =========================================================================
// ATENÇÃO: LINHAS DE EXIBIÇÃO DE ERROS - ESSENCIAIS PARA DEPURAR
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================================================================

// Configurações de conexão
$serverName = "192.168.0.8,1433";
$connectionOptions = [
    "Database" => "Indicadores",
    "Uid" => "sa", 
    "PWD" => "aplak2904&",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => false
];

// Criar conexão
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro de conexão com o Banco de Dados: ' . print_r(sqlsrv_errors(), true)
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar se todos os campos necessários estão presentes
        $required_fields = ['email', 'firstName', 'lastName', 'password', 'birthDate'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Dados incompletos. Campos faltando: ' . implode(', ', $missing_fields)
            ]);
            exit;
        }

        $email = $_POST['email'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $birthDate = json_decode($_POST['birthDate'], true);
        
        // Validar data de nascimento
        if (!$birthDate || !isset($birthDate['day']) || !isset($birthDate['month']) || !isset($birthDate['year'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Data de nascimento inválida'
            ]);
            exit;
        }
        
        // Processar imagem se existir
        $imageData = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['image']['tmp_name'];
            $imageData = file_get_contents($imageTmpPath);
        }

        // Verificar se o email já existe
        $sql_check = "SELECT CodUsuario FROM vW_Usuario_Formatada WHERE Email = ?";
        $params_check = array($email);
        $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);

        if ($stmt_check === false) {
            throw new Exception("Erro ao verificar email: " . print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_has_rows($stmt_check)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Este email já está cadastrado'
            ]);
            sqlsrv_free_stmt($stmt_check);
            exit;
        }
        sqlsrv_free_stmt($stmt_check);

        // Inserir no banco de dados
        $sql = "INSERT INTO vW_Usuario_Formatada 
                (Email, FirstName, LastName, Password, BirthDay, BirthMonth, BirthYear, Imagem) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = array(
            $email, 
            $firstName, 
            $lastName, 
            $password_hash, 
            $birthDate['day'], 
            $birthDate['month'], 
            $birthDate['year'],
            $imageData
        );

        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("Erro ao inserir usuário: " . print_r(sqlsrv_errors(), true));
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Conta criada com sucesso!'
        ]);

        sqlsrv_free_stmt($stmt);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Erro: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Método não permitido'
    ]);
}

// Fechar conexão
if ($conn) {
    sqlsrv_close($conn);
}
?>