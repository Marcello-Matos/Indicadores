<?php
// =========================================================================
// ATENÇÃO: LINHAS DE EXIBIÇÃO DE ERROS - ESSENCIAIS PARA DEPURAR
// Se a página ficar em branco, essas linhas forçarão a mensagem de erro.
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================================================================

// Controle para exibir ou ocultar os logs de debug no HTML.
// Altere para 'true' se precisar ver o log de SQL ou dados.
$show_debug_html = false; // Voltei para false para produção

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

// Variáveis de log e erro
$debug_logs = [];
$erro = '';

if ($conn === false) {
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

// Processar filtros
$data_inicio = filter_input(INPUT_POST, 'data_inicio', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$data_fim 	 = filter_input(INPUT_POST, 'data_fim', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$status_filtro = filter_input(INPUT_POST, 'status_filtro', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// Validação das datas
if (!empty($data_inicio) && !empty($data_fim) && $data_inicio > $data_fim) {
    $erro = "Data início não pode ser maior que data fim!";
}

// ----------------------------------------------------
// LÓGICA DE EXECUÇÃO DE CONSULTAS
// ----------------------------------------------------
if (empty($erro) && $conn !== false) {
    try {
        // --- CONSTRUÇÃO DA CLÁUSULA WHERE E PARÂMETROS ---
        $where_conditions = [];
        $query_params = [];
        
        // Filtro de Datas - Baseado na coluna DtEmissao da imagem
        if (!empty($data_inicio)) {
            $where_conditions[] = "CAST(DtEmissao AS DATE) >= ?";
            $query_params[] = $data_inicio;
        }
        if (!empty($data_fim)) {
            $where_conditions[] = "CAST(DtEmissao AS DATE) <= ?";
            $query_params[] = $data_fim;
        }
        
        $where_clause_graphs = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";
        $params_datas = $query_params;
        
        // --- CONSULTA PRINCIPAL ---
        $main_where_conditions = $where_conditions;
        $main_query_params = $query_params;
        
        if (!empty($status_filtro)) {
            // Converter o status do filtro para o valor numérico correto
            $status_map = [
                'ATRASADO' => '10',
                'NA DATA' => '20', 
                'ADIANTADO' => '30'
            ];
            $status_value = $status_map[$status_filtro] ?? $status_filtro;
            $main_where_conditions[] = "StatusEntrega = ?";
            $main_query_params[] = $status_value;
        }

        $main_where_clause = !empty($main_where_conditions) ? " WHERE " . implode(" AND ", $main_where_conditions) : "";
        
        // CONSULTA PRINCIPAL - SELECIONANDO APENAS AS COLUNAS DA IMAGEM
        $sql_principal = "SELECT 
                            NF,
                            CodCliente,
                            Cliente,
                            NatOperacao,
                            Modelo,
                            MovEstoque,
                            DtEmissao,
                            DtSaida,
                            GapEmissaoSaida
                            FROM vW_Ind47_Formatada $main_where_clause 
                            ORDER BY NF DESC";
        
        $debug_logs[] = "SQL Principal: " . $sql_principal;

        $stmt = sqlsrv_query($conn, $sql_principal, $main_query_params);
        
        if ($stmt === false) {
            throw new Exception("Erro ao executar consulta principal: " . print_r(sqlsrv_errors(), true));
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $dados[] = $row;
            $total_registros++;
        }
        sqlsrv_free_stmt($stmt);

        $debug_logs[] = "Registros encontrados na consulta principal: " . $total_registros;

        // --- CONSULTAS PARA GRÁFICOS ---
        
        // 1. Gráfico de Status - Baseado no GapEmissaoSaida
        $sql_status = "SELECT 
                     CASE 
                          WHEN GapEmissaoSaida = 0 THEN 'NA DATA'
                          WHEN GapEmissaoSaida < 0 THEN 'ADIANTADO' 
                          WHEN GapEmissaoSaida > 0 THEN 'ATRASADO'
                      END as status,
                      COUNT(*) as total 
                      FROM vW_Ind47_Formatada $where_clause_graphs 
                      GROUP BY 
                      CASE 
                          WHEN GapEmissaoSaida = 0 THEN 'NA DATA'
                          WHEN GapEmissaoSaida < 0 THEN 'ADIANTADO' 
                          WHEN GapEmissaoSaida > 0 THEN 'ATRASADO'
                      END";
        
        $results_status = sqlsrv_query($conn, $sql_status, $params_datas);
        if ($results_status) {
            while ($row = sqlsrv_fetch_array($results_status, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['status'][$row['status']] = $row['total'];
            }
            sqlsrv_free_stmt($results_status);
        }

        // 2. Gráfico de Top 10 Clientes
        $sql_clientes = "SELECT TOP 10 Cliente, COUNT(*) as total 
                         FROM vW_Ind47_Formatada $where_clause_graphs 
                         GROUP BY Cliente 
                         ORDER BY total DESC";
        
        $results_clientes = sqlsrv_query($conn, $sql_clientes, $params_datas);
        if ($results_clientes) {
            while ($row = sqlsrv_fetch_array($results_clientes, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['top_clientes'][$row['Cliente']] = $row['total'];
            }
            sqlsrv_free_stmt($results_clientes);
        }

        // 3. Gráfico de Top 10 Produtos (usando NatOperacao como produto)
        $sql_produtos = "SELECT TOP 10 NatOperacao as Produto, COUNT(*) as total 
                         FROM vW_Ind47_Formatada $where_clause_graphs 
                         GROUP BY NatOperacao 
                         ORDER BY total DESC";
        
        $results_produtos = sqlsrv_query($conn, $sql_produtos, $params_datas);
        if ($results_produtos) {
            while ($row = sqlsrv_fetch_array($results_produtos, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['top_produtos'][$row['Produto']] = $row['total'];
            }
            sqlsrv_free_stmt($results_produtos);
        }

        // 4. Gráfico de Vendas Mensais (Últimos 12 meses) - CONSULTA CORRIGIDA
        $sql_mensal = "SELECT 
                     FORMAT(CAST(DtEmissao AS DATE), 'yyyy-MM') as mes_ano, 
                     COUNT(*) as total 
                     FROM vW_Ind47_Formatada 
                     WHERE CAST(DtEmissao AS DATE) >= DATEADD(MONTH, -12, CAST(GETDATE() AS DATE))
                     AND CAST(DtEmissao AS DATE) <= CAST(GETDATE() AS DATE)
                     GROUP BY FORMAT(CAST(DtEmissao AS DATE), 'yyyy-MM')
                     ORDER BY mes_ano";
        
        $debug_logs[] = "SQL Mensal: " . $sql_mensal;
        
        $results_mensal = sqlsrv_query($conn, $sql_mensal);
        if ($results_mensal) {
            while ($row = sqlsrv_fetch_array($results_mensal, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['vendas_mensais'][$row['mes_ano']] = $row['total'];
            }
            sqlsrv_free_stmt($results_mensal);
            $debug_logs[] = "Dados mensais encontrados: " . count($dados_graficos['vendas_mensais']);
        } else {
            $debug_logs[] = "Erro na consulta mensal: " . print_r(sqlsrv_errors(), true);
        }

        // 5. Gráfico de Vendas Anuais (Últimos 5 anos) - CONSULTA CORRIGIDA
        $sql_anual = "SELECT 
                     YEAR(CAST(DtEmissao AS DATE)) as ano,
                     COUNT(*) as total 
                     FROM vW_Ind47_Formatada 
                     WHERE CAST(DtEmissao AS DATE) >= DATEADD(YEAR, -5, CAST(GETDATE() AS DATE))
                     AND CAST(DtEmissao AS DATE) <= CAST(GETDATE() AS DATE)
                     AND ISDATE(DtEmissao) = 1
                     GROUP BY YEAR(CAST(DtEmissao AS DATE))
                     ORDER BY ano";
        
        $results_anual = sqlsrv_query($conn, $sql_anual);
        if ($results_anual) {
            while ($row = sqlsrv_fetch_array($results_anual, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['vendas_anuais'][$row['ano']] = $row['total'];
            }
            sqlsrv_free_stmt($results_anual);
        }
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
        $debug_logs[] = "Erro geral: " . $erro;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INDICADORES 47 - EMBAQUIM</title>
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

            /* VARIAVEIS ADICIONADAS DO CÓDIGO DE REFERÊNCIA */
            --accent-color: #00f2fe; /* Cor do ícone do usuário, azul-ciano */
            --dark-bg: #0f0f23;
            --header-border: rgba(255, 255, 255, 0.1);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.2);
            --glow-color: rgba(0, 242, 254, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #1e1b4b 100%);
            color: var(--text);
            min-height: 100vh;
        }

        .container { max-width: 95%; margin: 0 auto; padding: 20px; }
        
        .header {
            background: var(--card-bg); 
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass); 
            border-radius: 20px;
            padding: 30px 40px; 
            margin-bottom: 30px;
            position: relative;
        }
        
        .header-content {
            display: flex;
            align-items: flex-start; /* Altera para alinhar o topo dos elementos */
            justify-content: space-between;
            width: 100%;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
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
        }
        
        /* NOVO CONTAINER PARA ALINHAR PERFIL E VOLTAR VERTICALMENTE */
        .user-actions {
            display: flex;
            flex-direction: column; /* Empilha os elementos verticalmente */
            align-items: flex-end; /* Alinha os elementos à direita */
            gap: 10px; /* Espaçamento entre os botões */
        }

        /* INÍCIO DOS ESTILOS DE BOTÃO COPIADOS DO CÓDIGO DE REFERÊNCIA */
        .user-profile {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative; /* Não precisa mais ser absolute aqui */
            background: rgba(15, 15, 35, 0.5);
            border: 1px solid var(--header-border);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-light);
            /* Removi as propriedades top e right */
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
            color: var(--accent-color); /* Azul Ciano */
            filter: drop-shadow(0 0 5px var(--glow-color)); /* Efeito de brilho */
        }

        .avatar::after {
            content: '';
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #00ff00; /* Ponto verde de status */
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
        
        /* Botão de Voltar Abaixo do Usuário (CSS ajustado) */
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
            text-decoration: none;
            position: relative; /* Remove o absolute para se ajustar ao fluxo normal da div.user-actions */
            /* Removi as propriedades top e right */
        }

        .back-button-below:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
        }
        /* FIM DOS ESTILOS DE BOTÃO COPIADOS */


        .error {
            background: var(--danger); color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;
        }
        .debug-logs { 
            background: #1c2630; color: var(--text-muted); padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; max-height: 300px; overflow-y: auto; border: 1px solid var(--border); 
        }
        .debug-logs h4 { color: var(--accent); margin-bottom: 10px; }

        .filtros {
            background: var(--card-bg); backdrop-filter: blur(10px);
            border: 1px solid var(--glass); border-radius: 16px;
            padding: 25px; margin-bottom: 30px;
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
            display: flex; align-items: center; gap: 8px;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-secondary { 
            background: linear-gradient(135deg, var(--secondary) 0%, #475569 100%); 
        }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: var(--card-bg); border: 1px solid var(--glass); border-radius: 16px;
            padding: 25px; text-align: center;
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
        
        /* Estilos para destaque do Gap */
        .gap-negativo { color: var(--warning); font-weight: bold; }
        .gap-positivo { color: var(--danger); font-weight: bold; }
        .gap-zero { color: var(--success); font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-chart-network"></i></div>
                    <div>
                        <h1>INDICADORES 47 - EMBAQUIM</h1>
                        <p>Análise de Performance de Pedidos</p>
                    </div>
                </div>

                <div class="user-actions">
                    <div class="user-profile">
                        <div class="avatar">
                            <i class="fas fa-user-circle"></i> 
                        </div>
                        <span class="user-name">TESTE GERENTE</span>
                    </div>

                    <a href="dashboard.php" class="back-button-below">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
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
                <h4>📋 Logs de Debug:</h4>
                <?php foreach ($debug_logs as $log): ?>
                    <div><?php echo htmlspecialchars($log); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="filtros">
            <div class="form-group">
                <label for="data_inicio"><i class="fas fa-calendar-alt"></i> Data Início:</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
            </div>
            
            <div class="form-group">
                <label for="data_fim"><i class="fas fa-calendar-check"></i> Data Fim:</label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
            </div>
            
            <div class="form-group">
                <label for="status_filtro"><i class="fas fa-filter"></i> Status:</label>
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
                    <div class="stat-label">Total de Notas Fiscais</div>
                </div>

                <?php 
                    $atrasados = $dados_graficos['status']['ATRASADO'] ?? 0;
                    $na_data = $dados_graficos['status']['NA DATA'] ?? 0;
                    $adiantado = $dados_graficos['status']['ADIANTADO'] ?? 0;
                    $total_status = $atrasados + $na_data + $adiantado;
                    $percent_atraso = $total_status > 0 ? ($atrasados / $total_status) * 100 : 0;
                    $percent_na_data = $total_status > 0 ? ($na_data / $total_status) * 100 : 0;
                ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock" style="color: var(--danger);"></i></div>
                    <div class="stat-number"><?= number_format($atrasados, 0, ',', '.') ?></div>
                    <div class="stat-label">Notas Atrasadas (<?= number_format($percent_atraso, 1, ',', '.') ?>%)</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle" style="color: var(--success);"></i></div>
                    <div class="stat-number"><?= number_format($na_data, 0, ',', '.') ?></div>
                    <div class="stat-label">Notas na Data (<?= number_format($percent_na_data, 1, ',', '.') ?>%)</div>
                </div>
            </div>

            <div class="dashboard">
                <div class="card">
                    <h3><i class="fas fa-chart-pie"></i> Distribuição de Status</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['status'])): ?>
                            <canvas id="chartStatus"></canvas>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-pie"></i>
                                <p>Nenhum dado disponível para o gráfico de status.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-user-chart"></i> Top 10 Clientes</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['top_clientes'])): ?>
                            <canvas id="chartClientes"></canvas>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-user-chart"></i>
                                <p>Nenhum dado disponível para o gráfico de clientes.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> Top 10 Tipos de Operação</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['top_produtos'])): ?>
                            <canvas id="chartProdutos"></canvas>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-bar"></i>
                                <p>Nenhum dado disponível para o gráfico de operações.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chart-line"></i> Notas Mensais (12 meses)</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['vendas_mensais'])): ?>
                            <canvas id="chartVendasMensais"></canvas>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-line"></i>
                                <p>Nenhum dado disponível para o gráfico mensal.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-calendar-alt"></i> Notas Anuais</h3>
                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['vendas_anuais'])): ?>
                            <canvas id="chartVendasAnuais"></canvas>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-calendar-alt"></i>
                                <p>Nenhum dado disponível para o gráfico anual.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <script>
                // Dados dos gráficos
                const chartData = {
                    status: {
                        labels: <?= json_encode(array_keys($dados_graficos['status'] ?? [])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['status'] ?? [])) ?>
                    },
                    clientes: {
                        labels: <?= json_encode(array_keys($dados_graficos['top_clientes'] ?? [])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['top_clientes'] ?? [])) ?>
                    },
                    produtos: {
                        labels: <?= json_encode(array_keys($dados_graficos['top_produtos'] ?? [])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['top_produtos'] ?? [])) ?>
                    },
                    vendasMensais: {
                        labels: <?= json_encode(array_keys($dados_graficos['vendas_mensais'] ?? [])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['vendas_mensais'] ?? [])) ?>
                    },
                    vendasAnuais: {
                        labels: <?= json_encode(array_keys($dados_graficos['vendas_anuais'] ?? [])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['vendas_anuais'] ?? [])) ?>
                    }
                };

                console.log('Dados para gráfico mensal:', chartData.vendasMensais);

                // Configuração padrão do Chart.js
                Chart.defaults.color = '#e2e8f0';
                Chart.defaults.font.family = 'Segoe UI';

                function initializeCharts() {
                    console.log('Inicializando gráficos...');
                    
                    // Gráfico de Status (Pizza)
                    if (chartData.status.labels.length > 0 && chartData.status.data.some(val => val > 0)) {
                        const ctx = document.getElementById('chartStatus').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: chartData.status.labels,
                                datasets: [{
                                    data: chartData.status.data,
                                    backgroundColor: ['#ef4444', '#10b981', '#f59e0b'],
                                    borderWidth: 2,
                                    borderColor: '#1e293b'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = Math.round((context.parsed / total) * 100);
                                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Gráfico de Clientes (Barras Horizontais)
                    if (chartData.clientes.labels.length > 0 && chartData.clientes.data.some(val => val > 0)) {
                        const ctx = document.getElementById('chartClientes').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: chartData.clientes.labels.map(label => 
                                    label.length > 25 ? label.substring(0, 25) + '...' : label
                                ),
                                datasets: [{
                                    label: 'Quantidade de Notas',
                                    data: chartData.clientes.data,
                                    backgroundColor: '#3b82f6',
                                    borderColor: '#1d4ed8',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        }
                                    },
                                    y: {
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Gráfico de Produtos (Barras Horizontais)
                    if (chartData.produtos.labels.length > 0 && chartData.produtos.data.some(val => val > 0)) {
                        const ctx = document.getElementById('chartProdutos').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: chartData.produtos.labels.map(label => 
                                    label.length > 30 ? label.substring(0, 30) + '...' : label
                                ),
                                datasets: [{
                                    label: 'Quantidade de Notas',
                                    data: chartData.produtos.data,
                                    backgroundColor: '#f59e0b',
                                    borderColor: '#d97706',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        }
                                    },
                                    y: {
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Gráfico de Vendas Mensais (Linha) - CORRIGIDO
                    if (chartData.vendasMensais.labels.length > 0 && chartData.vendasMensais.data.some(val => val > 0)) {
                        console.log('Criando gráfico mensal com dados:', chartData.vendasMensais);
                        const ctx = document.getElementById('chartVendasMensais').getContext('2d');
                        
                        // Formatar labels para exibição mais amigável
                        const labels = chartData.vendasMensais.labels;
                        const data = chartData.vendasMensais.data;
                        
                        // Ordenar os dados por data
                        const sortedData = labels.map((label, index) => ({
                            label: label,
                            data: data[index]
                        })).sort((a, b) => a.label.localeCompare(b.label));
                        
                        const sortedLabels = sortedData.map(item => {
                            const [year, month] = item.label.split('-');
                            const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                            return `${monthNames[parseInt(month) - 1]}/${year}`;
                        });
                        
                        const sortedValues = sortedData.map(item => item.data);

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: sortedLabels,
                                datasets: [{
                                    label: 'Quantidade de Notas',
                                    data: sortedValues,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 3,
                                    pointBackgroundColor: '#10b981',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2,
                                    pointRadius: 5,
                                    pointHoverRadius: 7
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return value.toLocaleString('pt-BR');
                                            }
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `Notas: ${context.parsed.y.toLocaleString('pt-BR')}`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        console.log('Não foi possível criar gráfico mensal. Dados:', chartData.vendasMensais);
                    }

                    // Gráfico de Vendas Anuais (Barras)
                    if (chartData.vendasAnuais.labels.length > 0 && chartData.vendasAnuais.data.some(val => val > 0)) {
                        const ctx = document.getElementById('chartVendasAnuais').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: chartData.vendasAnuais.labels,
                                datasets: [{
                                    label: 'Notas por Ano',
                                    data: chartData.vendasAnuais.data,
                                    backgroundColor: '#8b5cf6',
                                    borderColor: '#7c3aed',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        }
                                    },
                                    x: {
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.1)'
                                        }
                                    }
                                }
                            }
                        });
                    }
                }

                // Inicializar gráficos quando a página carregar
                document.addEventListener('DOMContentLoaded', function() {
                    initializeCharts();
                });

                // Também inicializar gráficos se a página for carregada via AJAX (após submit do form)
                if (document.readyState === 'complete') {
                    initializeCharts();
                }
            </script>

            <?php if ($total_registros > 0): ?>
                <div class="tabela-container">
                    <div class="tabela-header">
                        <h3><i class="fas fa-table"></i> Detalhes das Notas Fiscais (<?= number_format($total_registros, 0, ',', '.') ?> registros)</h3>
                    </div>
                    <div class="tabela-content">
                        <table>
                            <thead>
                                <tr>
                                    <th>NF</th>
                                    <th>CodCliente</th>
                                    <th>Cliente</th>
                                    <th>NatOperacao</th>
                                    <th>Modelo</th>
                                    <th>MovEstoque</th>
                                    <th>DtEmissao</th>
                                    <th>DtSaida</th>
                                    <th>GapEmissaoSaida</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dados as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $key => $value): ?>
                                            <td>
                                                <?php 
                                                if ($value instanceof DateTime) {
                                                    echo $value->format('d/m/Y');
                                                } else {
                                                    $texto = htmlspecialchars($value ?? '');
                                                    // Destacar o GapEmissaoSaida com cores
                                                    if ($key === 'GapEmissaoSaida') {
                                                        $gap_class = '';
                                                        if ($texto > 0) $gap_class = 'gap-positivo';
                                                        elseif ($texto < 0) $gap_class = 'gap-negativo';
                                                        else $gap_class = 'gap-zero';
                                                        echo "<span class='$gap_class'>$texto</span>";
                                                    } else {
                                                        echo strlen($texto) > 50 ? substr($texto, 0, 50) . '...' : $texto;
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
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        if ($conn) {
            sqlsrv_close($conn);
        }
        ?>
    </div>
</body>
</html>