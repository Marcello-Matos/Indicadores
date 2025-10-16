<?php
// Arquivo: send_verification.php - VERSÃO FINAL (INCLUI VERIFICAÇÃO DE INSERÇÃO)
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =====================================================
// CONFIGURAÇÃO DA CONEXÃO
// =====================================================
// Adicionado verificação e aumento de limite de tempo (timeout)
set_time_limit(60); 

if (!function_exists('sqlsrv_connect')) {
     http_response_code(500);
     echo json_encode(['success' => false, 'message' => "Erro de servidor: O driver SQLSRV não está instalado/habilitado no PHP."]);
     exit();
}
 
$serverName = "192.168.0.8,1433";
$connectionOptions = [
    "Database" => "Indicadores",
    "Uid" => "sa", 
    "PWD" => "aplak2904&",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => false
];

function getDbConnection() {
    global $serverName, $connectionOptions;
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if ($conn === false) {
        error_log('Falha na conexão: ' . print_r(sqlsrv_errors(), true));
        throw new Exception('Falha na conexão com o banco de dados. Verifique as credenciais.');
    }
    return $conn;
}

// =====================================================
// FUNÇÃO PARA OBTER OS LIMITES EXATOS DA TABELA
// =====================================================
function getTableLimits() {
    $conn = null;
    $limits = [];
    try {
        $conn = getDbConnection();
        
        $sql = "SELECT 
                COLUMN_NAME,
                CHARACTER_MAXIMUM_LENGTH as max_length
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'vW_Usuario'
            ORDER BY ORDINAL_POSITION";
        
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $normalizedName = strtolower($row['COLUMN_NAME']); 
                $limits[$normalizedName] = ($row['max_length'] == -1) ? 4000 : $row['max_length'];
            }
            sqlsrv_free_stmt($stmt);
        }
        
    } catch (Exception $e) {
        error_log("ERRO AO OBTER LIMITES: " . $e->getMessage());
        // Fallback para os limites conhecidos: Nome=50, Usuario=50, Senha=6
        $limits = ['nome' => 50, 'usuario' => 50, 'senha' => 6];
    } finally {
         if ($conn) sqlsrv_close($conn);
    }
    return $limits;
}

// =====================================================
// FUNÇÃO PRINCIPAL DE REGISTRO (CORRIGIDA E OTIMIZADA)
// =====================================================
function registerUserFixed($email, $fullName, $username, $password) {
    $conn = null;
    
    try {
        $limits = getTableLimits();
        
        $nomeLimit = isset($limits['nome']) ? $limits['nome'] : 50; 
        $usuarioLimit = isset($limits['usuario']) ? $limits['usuario'] : 50; 
        $senhaLimit = isset($limits['senha']) ? $limits['senha'] : 6; 
        
        $conn = getDbConnection();
        
        // APLICAR LIMITES
        $fullName = substr($fullName, 0, $nomeLimit);
        $username = substr(strtoupper($username), 0, $usuarioLimit); 
        
        // Contorno para Senha VARCHAR(6) (INSEGURO)
        $passwordHash = md5($password);
        if (strlen($passwordHash) > $senhaLimit) {
            $passwordHash = substr($passwordHash, 0, $senhaLimit);
        }

        // VERIFICAR SE USUÁRIO JÁ EXISTE
        $sql_check = "SELECT COUNT(*) as total FROM dbo.vW_Usuario WHERE Usuario = ?";
        $stmt_check = sqlsrv_query($conn, $sql_check, [$username]);
        
        if ($stmt_check && $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
            if ($row['total'] > 0) {
                sqlsrv_free_stmt($stmt_check);
                sqlsrv_close($conn);
                return ['success' => false, 'message' => "Usuário '{$username}' já existe no banco de dados."];
            }
        }
        if ($stmt_check) sqlsrv_free_stmt($stmt_check);
        
        // TENTAR INSERÇÃO
        $sql_insert = "INSERT INTO dbo.vW_Usuario (Nome, Usuario, senha) VALUES (?, ?, ?)";
        $params_insert = [$fullName, $username, $passwordHash];
        
        $stmt_insert = sqlsrv_query($conn, $sql_insert, $params_insert);
        
        if ($stmt_insert === false) {
            $errors = sqlsrv_errors();
            $error_msg = "Erro na inserção SQL: ";
            foreach ($errors as $error) {
                $error_msg .= "[Code: {$error['code']}, Message: {$error['message']}] ";
            }
            throw new Exception($error_msg);
        }
        
        // !!! VERIFICAÇÃO CRÍTICA DE LINHAS AFETADAS !!!
        $rows_affected = sqlsrv_rows_affected($stmt_insert);

        if ($rows_affected > 0) {
             sqlsrv_free_stmt($stmt_insert);
             sqlsrv_close($conn);
             return ['success' => true, 'message' => 'Usuário cadastrado com sucesso!'];
        } else {
             // Isso acontece se a View for complexa ou se houver um bloqueio/permissão.
             sqlsrv_free_stmt($stmt_insert);
             sqlsrv_close($conn);
             error_log("ALERTA: SQL INSERT falhou silenciosamente (0 linhas afetadas). Permissão ou View de escrita.");
             return ['success' => false, 'message' => 'Erro interno. O banco de dados não aceitou a escrita (código de retorno 0). Verifique permissões na tabela/View.'];
        }
        
    } catch (Exception $e) {
        if ($conn) sqlsrv_close($conn);
        error_log("ERRO FINAL NO REGISTRO: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()];
    }
}

// =====================================================
// INCLUSÃO DO PHPMailer (mantido)
// =====================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-6.10.0/src/Exception.php';
require 'PHPMailer-6.10.0/src/PHPMailer.php';
require 'PHPMailer-6.10.0/src/SMTP.php';

// =====================================================
// PROCESSAMENTO DAS REQUISIÇÕES
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'send_code') {
        $email_cliente = $input['email'] ?? ''; 
        
        if (empty($email_cliente) || !filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            exit;
        }

        $verification_code = sprintf('%06d', mt_rand(1, 999999));
        $_SESSION['verification_code'] = $verification_code; 
        $_SESSION['verification_email'] = $email_cliente; 
        $_SESSION['verification_time'] = time();
        
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'marcelloxmarcellojr@gmail.com'; 
            $mail->Password   = 'aqzv ccdt rgid oxtr'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('marcelloxmarcellojr@gmail.com', 'Sistema de Cadastro');
            $mail->addAddress($email_cliente); 

            $mail->isHTML(true);
            $mail->Subject = 'Código de Verificação';
            $mail->Body    = "<h1>Seu Código: {$verification_code}</h1>";
            $mail->AltBody = "Código: {$verification_code}";

            $mail->send();
            
            echo json_encode(['success' => true, 'message' => 'Código enviado para ' . $email_cliente]);
        } catch (Exception $e) {
            error_log("Erro email: " . $mail->ErrorInfo);
            echo json_encode(['success' => false, 'message' => 'Erro ao enviar código. Verifique as credenciais do PHPMailer.']);
        }
    
    } elseif ($action === 'verify_code') {
        $email_request = $input['email'] ?? '';
        $code_request = $input['code'] ?? '';

        $session_code = $_SESSION['verification_code'] ?? null;
        $session_email = $_SESSION['verification_email'] ?? null;
        $session_time = $_SESSION['verification_time'] ?? null;

        if ($session_time && (time() - $session_time) > 600) {
            unset($_SESSION['verification_code'], $_SESSION['verification_email'], $_SESSION['verification_time']);
            echo json_encode(['success' => false, 'message' => 'Código expirado']);
            exit;
        }

        if (empty($session_code) || empty($session_email)) {
            echo json_encode(['success' => false, 'message' => 'Sessão expirada. Tente reenviar o código.']);
            exit;
        }

        if ($email_request === $session_email && $code_request === $session_code) {
            unset($_SESSION['verification_code'], $_SESSION['verification_time']); 
            $_SESSION['verified_email'] = $session_email; 
            $_SESSION['verified_time'] = time();

            echo json_encode(['success' => true, 'message' => 'Código verificado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Código incorreto']);
        }
    
    } elseif ($action === 'register_user') {
        $verified_email = $_SESSION['verified_email'] ?? null;
        $verified_time = $_SESSION['verified_time'] ?? null;
        $email_request = $input['email'] ?? '';

        if ($verified_time && (time() - $verified_time) > 1800) {
            unset($_SESSION['verified_email'], $_SESSION['verified_time']);
            echo json_encode(['success' => false, 'message' => 'Verificação expirada. Recomece o cadastro.']);
            exit;
        }

        if (empty($verified_email) || $verified_email !== $email_request) {
            echo json_encode(['success' => false, 'message' => 'Email não verificado ou sessão inválida.']);
            exit;
        }

        $fullName = trim($input['fullName'] ?? ''); 
        $username = trim($input['username'] ?? ''); 
        $password = $input['password'] ?? '';

        if (empty($fullName) || empty($username) || strlen($password) < 4) { 
            echo json_encode(['success' => false, 'message' => 'Por favor, preencha Nome, Usuário e Senha (mínimo 4 caracteres).']);
            exit;
        }

        $result = registerUserFixed($email_request, $fullName, $username, $password);

        if ($result['success']) {
            unset($_SESSION['verified_email'], $_SESSION['verified_time']);
        }

        echo json_encode($result);

    } else {
        echo json_encode(['success' => false, 'message' => 'Ação desconhecida']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>