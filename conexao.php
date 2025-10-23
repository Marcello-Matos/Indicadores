<?php
// Arquivo: conexao.php
// Objetivo: Apenas testar e confirmar a conexão com o SQL Server.

// --- 1. CONFIGURAÇÕES DE CONEXÃO ---
$serverName = "servidor"; 
$connectionOptions = [
    "Database" => "dbkalpa", // Banco de dados
    "Uid" => "sa",
    "PWD" => "aplak",
    "CharacterSet" => "UTF-8",
    "LoginTimeout" => 5, // Tenta conectar por 5 segundos antes de desistir
    "ReturnDatesAsStrings" => true
];

// --- 2. TENTATIVA DE CONEXÃO ---
$conn = sqlsrv_connect($serverName, $connectionOptions);

// ----------------------------------------------------------------------
// *** BLOCO DE VERIFICAÇÃO DE CONEXÃO ***
// ----------------------------------------------------------------------
if (!$conn) {
    // ------------------------------------------------------------------
    // CONEXÃO FALHOU
    // ------------------------------------------------------------------
    echo "<h1 style='color: red;'>ERRO CRÍTICO DE CONEXÃO</h1>";
    echo "<p>Não foi possível conectar ao SQL Server com as credenciais fornecidas.</p>";
    echo "<h3>Detalhes do Erro:</h3>";
    // Exibe os erros detalhados do SQL Server
    die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
} else {
    // ------------------------------------------------------------------
    // CONEXÃO BEM-SUCEDIDA
    // ------------------------------------------------------------------
    echo "<h1 style='color: green;'>SUCESSO!</h1>";
    echo "<p>Conexão estabelecida com sucesso com o servidor **{$serverName}** e o banco de dados **{$connectionOptions['Database']}**.</p>";
    
    // Fechamos a conexão, pois não há mais nada a ser feito.
    sqlsrv_close($conn); 
}

// O restante do código original (consulta, fetch e print) foi removido.
?>