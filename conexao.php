<?php
// Arquivo: conexao.php
// Objetivo: Estabelecer a conexão com o SQL Server e buscar os dados de tbl_Ind_Departamento.

// --- 1. CONFIGURAÇÕES DE CONEXÃO (Confirmado que o banco 'Indicadores' é o correto) ---
$serverName = "192.168.0.8, 1433"; 
$connectionOptions = [
    "Database" => "Indicadores", // Banco de dados correto
    "Uid" => "sa",
    "PWD" => "aplak2904&",
    "CharacterSet" => "UTF-8",
    "LoginTimeout" => 5,
    "ReturnDatesAsStrings" => true
];

// --- 2. CONEXÃO COM O BANCO DE DADOS ---
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("Falha na conexão com o SQL Server. Verifique se o banco 'Indicadores' existe: " . print_r(sqlsrv_errors(), true));
}

// --- 3. CONSULTA SQL (CORRIGIDA COM OS NOMES DE COLUNAS REAIS DA TABELA!) ---
$sql = "SELECT 
            CodDepto as codigo,      -- Corrigido de CodDepartamento
            Nome as nome,            -- Corrigido de NomeDepartamento
            -- Descricao, Status: Removidas pois não estão visíveis na imagem. 
            -- Se existirem, use o nome EXATO!
            dt_Incl as data_criacao, -- Corrigido de DataCriacao
            User_Incl as responsavel -- Corrigido de Responsavel
        FROM [dbo].[tbl_Ind_Departamento]
        ORDER BY dt_Incl DESC";     // Ordenando pela coluna de data real

$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    // Se falhar agora, o erro é no nome de alguma coluna ou na ausência de colunas esperadas.
    die("Falha na consulta SQL. Ajuste os nomes de coluna novamente: " . print_r(sqlsrv_errors(), true));
}

// --- 4. PROCESSAR OS DADOS ---
$dados_departamentos = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $dados_departamentos[] = $row;
}

sqlsrv_free_stmt($stmt); 

// --- 5. FECHAR CONEXÃO ---
sqlsrv_close($conn);


// --- 6. EXIBIR OS DADOS (PARA TESTE) ---
echo "<h2>Dados da tbl_Ind_Departamento (Retornados do DB 'Indicadores')</h2>";
echo "<pre>";
print_r($dados_departamentos);
echo "</pre>";
?>