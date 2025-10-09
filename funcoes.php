<?php
// Inclui o arquivo de conexão
require_once 'conexao.php';

/**
 * Busca os dados de uma tabela específica (apenas os 5 primeiros registros para uma visão geral).
 * @param PDO $pdo Objeto de conexão PDO.
 * @param string $nome_tabela O nome da tabela.
 * @return array Um array com 'colunas' e 'registros'.
 */
function buscarDadosTabela($pdo, $nome_tabela) {
    $dados = [
        'titulo' => str_replace(['tbl_Ind_', 'tbl_'], '', $nome_tabela), // Título amigável
        'colunas' => [],
        'registros' => []
    ];

    try {
        // 1. Consulta para obter os dados (limita a 5 registros)
        $stmt_registros = $pdo->prepare("SELECT * FROM `{$nome_tabela}` LIMIT 5");
        $stmt_registros->execute();
        $registros = $stmt_registros->fetchAll(PDO::FETCH_ASSOC);

        if (empty($registros)) {
            // Se não houver registros, apenas retorna as colunas
            $dados['colunas'] = buscarColunasTabela($pdo, $nome_tabela);
            return $dados;
        }

        // 2. Obtém as colunas (chaves do primeiro registro)
        $dados['colunas'] = array_keys($registros[0]);

        // 3. Formata os registros
        foreach ($registros as $row) {
            $dados['registros'][] = array_values($row);
        }

    } catch (PDOException $e) {
        // Trata erro (ex: tabela não existe)
        $dados['titulo'] = "ERRO: {$nome_tabela}";
        $dados['colunas'] = ['Erro'];
        $dados['registros'][] = ["Não foi possível carregar os dados. Verifique o nome da tabela. Erro: " . $e->getMessage()];
    }
    
    return $dados;
}

/**
 * Busca apenas os nomes das colunas (útil para tabelas vazias)
 */
function buscarColunasTabela($pdo, $nome_tabela) {
    // Usa uma consulta mais leve para obter metadados das colunas
    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = :table_name");
    $stmt->bindParam(':db_name', DB_NAME);
    $stmt->bindParam(':table_name', $nome_tabela);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Lista das tabelas que você deseja exibir
$tabelas_desejadas = [
    'tbl_Ind_Empresa',
    'tbl_Ind_Departamento',
    'tbl_Ind_Usuario',
    'tbl_Ind_Indicadores',
    'tbl_IndicadoresAno'
];

$dados_reais = [];
foreach ($tabelas_desejadas as $tabela) {
    $dados_reais[$tabela] = buscarDadosTabela($pdo, $tabela);
}