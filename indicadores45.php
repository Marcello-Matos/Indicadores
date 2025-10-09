<?php
// =========================================================================
// ATENÇÃO: LINHAS DE EXIBIÇÃO DE ERROS - ESSENCIAIS PARA DEPURAR
// Se a página ficar em branco, essas linhas forçarão a mensagem de erro.
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================================================================

// Controle para exibir ou ocultar os logs de debug no HTML.
// Altere para 'true' se precisar ver o log de SQL ou dados.
$show_debug_html = false; 

// Configurações de conexão (ATENÇÃO: Credenciais sensíveis em código de produção são um risco de segurança. Considere variáveis de ambiente.)
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

// Variáveis de log e erro
$debug_logs = [];
$erro = '';

if ($conn === false) {
    // Captura o erro de conexão do SQLSRV e interrompe, exibindo a mensagem.
    $erro = "Erro de conexão com o Banco de Dados. Verifique o driver SQLSRV e as credenciais. Detalhes: " . print_r(sqlsrv_errors(), true);
    $debug_logs[] = $erro;
}

// Inicializar variáveis
$total_registros = 0;
$dados = [];
$dados_graficos = [
    'status' => [],
    'top_clientes' => [],
    'top_produtos' => [],
    'vendas_mensais' => [],
    'vendas_anuais' => []
];

// Colunas predefinidas (usadas para logs e montagem de queries)
$colunas_tabela = ['CodItemPedido', 'CodPedido', 'Pedido', 'Item', 'Produto', 'Cliente', 'DtEmissao', 'DtEntrVds', 'DtPCP', 'DtNegocPCP', 'DtLiberaPCP', 'DtUtilizada', 'StatusEntrega', 'DIAS', 'Qtdade', 'QtPend', 'MotivoAtraso'];
$coluna_data = 'DtEmissao'; 
$coluna_status = 'StatusEntrega';
$coluna_cliente = 'Cliente';
$coluna_produto = 'Produto';

// Processar filtros and sanitizar dados (melhor prática de segurança)
$data_inicio = filter_input(INPUT_POST, 'data_inicio', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$data_fim    = filter_input(INPUT_POST, 'data_fim', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$status_filtro = filter_input(INPUT_POST, 'status_filtro', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

$filtros_aplicados = !empty($data_inicio) || !empty($data_fim) || !empty($status_filtro);

// Validação das datas
if (!empty($data_inicio) && !empty($data_fim) && $data_inicio > $data_fim) {
    $erro = "Data início não pode ser maior que data fim!";
}

$debug_logs[] = "Colunas encontradas: " . implode(', ', $colunas_tabela);
$debug_logs[] = "Coluna de data selecionada: " . $coluna_data;
$debug_logs[] = "Coluna de status selecionada: " . $coluna_status;


// ----------------------------------------------------
// LÓGICA DE EXECUÇÃO DE CONSULTAS (Aprimorada com parâmetros)
// ----------------------------------------------------
if (empty($erro) && $conn !== false) {
    try {
        // --- CONSTRUÇÃO DA CLÁUSULA WHERE E PARÂMETROS ---
        $where_conditions = [];
        $query_params = [];
        
        // Filtro de Datas (aplicável a todas as consultas)
        if (!empty($data_inicio)) {
            $where_conditions[] = "CAST($coluna_data AS DATE) >= ?";
            $query_params[] = $data_inicio;
        }
        if (!empty($data_fim)) {
            $where_conditions[] = "CAST($coluna_data AS DATE) <= ?";
            $query_params[] = $data_fim;
        }
        
        // Cláusula base para consultas de filtro de data (usada nos gráficos)
        $where_clause_graphs = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";
        $params_datas = $query_params; // Parâmetros apenas de data
        
        // --- CONSULTA PRINCIPAL (Aplicação do filtro de Status) ---
        $main_where_conditions = $where_conditions;
        $main_query_params = $query_params;
        
        if (!empty($status_filtro)) {
            $main_where_conditions[] = "$coluna_status = ?";
            $main_query_params[] = $status_filtro;
        }

        $main_where_clause = !empty($main_where_conditions) ? " WHERE " . implode(" AND ", $main_where_conditions) : "";
        
        $sql_principal = "SELECT * FROM vW_Ind45_Formatada $main_where_clause ORDER BY 1 DESC";
        // A linha abaixo usa %s para substituição no log (apenas para debug)
        $debug_sql_log = $sql_principal;
        foreach ($main_query_params as $param) {
            $debug_sql_log = preg_replace('/\?/', "'$param'", $debug_sql_log, 1);
        }
        $debug_logs[] = "SQL Principal: " . $debug_sql_log;

        $stmt = sqlsrv_query($conn, $sql_principal, $main_query_params);
        
        if ($stmt === false) {
            throw new Exception("Erro ao executar consulta principal: " . print_r(sqlsrv_errors(), true));
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $dados[] = $row;
            $total_registros++;
        }
        sqlsrv_free_stmt($stmt);

        $debug_logs[] = "Registros encontrados: " . $total_registros;

        // --- 2. DADOS PARA GRÁFICOS (Usando $where_clause_graphs e $params_datas) ---
        
        // 2.1. Gráfico de Status
        $sql_status = "SELECT $coluna_status, COUNT(*) as total FROM vW_Ind45_Formatada $where_clause_graphs GROUP BY $coluna_status";
        $results_status = sqlsrv_query($conn, $sql_status, $params_datas);
        if ($results_status) {
            while ($row = sqlsrv_fetch_array($results_status, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['status'][$row[$coluna_status]] = $row['total'];
            }
            sqlsrv_free_stmt($results_status);
            $debug_logs[] = "Dados Status: " . json_encode($dados_graficos['status']);
        } else { $debug_logs[] = "Erro SQL Status: " . print_r(sqlsrv_errors(), true); }


        // 2.2. Gráfico de Top 10 Clientes
        $sql_clientes = "SELECT TOP 10 $coluna_cliente, COUNT(*) as total FROM vW_Ind45_Formatada $where_clause_graphs GROUP BY $coluna_cliente ORDER BY total DESC";
        $results_clientes = sqlsrv_query($conn, $sql_clientes, $params_datas);
        if ($results_clientes) {
            while ($row = sqlsrv_fetch_array($results_clientes, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['top_clientes'][$row[$coluna_cliente]] = $row['total'];
            }
            sqlsrv_free_stmt($results_clientes);
            $debug_logs[] = "Dados Clientes: " . json_encode($dados_graficos['top_clientes']);
        } else { $debug_logs[] = "Erro SQL Clientes: " . print_r(sqlsrv_errors(), true); }


        // 2.3. Gráfico de Top 10 Produtos
        $sql_produtos = "SELECT TOP 10 $coluna_produto, COUNT(*) as total FROM vW_Ind45_Formatada $where_clause_graphs GROUP BY $coluna_produto ORDER BY total DESC";
        $results_produtos = sqlsrv_query($conn, $sql_produtos, $params_datas);
        if ($results_produtos) {
            while ($row = sqlsrv_fetch_array($results_produtos, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['top_produtos'][$row[$coluna_produto]] = $row['total'];
            }
            sqlsrv_free_stmt($results_produtos);
            $debug_logs[] = "Dados Produtos: " . json_encode($dados_graficos['top_produtos']);
        } else { $debug_logs[] = "Erro SQL Produtos: " . print_r(sqlsrv_errors(), true); }


        // 2.4. Gráfico de Vendas Mensais (Últimos 12 meses)
        $where_mensal_base = " $coluna_data >= DATEADD(MONTH, -12, GETDATE())";
        $where_mensal = empty($where_clause_graphs) ? " WHERE " . $where_mensal_base : $where_clause_graphs . " AND " . $where_mensal_base;
        
        $sql_mensal = "SELECT CONVERT(VARCHAR(7), $coluna_data, 120) as mes_ano, COUNT(*) as total FROM vW_Ind45_Formatada $where_mensal GROUP BY CONVERT(VARCHAR(7), $coluna_data, 120) ORDER BY mes_ano";
        $results_mensal = sqlsrv_query($conn, $sql_mensal, $params_datas);
        if ($results_mensal) {
            while ($row = sqlsrv_fetch_array($results_mensal, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['vendas_mensais'][$row['mes_ano']] = $row['total'];
            }
            sqlsrv_free_stmt($results_mensal);
            $debug_logs[] = "Dados Mensais: " . json_encode($dados_graficos['vendas_mensais']);
        } else { $debug_logs[] = "Erro SQL Mensal: " . print_r(sqlsrv_errors(), true); }

        
        // 2.5. Gráfico de Vendas Anuais (Últimos 5 anos)
        $where_anual_base = " $coluna_data >= DATEADD(YEAR, -5, GETDATE())";
        $where_anual = empty($where_clause_graphs) ? " WHERE " . $where_anual_base : $where_clause_graphs . " AND " . $where_anual_base;
        
        $sql_anual = "SELECT YEAR($coluna_data) as ano, COUNT(*) as total FROM vW_Ind45_Formatada $where_anual GROUP BY YEAR($coluna_data) ORDER BY ano";
        $results_anual = sqlsrv_query($conn, $sql_anual, $params_datas);
        if ($results_anual) {
            while ($row = sqlsrv_fetch_array($results_anual, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['vendas_anuais'][$row['ano']] = $row['total'];
            }
            sqlsrv_free_stmt($results_anual);
            $debug_logs[] = "Dados Anuais: " . json_encode($dados_graficos['vendas_anuais']);
        } else { $debug_logs[] = "Erro SQL Anual: " . print_r(sqlsrv_errors(), true); }

        
    } catch (Exception $e) {
        $erro = $e->getMessage();
        $debug_logs[] = "Erro geral: " . $erro;
    }
}

// Fechar conexão (será fechada no final do HTML)
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INDICADORES 45 - EMBAQUIM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #334155;
            --card-bg: rgba(30, 41, 59, 0.7);
            --glass: rgba(255, 255, 255, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Novas variáveis do dashboard */
            --primary-gradient-start: #6a11cb;
            --primary-gradient-end: #2575fc;
            --secondary-gradient-start: #ff6b6b;
            --secondary-gradient-end: #ffa726;
            --accent-color: #00f2fe;
            --dark-bg: #0f0f23;
            --sidebar-bg: rgba(34, 0, 83, 0.8);
            --active-item-bg: rgba(64, 0, 176, 0.7);
            --hover-bg: rgba(50, 0, 126, 0.6);
            --text-light: #f0f0f0;
            --text-dark: #333;
            --header-border: rgba(255, 255, 255, 0.1);
            --content-bg: rgba(15, 15, 35, 0.4);
            --search-bg: rgba(255, 255, 255, 0.1);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.2);
            --glow-color: rgba(0, 242, 254, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #1e1b4b 100%);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            opacity: 0; 
            transition: opacity 0.3s ease-in-out;
        }

        .container { max-width: 95%; margin: 0 auto; padding: 20px; }
        
        /* Header Principal - AGORA COM PERFIL DO USUÁRIO INTEGRADO */
        .header {
            background: var(--card-bg); 
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass); 
            border-radius: 20px;
            padding: 30px 40px; 
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative; 
        }
        
        .header-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            width: 100%;
            position: relative;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .logo-icon {
            font-size: 2.5em;
            color: var(--accent);
        }
        
        .header h1 {
            font-size: 2.5em; 
            font-weight: 700;
            background: linear-gradient(90deg, var(--text), var(--accent));
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .header p {
            color: var(--text-muted);
            margin-bottom: 3px;
            font-size: 14px;
        }

        /* Perfil do Usuário - AGORA DENTRO DO HEADER NO CANTO SUPERIOR DIREITO */
        .user-profile {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(15, 15, 35, 0.5);
            border: 1px solid var(--header-border);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-light);
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .avatar {
            position: relative;
            margin-right: 12px;
        }

        .avatar i {
            font-size: 36px;
            color: var(--accent-color);
            filter: drop-shadow(0 0 5px var(--glow-color));
        }

        .avatar::after {
            content: '';
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #00ff00;
            border-radius: 50%;
            border: 2px solid var(--dark-bg);
        }

        .user-name {
            font-size: 16px;
            font-weight: 600;
            background: linear-gradient(to right, #ffffff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Botão de Voltar */
        .back-button {
            position: fixed;
            top: 25px;
            left: 25px;
            z-index: 1000;
            background: linear-gradient(135deg, var(--secondary) 0%, #475569 100%);
            color: white;
            border: 1px solid var(--glass);
            border-radius: 50px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
        }
        
        .back-button:active {
            transform: translateY(0);
        }

        /* Botão de Voltar Abaixo do Usuário */
        .back-button-below {
            background: linear-gradient(135deg, var(--secondary) 0%, #475569 100%);
            color: white;
            border: 1px solid var(--glass);
            border-radius: 50px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .back-button-below:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
        }

        .error {
            background: var(--danger); color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 14px;
        }
        .debug-logs { 
            background: #1c2630; color: var(--text-muted); padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; max-height: 200px; overflow-y: auto; border: 1px solid var(--border); 
        }
        .debug-logs h4 { color: var(--accent); margin-bottom: 10px; }

        .filtros {
            background: var(--card-bg); backdrop-filter: blur(10px);
            border: 1px solid var(--glass); border-radius: 16px;
            padding: 25px; margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;
        }
        .form-group { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 180px; }
        .form-group input, .form-group select {
            padding: 12px 15px; background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border); border-radius: 10px;
            font-size: 14px; color: var(--text);
        }
        .btn {
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%); color: white;
            border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer;
            font-size: 14px; font-weight: 600; transition: all 0.2s;
            display: flex; align-items: center; gap: 8px; height: 44px;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-secondary { background: linear-gradient(135deg, var(--secondary) 0%, #475569 100%); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: var(--card-bg); border: 1px solid var(--glass); border-radius: 16px;
            padding: 25px; text-align: center; position: relative; overflow: hidden;
        }
        .stat-icon { font-size: 2.5em; margin-bottom: 15px; opacity: 0.8; }
        .stat-number {
            font-size: 2.8em; font-weight: 800; margin-bottom: 10px;
            background: linear-gradient(90deg, var(--text), var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .card {
            background: var(--card-bg); backdrop-filter: blur(10px);
            border: 1px solid var(--glass); border-radius: 16px;
            padding: 25px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        .card h3 { margin-bottom: 20px; font-size: 1.3em; display: flex; align-items: center; gap: 10px; }
        .chart-wrapper { position: relative; height: 350px; width: 100%; }
        .no-data { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); text-align: center; }
        .no-data i { font-size: 3em; margin-bottom: 15px; opacity: 0.5; }

        .tabela-container { background: var(--card-bg); border: 1px solid var(--glass); border-radius: 16px; overflow: hidden; margin-bottom: 30px; }
        .tabela-header {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .tabela-content { overflow-x: auto; max-height: 70vh; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; }
        th { background: rgba(15, 23, 42, 0.7); font-weight: 600; position: sticky; top: 0; }
        tr:hover { background: rgba(59, 130, 246, 0.1); }
        .search-container { padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; gap: 10px; }
        .search-input { flex: 1; padding: 10px 15px; background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border); border-radius: 8px; color: var(--text); }
        
        @media (max-width: 768px) {
            .dashboard { grid-template-columns: 1fr; }
            .filtros { flex-direction: column; }
            .form-group { width: 100%; }
            .tabela-header { justify-content: center; }
            
            /* Ajustes responsivos para header */
            .header {
                padding: 20px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .user-profile {
                position: relative;
                top: auto;
                right: auto;
                margin-top: 15px;
                align-self: flex-end;
            }
            
            .avatar i {
                font-size: 30px;
            }
            
            .user-name {
                font-size: 14px;
            }
            
            .back-button {
                top: 15px;
                left: 15px;
                padding: 10px 15px;
                font-size: 12px;
            }

            .back-button-below {
                position: relative !important;
                top: auto !important;
                right: auto !important;
                margin-top: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .user-profile {
                flex-direction: column;
                gap: 5px;
                padding: 10px;
                border-radius: 20px;
                align-self: center;
            }
            
            .avatar {
                margin-right: 0;
                margin-bottom: 5px;
            }
            
            .header h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- Header Principal COM PERFIL DO USUÁRIO DENTRO -->
        <div class="header">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-chart-network"></i></div>
                    <div>
                        <h1>INDICADORES 45 - EMBAQUIM</h1>
                        <p>Análise de Performance de Pedidos</p>
                        <p>Teste para ver se vai para a outra linha</p>
                    </div>
                </div>
                
                <!-- Perfil do Usuário - AGORA DENTRO DO HEADER NO CANTO SUPERIOR DIREITO -->
                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i> 
                    </div>
                    <span class="user-name">TESTE GERENTE</span>
                </div>

                <!-- BOTÃO VOLTAR ABAIXO DO USUÁRIO -->
                <div style="position: absolute; top: 70px; right: 40px; z-index: 1000;">
                    <button class="back-button-below" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="error">
                <strong>🚨 Erro:</strong><br>
                <?php echo nl2br(htmlspecialchars($erro)); ?>
            </div>
        <?php endif; ?>

        <?php if ($show_debug_html && !empty($debug_logs)): ?>
            <div class="debug-logs">
                <h4>📋 Logs de Debug (Oculto em produção):</h4>
                <?php foreach ($debug_logs as $log): ?>
                    <div><?php echo htmlspecialchars($log); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="filtros">
            <div class="form-group">
                <label for="data_inicio"><i class="fas fa-calendar-alt"></i> Data Início (Emissão):</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
            </div>
            
            <div class="form-group">
                <label for="data_fim"><i class="fas fa-calendar-check"></i> Data Fim (Emissão):</label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
            </div>
            
            <div class="form-group">
                <label for="status_filtro"><i class="fas fa-filter"></i> Status de Entrega:</label>
                <select id="status_filtro" name="status_filtro">
                    <option value="">Todos</option>
                    <option value="ATRASADO" <?php echo $status_filtro == 'ATRASADO' ? 'selected' : ''; ?>>Atrasado</option>
                    <option value="NA DATA" <?php echo $status_filtro == 'NA DATA' ? 'selected' : ''; ?>>Na Data</option>
                    <option value="ADIANTADO" <?php echo $status_filtro == 'ADIANTADO' ? 'selected' : ''; ?>>Adiantado</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn"><i class="fas fa-sliders-h"></i> Aplicar Filtros</button>
                <a href="?" class="btn btn-secondary"><i class="fas fa-broom"></i> Limpar</a>
            </div>
        </form>

        <?php if ($conn && empty($erro)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-invoice" style="color: var(--accent);"></i></div>
                    <div class="stat-number"><?= number_format($total_registros, 0, ',', '.') ?></div>
                    <div class="stat-label">Total de Registros (Filtro)</div>
                </div>

                <?php 
                    $atrasados = $dados_graficos['status']['ATRASADO'] ?? 0;
                    $na_data = $dados_graficos['status']['NA DATA'] ?? 0;
                    $adiantado = $dados_graficos['status']['ADIANTADO'] ?? 0;
                    $total_status = $atrasados + $na_data + $adiantado;
                    $percent_atraso = $total_status > 0 ? ($atrasados / $total_status) * 100 : 0;
                ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock" style="color: var(--danger);"></i></div>
                    <div class="stat-number" style="-webkit-text-fill-color: var(--danger);">
                        <?= number_format($atrasados, 0, ',', '.') ?>
                    </div>
                    <div class="stat-label">Pedidos Atrasados (<?= number_format($percent_atraso, 1, ',', '.') ?>%)</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle" style="color: var(--success);"></i></div>
                    <div class="stat-number" style="-webkit-text-fill-color: var(--success);"><?= number_format($na_data + $adiantado, 0, ',', '.') ?></div>
                    <div class="stat-label">Entregas no Prazo/Adiantadas</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users" style="color: var(--warning);"></i></div>
                    <div class="stat-number" style="-webkit-text-fill-color: var(--warning);"><?= number_format(count($dados_graficos['top_clientes']), 0, ',', '.') ?></div>
                    <div class="stat-label">Top 10 Clientes Ativos</div>
                </div>
            </div>

            <div class="dashboard">
                <div class="card">
                    <h3><i class="fas fa-chart-pie"></i> Distribuição de Status</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['status'])): ?><canvas id="chartStatus"></canvas><?php else: ?><div class="no-data"><i class="fas fa-chart-pie"></i><p>Nenhum dado disponível.</p></div><?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-user-chart"></i> Top 10 Clientes por Pedidos</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['top_clientes'])): ?><canvas id="chartClientes"></canvas><?php else: ?><div class="no-data"><i class="fas fa-user-chart"></i><p>Nenhum dado disponível.</p></div><?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> Top 10 Produtos Vendidos</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['top_produtos'])): ?><canvas id="chartProdutos"></canvas><?php else: ?><div class="no-data"><i class="fas fa-chart-bar"></i><p>Nenhum dado disponível.</p></div><?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chart-line"></i> Tendência Mensal (Últimos 12 meses)</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['vendas_mensais'])): ?><canvas id="chartVendasMensais"></canvas><?php else: ?><div class="no-data"><i class="fas fa-chart-line"></i><p>Nenhum dado disponível.</p></div><?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-calendar-alt"></i> Vendas Anuais (5 anos)</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['vendas_anuais'])): ?><canvas id="chartVendasAnuais"></canvas><?php else: ?><div class="no-data"><i class="fas fa-calendar-alt"></i><p>Nenhum dado disponível.</p></div><?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chart-area"></i> Distribuição Mensal</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['vendas_mensais'])): ?><canvas id="chartDistribuicao"></canvas><?php else: ?><div class="no-data"><i class="fas fa-chart-area"></i><p>Nenhum dado disponível.</p></div><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tabela-container">
                <div class="tabela-header">
                    <h3><i class="fas fa-table"></i> Detalhes dos Pedidos (<?= number_format($total_registros, 0, ',', '.') ?> registros)</h3>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Exportar Excel</button>
                        <button class="btn btn-secondary" onclick="printReport()"><i class="fas fa-print"></i> Imprimir</button>
                    </div>
                </div>

                <?php if ($total_registros > 0): ?>
                    <div class="search-container">
                        <input type="text" class="search-input" id="searchInput" placeholder="🔍 Buscar em todos os campos...">
                    </div>

                    <div class="tabela-content">
                        <table id="tabela45">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($dados[0]) as $coluna): ?>
                                        <th><?= htmlspecialchars($coluna) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dados as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $key => $value): ?>
                                            <td>
                                                <?php 
                                                // Formatação de datas
                                                if ($value instanceof DateTime) {
                                                    echo $value->format('d/m/Y H:i:s');
                                                } else {
                                                    $texto = htmlspecialchars($value ?? '');
                                                    
                                                    // Estilização do StatusEntrega
                                                    if ($key === 'StatusEntrega') {
                                                        $class = '';
                                                        if ($texto === 'ATRASADO') $class = 'style="color: var(--danger); font-weight: bold;"';
                                                        if ($texto === 'NA DATA') $class = 'style="color: var(--success); font-weight: bold;"';
                                                        if ($texto === 'ADIANTADO') $class = 'style="color: var(--warning); font-weight: bold;"';
                                                        echo "<span $class>$texto</span>";
                                                    } 
                                                    // Truncar textos longos
                                                    elseif (strlen($texto) > 50) {
                                                        echo '<span title="' . $texto . '">' . substr($texto, 0, 50) . '...</span>';
                                                    } 
                                                    else {
                                                        echo $texto;
                                                    }
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="info-total" style="padding: 20px; text-align: center; color: var(--text-muted);">
                        Nenhum registro encontrado com os filtros aplicados.
                    </div>
                <?php endif; ?>
            </div>

            <script>
                // Dados dos gráficos (Passados via PHP para JS)
                const chartData = {
                    status: {
                        labels: <?= json_encode(array_keys($dados_graficos['status'])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['status'])) ?>
                    },
                    clientes: {
                        labels: <?= json_encode(array_keys($dados_graficos['top_clientes'])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['top_clientes'])) ?>
                    },
                    produtos: {
                        labels: <?= json_encode(array_keys($dados_graficos['top_produtos'])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['top_produtos'])) ?>
                    },
                    vendasMensais: {
                        labels: <?= json_encode(array_keys($dados_graficos['vendas_mensais'])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['vendas_mensais'])) ?>
                    },
                    vendasAnuais: {
                        labels: <?= json_encode(array_keys($dados_graficos['vendas_anuais'])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['vendas_anuais'])) ?>
                    }
                };

                Chart.defaults.color = '#e2e8f0';
                Chart.defaults.font.family = 'Segoe UI';

                const colors = [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                    '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#64748b'
                ];
                
                // Função para voltar à página anterior
                function goBack() {
                    if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) {
                        window.history.back();
                    } else {
                        // Se não houver referência válida, redireciona para uma página padrão
                        window.location.href = 'dashboard.php'; // Redireciona para o dashboard
                    }
                }
                
                function initializeCharts() {
                    // Obter as variáveis CSS para cores (essencial para o gráfico de Status)
                    const rootStyles = getComputedStyle(document.documentElement);
                    
                    const statusColors = chartData.status.labels.map(label => {
                        if (label === 'ATRASADO') return rootStyles.getPropertyValue('--danger').trim();
                        if (label === 'NA DATA') return rootStyles.getPropertyValue('--success').trim();
                        if (label === 'ADIANTADO') return rootStyles.getPropertyValue('--warning').trim();
                        return colors[3];
                    });

                    // 1. Gráfico de Status (Doughnut) - CORES CORRIGIDAS
                    if (chartData.status.data.length > 0 && chartData.status.data.some(val => val > 0)) {
                        new Chart(document.getElementById('chartStatus'), {
                            type: 'doughnut',
                            data: { 
                                labels: chartData.status.labels, 
                                datasets: [{ 
                                    data: chartData.status.data, 
                                    backgroundColor: statusColors, 
                                    borderWidth: 2 
                                }] 
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                        });
                    }

                    // 2. Gráfico de Top Clientes (Barra Horizontal)
                    if (chartData.clientes.data.length > 0 && chartData.clientes.data.some(val => val > 0)) {
                        new Chart(document.getElementById('chartClientes'), {
                            type: 'bar',
                            data: { labels: chartData.clientes.labels.map(label => label.length > 20 ? label.substring(0, 20) + '...' : label), datasets: [{ label: 'Pedidos', data: chartData.clientes.data, backgroundColor: colors[0] }] },
                            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { beginAtZero: true } } } }
                        });
                    }

                    // 3. Gráfico de Top Produtos (Barra Horizontal)
                    if (chartData.produtos.data.length > 0 && chartData.produtos.data.some(val => val > 0)) {
                        new Chart(document.getElementById('chartProdutos'), {
                            type: 'bar',
                            data: { labels: chartData.produtos.labels.map(label => label.length > 20 ? label.substring(0, 20) + '...' : label), datasets: [{ label: 'Pedidos', data: chartData.produtos.data, backgroundColor: colors[2] }] },
                            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { beginAtZero: true } } }
                            }
                        });
                    }

                    // 4. Gráfico de Vendas Mensais (Linha)
                    if (chartData.vendasMensais.data.length > 0 && chartData.vendasMensais.data.some(val => val > 0)) {
                        new Chart(document.getElementById('chartVendasMensais'), {
                            type: 'line',
                            data: { labels: chartData.vendasMensais.labels, datasets: [{ label: 'Pedidos', data: chartData.vendasMensais.data, borderColor: colors[0], backgroundColor: colors[0] + '20', fill: true, tension: 0.4 }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                        });
                    }

                    // 5. Gráfico de Vendas Anuais (Barra Vertical)
                    if (chartData.vendasAnuais.data.length > 0 && chartData.vendasAnuais.data.some(val => val > 0)) {
                        new Chart(document.getElementById('chartVendasAnuais'), {
                            type: 'bar',
                            data: { labels: chartData.vendasAnuais.labels, datasets: [{ label: 'Pedidos', data: chartData.vendasAnuais.data, backgroundColor: colors.slice(0, chartData.vendasAnuais.data.length) }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                        });
                    }

                    // 6. Gráfico de Distribuição Temporal (Polar Area) - COM TOOLTIPS
                    if (chartData.vendasMensais.data.length > 0 && chartData.vendasMensais.data.some(val => val > 0)) {
                        new Chart(document.getElementById('chartDistribuicao'), {
                            type: 'polarArea',
                            data: { 
                                labels: chartData.vendasMensais.labels, 
                                datasets: [{ 
                                    data: chartData.vendasMensais.data, 
                                    backgroundColor: colors.slice(0, chartData.vendasMensais.data.length),
                                    borderWidth: 2
                                }] 
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false,
                                plugins: {
                                    tooltip: {
                                        enabled: true, // Ativa os tooltips
                                        callbacks: {
                                            label: function(context) {
                                                return `${context.label}: ${context.parsed} pedidos`;
                                            }
                                        }
                                    },
                                    legend: {
                                        display: true,
                                        position: 'right'
                                    }
                                },
                                scales: {
                                    r: {
                                        ticks: {
                                            display: false // Remove os números do eixo radial
                                        },
                                        grid: {
                                            display: true // Mantém as linhas de grade para visualização
                                        }
                                    }
                                }
                            }
                        });
                    }
                }

                // Eventos
                document.addEventListener('DOMContentLoaded', function() {
                    initializeCharts();
                    
                    const searchInput = document.getElementById('searchInput');
                    const tabela45 = document.getElementById('tabela45');
                    const linhas = tabela45 ? tabela45.querySelectorAll('tbody tr') : [];
                    
                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            const valor = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                            
                            linhas.forEach(function(linha) {
                                const texto = linha.textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                linha.style.display = texto.includes(valor) ? '' : 'none';
                            });
                        });
                    }
                });

                function exportToExcel() {
                    const tabela = document.getElementById('tabela45');
                    if (!tabela) return;

                    const tempTable = tabela.cloneNode(true);
                    tempTable.querySelectorAll('tbody tr').forEach(row => { if (row.style.display === 'none') { row.remove(); } });

                    const html = tempTable.outerHTML;
                    const finalHtml = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"></head><body>${html}</body></html>`;

                    const blob = new Blob([finalHtml], { type: 'application/vnd.ms-excel' });
                    
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'embaquim_indicadores45_' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '.xls';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                function printReport() {
                    window.print();
                }

                // Efeito de transição suave
                window.addEventListener('load', function() {
                    document.body.style.opacity = '1';
                });
            </script>
        <?php endif; ?>

        <?php
        // Fechar conexão no final
        if ($conn) {
            sqlsrv_close($conn);
        }
        ?>
    </div>
</body>
</html>