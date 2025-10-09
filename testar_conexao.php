<?php
// Arquivo: testar_conexao.php

// 1. MOSTRAR TODOS OS ERROS POSSÍVEIS
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. CONFIGURAÇÕES DE CONEXÃO (CORRIGIDAS PARA O DRIVER SQLSRV)

// A porta deve ser incluída no nome do servidor, separada por vírgula.
$serverName = "192.168.0.8, 1433"; 

$connectionOptions = [
    "Database" => "dbkalpa",
    "Uid" => "sa",
    "PWD" => "aplak2904&",
    "CharacterSet" => "UTF-8",
    "LoginTimeout" => 5,
    "ReturnDatesAsStrings" => true
    // REMOVIDA: A opção "Port" não é suportada por sqlsrv_connect
];

// 3. TENTAR CONECTAR
$conn = null; // Inicializa a variável de conexão
try {
    echo "<p style=\"font-family: sans-serif; color: #333;\">Tentando conectar ao SQL Server no **{$serverName}**...</p>";
    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if ($conn) {
        echo "<h2 style=\"font-family: sans-serif; color: green;\">✅ Conexão com o SQL Server estabelecida com sucesso!</h2>";
        // Opcional: Tentar executar uma consulta simples para confirmar que a conexão está ativa
        $sql = "SELECT GETDATE() AS CurrentDateTime";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            echo "<p style=\"font-family: sans-serif; color: #333;\">Consulta de teste executada com sucesso. Data/Hora do servidor: <b>" . $row[
                'CurrentDateTime'
            ] . "</b></p>";
            sqlsrv_free_stmt($stmt);
        } else {
            echo "<p style=\"font-family: sans-serif; color: orange;\">⚠️ Conexão estabelecida, mas a consulta de teste falhou. Isso pode indicar problemas de permissão ou com a consulta em si.</p>";
            echo "<pre style=\"font-family: monospace; background-color: #f8f8f8; padding: 10px; border: 1px solid #ddd;\">" . htmlspecialchars(print_r(sqlsrv_errors(), true)) . "</pre>";
        }
    } else {
        echo "<h2 style=\"font-family: sans-serif; color: red;\">❌ Falha na conexão com o SQL Server!</h2>";
        echo "<p style=\"font-family: sans-serif; color: #333;\">Detalhes do erro:</p>";
        // Exibe os erros de conexão
        echo "<pre style=\"font-family: monospace; background-color: #f8f8f8; padding: 10px; border: 1px solid #ddd;\">" . htmlspecialchars(print_r(sqlsrv_errors(), true)) . "</pre>";
    }

} catch (Exception $e) {
    echo "<h2 style=\"font-family: sans-serif; color: red;\">❌ Erro crítico durante a tentativa de conexão!</h2>";
    echo "<p style=\"font-family: sans-serif; color: #333;\">Mensagem: " . htmlspecialchars($e->getMessage()) . "</p>";
    if (function_exists('sqlsrv_errors')) {
        echo "<pre style=\"font-family: monospace; background-color: #f8f8f8; padding: 10px; border: 1px solid #ddd;\">" . htmlspecialchars(print_r(sqlsrv_errors(), true)) . "</pre>";
    }
} finally {
    // 4. FECHAR CONEXÃO (se estiver aberta)
    if ($conn) {
        sqlsrv_close($conn);
        echo "<p style=\"font-family: sans-serif; color: #333;\">Conexão com o SQL Server fechada após o teste.</p>";
    }
}

?>