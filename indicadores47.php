<?php
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

// Verificar conexão
$connection_error = null;
if ($conn === false) {
    $connection_error = "Falha na conexão com o banco de dados: " . print_r(sqlsrv_errors(), true);
}

// Processar filtros
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = $_POST['data_fim'] ?? '';
$periodo = $_POST['periodo'] ?? '';
$mes_ano = $_POST['mes_ano'] ?? '';
$ano = $_POST['ano'] ?? '';

// ** CORREÇÃO 1: Definir o filtro padrão 'ano_atual' **
if ($_SERVER['REQUEST_METHOD'] === 'GET' || (empty($data_inicio) && empty($data_fim) && empty($periodo) && empty($mes_ano) && empty($ano))) {
    $periodo = 'ano_atual';
}

// Inicializar variáveis
$total_registros = 0;
$colunas_originais = [];
$coluna_data = null;
$dados_tabela = [];
$todas_colunas = [];

// Arrays para gráficos
$dados_graficos = [
    'categorias' => [],
    'vendas_mensais' => [],
    'vendas_anuais' => []
];

if ($conn && !$connection_error) {
    try {
        // ** CORREÇÃO 2: Buscar TODOS os dados sem limite **
        $sql_estrutura = "SELECT * FROM vW_Ind47";
        $stmt_estrutura = sqlsrv_query($conn, $sql_estrutura);
        
        if ($stmt_estrutura === false) {
            throw new Exception("Erro ao consultar estrutura da tabela: " . print_r(sqlsrv_errors(), true));
        }
        
        // Obter colunas da primeira linha
        if (sqlsrv_has_rows($stmt_estrutura)) {
            $row = sqlsrv_fetch_array($stmt_estrutura, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $colunas_originais = array_keys($row);
                $todas_colunas = $colunas_originais; // Guardar todas as colunas
                
                // ** CORREÇÃO 3: Detectar coluna de data de forma mais agressiva **
                $colunas_data_candidatas = ['DtEmissao', 'dtEmissao', 'DataEmissao', 'Emissao', 'Data', 'DataSaida', 'DtSaida', 'DataSaída', 'DtSaída'];
                
                foreach ($colunas_data_candidatas as $candidata) {
                    if (in_array($candidata, $colunas_originais)) {
                        $coluna_data = $candidata;
                        break;
                    }
                }
                
                // Se não encontrou, procura por padrões no nome
                if (!$coluna_data) {
                    foreach ($colunas_originais as $coluna) {
                        if (stripos($coluna, 'data') !== false || stripos($coluna, 'dt') !== false || 
                            stripos($coluna, 'emissao') !== false || stripos($coluna, 'saida') !== false ||
                            stripos($coluna, 'date') !== false) {
                            $coluna_data = $coluna;
                            break;
                        }
                    }
                }
                
                // Se ainda não encontrou, usa a primeira coluna do tipo data
                if (!$coluna_data) {
                    $sql_tipos = "SELECT COLUMN_NAME, DATA_TYPE 
                                 FROM INFORMATION_SCHEMA.COLUMNS 
                                 WHERE TABLE_NAME = 'vW_Ind47' 
                                 AND DATA_TYPE IN ('datetime', 'date', 'datetime2', 'smalldatetime')";
                    $stmt_tipos = sqlsrv_query($conn, $sql_tipos);
                    if ($stmt_tipos && $row_tipo = sqlsrv_fetch_array($stmt_tipos, SQLSRV_FETCH_ASSOC)) {
                        $coluna_data = $row_tipo['COLUMN_NAME'];
                    }
                }
                
                // ** CORREÇÃO 4: Voltar ao início do resultset para ler TODOS os dados **
                sqlsrv_free_stmt($stmt_estrutura);
                $stmt_estrutura = sqlsrv_query($conn, $sql_estrutura);
            }
        } else {
            throw new Exception("Nenhum dado encontrado na tabela vW_Ind47");
        }

        // ** CORREÇÃO 5: CONSTRUÇÃO DO WHERE MAIS FLEXÍVEL **
        $where_conditions = [];
        $query_params = [];

        // Filtro por período rápido (prioridade)
        if (!empty($periodo) && $coluna_data) {
            switch ($periodo) {
                case 'hoje':
                    $where_conditions[] = "CAST($coluna_data AS DATE) = CAST(GETDATE() AS DATE)";
                    break;
                case 'ontem':
                    $where_conditions[] = "CAST($coluna_data AS DATE) = CAST(DATEADD(DAY, -1, GETDATE()) AS DATE)";
                    break;
                case 'semana_atual':
                    $where_conditions[] = "$coluna_data >= DATEADD(wk, DATEDIFF(wk, 0, GETDATE()), 0)";
                    break;
                case 'semana_anterior':
                    $where_conditions[] = "$coluna_data >= DATEADD(wk, DATEDIFF(wk, 0, GETDATE()) - 1, 0) AND $coluna_data < DATEADD(wk, DATEDIFF(wk, 0, GETDATE()), 0)";
                    break;
                case 'mes_atual':
                    $where_conditions[] = "MONTH($coluna_data) = MONTH(GETDATE()) AND YEAR($coluna_data) = YEAR(GETDATE())";
                    break;
                case 'mes_anterior':
                    $where_conditions[] = "MONTH($coluna_data) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR($coluna_data) = YEAR(DATEADD(MONTH, -1, GETDATE()))";
                    break;
                case 'trimestre_atual':
                    $where_conditions[] = "DATEPART(QUARTER, $coluna_data) = DATEPART(QUARTER, GETDATE()) AND YEAR($coluna_data) = YEAR(GETDATE())";
                    break;
                case 'ano_atual':
                    $where_conditions[] = "YEAR($coluna_data) = YEAR(GETDATE())";
                    break;
                case 'ano_anterior':
                    $where_conditions[] = "YEAR($coluna_data) = YEAR(GETDATE()) - 1";
                    break;
                case 'todos':
                    // Não adiciona filtro de data - mostra todos os dados
                    break;
            }
        }

        // Filtros manuais (só aplica se não há período rápido)
        if (empty($periodo)) {
            if (!empty($data_inicio) && $coluna_data) {
                $where_conditions[] = "CAST($coluna_data AS DATE) >= ?";
                $query_params[] = $data_inicio;
            }

            if (!empty($data_fim) && $coluna_data) {
                $where_conditions[] = "CAST($coluna_data AS DATE) <= ?";
                $query_params[] = $data_fim;
            }

            if (!empty($mes_ano) && empty($data_inicio) && empty($data_fim) && $coluna_data) {
                list($ano_filtro, $mes_filtro) = explode('-', $mes_ano);
                $where_conditions[] = "YEAR($coluna_data) = ? AND MONTH($coluna_data) = ?";
                $query_params[] = $ano_filtro;
                $query_params[] = $mes_filtro;
            }

            if (!empty($ano) && empty($mes_ano) && empty($data_inicio) && empty($data_fim) && $coluna_data) {
                $where_conditions[] = "YEAR($coluna_data) = ?";
                $query_params[] = $ano;
            }
        }
        
        // Combinação final do WHERE
        $where_sql = "";
        if (!empty($where_conditions)) {
            $where_sql = "WHERE " . implode(" AND ", $where_conditions);
        }

        // ** CORREÇÃO 6: Total de Registros SEM LIMITE **
        $sql_total = "SELECT COUNT(*) as total FROM vW_Ind47 $where_sql";
        $stmt_total = sqlsrv_query($conn, $sql_total, $query_params);
        if ($stmt_total !== false) {
            $row_total = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC);
            $total_registros = $row_total ? $row_total['total'] : 0;
        } else {
            throw new Exception("Erro ao contar registros: " . print_r(sqlsrv_errors(), true));
        }

        // ** CORREÇÃO 7: Buscar TODOS os dados da tabela SEM LIMITE **
        $sql_dados = "SELECT * FROM vW_Ind47 $where_sql ORDER BY $coluna_data DESC";
        $stmt_dados = sqlsrv_query($conn, $sql_dados, $query_params);
        
        if ($stmt_dados !== false) {
            while ($row = sqlsrv_fetch_array($stmt_dados, SQLSRV_FETCH_ASSOC)) {
                $dados_linha = [];
                
                foreach ($colunas_originais as $coluna) {
                    $valor = $row[$coluna];
                    
                    // Formatar datas
                    if ($valor instanceof DateTime) {
                        $valor = $valor->format('d/m/Y H:i:s');
                    }
                    
                    // Tratar valores nulos
                    if ($valor === null) {
                        $valor = '';
                    }
                    
                    // Tratar strings muito longas
                    if (is_string($valor) && strlen($valor) > 100) {
                        $valor = substr($valor, 0, 100) . '...';
                    }
                    
                    $dados_linha[$coluna] = $valor;
                }
                
                $dados_tabela[] = $dados_linha;
            }
        } else {
            throw new Exception("Erro ao buscar dados: " . print_r(sqlsrv_errors(), true));
        }

        // ** CORREÇÃO 8: Dados para gráficos (após buscar todos os dados) **
        
        // Gráfico 1: Distribuição por categoria/UF
        $coluna_categoria = null;
        $possiveis_categorias = ['UF', 'Categoria', 'Tipo', 'Status', 'NatOperacao', 'Modelo', 'Cliente'];
        
        foreach ($possiveis_categorias as $categoria) {
            if (in_array($categoria, $colunas_originais)) {
                $coluna_categoria = $categoria;
                break;
            }
        }

        if ($coluna_categoria) {
            $sql_categorias = "SELECT $coluna_categoria, COUNT(*) as total FROM vW_Ind47 $where_sql GROUP BY $coluna_categoria ORDER BY total DESC";
            $stmt_categorias = sqlsrv_query($conn, $sql_categorias, $query_params);
            if ($stmt_categorias !== false) {
                while ($row = sqlsrv_fetch_array($stmt_categorias, SQLSRV_FETCH_ASSOC)) {
                    $valor = $row[$coluna_categoria];
                    if ($valor === null) $valor = 'Não Informado';
                    $dados_graficos['categorias'][$valor] = $row['total'];
                }
            }
        }

        // Gráfico 2: Vendas Mensais
        if ($coluna_data) {
            $sql_vendas_mensais = "
            SELECT 
                YEAR($coluna_data) as Ano,
                MONTH($coluna_data) as Mes,
                COUNT(*) as Total
            FROM vW_Ind47 
            WHERE $coluna_data >= DATEADD(MONTH, -12, GETDATE())
            GROUP BY YEAR($coluna_data), MONTH($coluna_data)
            ORDER BY Ano ASC, Mes ASC
            ";

            $stmt_mensais = sqlsrv_query($conn, $sql_vendas_mensais);
            if ($stmt_mensais !== false) {
                while ($row = sqlsrv_fetch_array($stmt_mensais, SQLSRV_FETCH_ASSOC)) {
                    $mes_formatado = $row['Ano'] . '-' . str_pad($row['Mes'], 2, '0', STR_PAD_LEFT);
                    $dados_graficos['vendas_mensais'][$mes_formatado] = (int)$row['Total'];
                }
            }

            // Gráfico 3: Vendas Anuais
            $sql_vendas_anuais = "
            SELECT 
                YEAR($coluna_data) as Ano,
                COUNT(*) as Total
            FROM vW_Ind47 
            WHERE $coluna_data IS NOT NULL
            GROUP BY YEAR($coluna_data)
            ORDER BY Ano ASC
            ";
            
            $stmt_anuais = sqlsrv_query($conn, $sql_vendas_anuais);
            if ($stmt_anuais !== false) {
                while ($row = sqlsrv_fetch_array($stmt_anuais, SQLSRV_FETCH_ASSOC)) {
                    $dados_graficos['vendas_anuais'][$row['Ano']] = $row['Total'];
                }
            }
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
} else {
    $error_message = $connection_error;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INDICADORES 47 - DASHBOARD COMPLETO EMBAQUIM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #3b82f6;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #1e1b4b 100%);
            color: var(--text);
            min-height: 100vh;
        }

        .container {
            max-width: 95%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(30, 41, 59, 0.7);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }

        .header-content {
            max-width: 80%;
            margin: 0 auto;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--text), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .filtros {
            background: rgba(30, 41, 59, 0.7);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-muted);
        }

        .form-group input, .form-group select {
            padding: 12px 15px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid #334155;
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
        }

        .btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2563eb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.7);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.8em;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--text), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 1.1em;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(30, 41, 59, 0.7);
            border-radius: 16px;
            padding: 25px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card h3 {
            margin-bottom: 20px;
            color: var(--text);
            font-size: 1.3em;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
        }

        .chart-wrapper {
            height: 350px;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.85em;
        }

        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px;
        }

        th {
            background: rgba(15, 23, 42, 0.7);
            font-weight: 600;
            color: var(--accent);
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: rgba(15, 23, 42, 0.5);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 10px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .alert {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .info {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid var(--accent);
            color: var(--text);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #a7f3d0;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: var(--text-muted);
        }

        .export-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .export-btn:hover {
            background: #059669;
        }

        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .back-btn {
                position: relative;
                top: auto;
                right: auto;
                margin-top: 15px;
                width: 100%;
                justify-content: center;
            }
            
            .header-content {
                max-width: 100%;
            }
            
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .filtros {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            table {
                font-size: 0.7em;
            }
            
            th, td {
                padding: 6px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-chart-line"></i> INDICADORES 47 - EMBAQUIM COMPLETO</h1>
                <p>Dashboard com TODOS os dados disponíveis</p>
            </div>
            <!-- BOTÃO VOLTAR ADICIONADO AQUI -->
            <a href="javascript:history.back()" class="back-btn">
                <a href="dashboard.php"></a>
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert">
                <strong><i class="fas fa-exclamation-triangle"></i> Erro:</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="filtros">
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Data Início:</label>
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Data Fim:</label>
                <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-clock"></i> Período Rápido:</label>
                <select name="periodo">
                    <option value="">Selecionar...</option>
                    <option value="hoje" <?= $periodo == 'hoje' ? 'selected' : '' ?>>Hoje</option>
                    <option value="ontem" <?= $periodo == 'ontem' ? 'selected' : '' ?>>Ontem</option>
                    <option value="semana_atual" <?= $periodo == 'semana_atual' ? 'selected' : '' ?>>Esta Semana</option>
                    <option value="semana_anterior" <?= $periodo == 'semana_anterior' ? 'selected' : '' ?>>Semana Anterior</option>
                    <option value="mes_atual" <?= $periodo == 'mes_atual' ? 'selected' : '' ?>>Este Mês</option>
                    <option value="mes_anterior" <?= $periodo == 'mes_anterior' ? 'selected' : '' ?>>Mês Anterior</option>
                    <option value="trimestre_atual" <?= $periodo == 'trimestre_atual' ? 'selected' : '' ?>>Este Trimestre</option>
                    <option value="ano_atual" <?= $periodo == 'ano_atual' ? 'selected' : '' ?>>Este Ano</option>
                    <option value="ano_anterior" <?= $periodo == 'ano_anterior' ? 'selected' : '' ?>>Ano Anterior</option>
                    <option value="todos" <?= $periodo == 'todos' ? 'selected' : '' ?>>TODOS OS DADOS</option>
                </select>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-filter"></i> Aplicar Filtros
            </button>
        </form>

        <?php if ($conn && !isset($error_message)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($total_registros, 0, ',', '.') ?></div>
                    <div class="stat-label">Total de Registros</div>
                </div>
                
                <?php if ($coluna_data): ?>
                <div class="stat-card">
                    <div class="stat-number"><?= $coluna_data ?></div>
                    <div class="stat-label">Coluna de Data</div>
                </div>
                <?php endif; ?>
                
                <div class="stat-card">
                    <div class="stat-number"><?= count($colunas_originais) ?></div>
                    <div class="stat-label">Colunas na Tabela</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= count($dados_tabela) ?></div>
                    <div class="stat-label">Registros Carregados</div>
                </div>
            </div>

            <div class="success">
                <i class="fas fa-check-circle"></i> 
                <strong>Sucesso:</strong> Carregados <?= number_format(count($dados_tabela), 0, ',', '.') ?> registros de <?= number_format($total_registros, 0, ',', '.') ?> totais.
            </div>

            <div class="dashboard">
                <?php if (!empty($dados_graficos['categorias'])): ?>
                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> Distribuição por <?= $coluna_categoria ?? 'Categoria' ?></h3>
                    <div class="chart-wrapper">
                        <canvas id="chartCategorias"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($dados_graficos['vendas_mensais'])): ?>
                <div class="card">
                    <h3><i class="fas fa-chart-line"></i> Vendas Mensais (Últimos 12 Meses)</h3>
                    <div class="chart-wrapper">
                        <canvas id="chartVendasMensais"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($dados_graficos['vendas_anuais'])): ?>
                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> Vendas Anuais (Todos os Anos)</h3>
                    <div class="chart-wrapper">
                        <canvas id="chartVendasAnuais"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3><i class="fas fa-table"></i> Dados Completos da Tabela - EMBAQUIM</h3>
                
                <div class="info">
                    <i class="fas fa-database"></i> 
                    <strong>Estrutura da Tabela:</strong> <?= implode(', ', $colunas_originais) ?>
                </div>
                
                <?php if (!empty($dados_tabela)): ?>
                <button class="export-btn" onclick="exportarParaExcel()">
                    <i class="fas fa-file-excel"></i> Exportar para Excel
                </button>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($colunas_originais as $coluna): ?>
                                    <th><?= htmlspecialchars($coluna) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados_tabela as $linha): ?>
                            <tr>
                                <?php foreach ($colunas_originais as $coluna): ?>
                                    <td title="<?= htmlspecialchars($linha[$coluna]) ?>">
                                        <?= htmlspecialchars($linha[$coluna]) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="loading">
                    <i class="fas fa-database"></i><br>
                    Nenhum dado encontrado com os filtros aplicados.
                </div>
                <?php endif; ?>
            </div>

            <script>
                // Dados para os gráficos
                const chartData = {
                    categorias: <?= json_encode($dados_graficos['categorias']) ?>,
                    vendasMensais: <?= json_encode($dados_graficos['vendas_mensais']) ?>,
                    vendasAnuais: <?= json_encode($dados_graficos['vendas_anuais']) ?>
                };

                // Função para exportar para Excel
                function exportarParaExcel() {
                    const table = document.querySelector('table');
                    const html = table.outerHTML;
                    const url = 'data:application/vnd.ms-excel;charset=utf-8,' + encodeURIComponent(html);
                    const link = document.createElement('a');
                    link.download = 'dados_embaquim.xls';
                    link.href = url;
                    link.click();
                }

                // Inicializar gráficos
                document.addEventListener('DOMContentLoaded', function() {
                    
                    Chart.defaults.color = '#e2e8f0';
                    Chart.defaults.borderColor = '#334155';

                    // Gráfico de Categorias
                    if (chartData.categorias && Object.keys(chartData.categorias).length > 0) {
                        const ctx1 = document.getElementById('chartCategorias').getContext('2d');
                        new Chart(ctx1, {
                            type: 'bar',
                            data: {
                                labels: Object.keys(chartData.categorias),
                                datasets: [{
                                    label: 'Quantidade',
                                    data: Object.values(chartData.categorias),
                                    backgroundColor: '#3b82f6',
                                    borderColor: '#2563eb',
                                    borderWidth: 1
                                }]
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true, position: 'top' }
                                },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }

                    // Gráfico de Vendas Mensais
                    if (chartData.vendasMensais && Object.keys(chartData.vendasMensais).length > 0) {
                        const ctx2 = document.getElementById('chartVendasMensais').getContext('2d');
                        new Chart(ctx2, {
                            type: 'line',
                            data: {
                                labels: Object.keys(chartData.vendasMensais),
                                datasets: [{
                                    label: 'Vendas',
                                    data: Object.values(chartData.vendasMensais),
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 2
                                }]
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true, position: 'top' }
                                },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                                    x: { grid: { color: 'rgba(148, 163, 184, 0.1)' } }
                                }
                            }
                        });
                    }

                    // Gráfico de Vendas Anuais
                    if (chartData.vendasAnuais && Object.keys(chartData.vendasAnuais).length > 0) {
                        const ctx3 = document.getElementById('chartVendasAnuais').getContext('2d');
                        new Chart(ctx3, {
                            type: 'bar',
                            data: {
                                labels: Object.keys(chartData.vendasAnuais),
                                datasets: [{
                                    label: 'Vendas',
                                    data: Object.values(chartData.vendasAnuais),
                                    backgroundColor: '#f59e0b',
                                    borderColor: '#d97706',
                                    borderWidth: 1
                                }]
                            },
                            options: { 
                                responsive: true, 
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true, position: 'top' }
                                },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }
                });
            </script>

        <?php else: ?>
            <div class="alert">
                <i class="fas fa-exclamation-triangle"></i> Erro de conexão com o banco de dados
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
if ($conn) {
    sqlsrv_close($conn);
}
?>