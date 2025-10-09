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

// Processar filtros
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = $_POST['data_fim'] ?? '';
$status_filtro = $_POST['status_filtro'] ?? '';
$periodo = $_POST['periodo'] ?? '';
$mes_ano = $_POST['mes_ano'] ?? '';
$ano = $_POST['ano'] ?? '';
$trimestre = $_POST['trimestre'] ?? '';

// Inicializar variáveis
$total_pedidos = 0;
$atrasados = 0;
$total_clientes = 0;
$total_produtos = 0;
$vendas_ultimos_12_meses = 0; 

// Arrays para gráficos
$dados_graficos = [
    'status' => [],
    'top_clientes' => [],
    'top_produtos' => [],
    'dias_atraso' => [],
    'vendas_mensais' => [],
    'vendas_anuais' => []
];

// Arrays para dados temporais
$dados_temporais = [
    'vendas_mes_atual' => 0,
    'crescimento_mensal' => 0,
    'vendas_ano_atual' => 0,
    'melhor_mes' => '-',
    'vendas_ultimos_12_meses' => 0
];

// Função para obter vendas dos últimos 12 meses
function obterVendasUltimos12Meses($conn, $where_conditions = [], $query_params = []) {
    $where_conditions_12meses = [];
    $query_params_12meses = [];
    
    foreach ($where_conditions as $index => $condition) {
        if (strpos($condition, 'DtEmissao') === false && 
            strpos($condition, 'DATEPART') === false &&
            strpos($condition, 'CAST(DtEmissao') === false) {
            $where_conditions_12meses[] = $condition;
            if (isset($query_params[$index])) {
                $query_params_12meses[] = $query_params[$index];
            }
        }
    }
    
    $where_conditions_12meses[] = "DtEmissao >= DATEADD(MONTH, -12, GETDATE())";
    
    $where_sql_12meses = "";
    if (!empty($where_conditions_12meses)) {
        $where_sql_12meses = "WHERE " . implode(" AND ", $where_conditions_12meses);
    }
    
    $sql_12meses = "SELECT COUNT(*) as total FROM vW_Ind46 $where_sql_12meses";
    $stmt_12meses = sqlsrv_query($conn, $sql_12meses, $query_params_12meses);
    
    if ($stmt_12meses !== false) {
        $row = sqlsrv_fetch_array($stmt_12meses, SQLSRV_FETCH_ASSOC);
        return $row ? $row['total'] : 0;
    }
    
    return 0;
}

if ($conn) {
    try {
        // CONSTRUÇÃO DINÂMICA DO WHERE
        $where_conditions = [];
        $query_params = [];

        // 1. Filtro de data de início/fim manual
        if (!empty($data_inicio)) {
            $where_conditions[] = "CAST(DtEmissao AS DATE) >= ?";
            $query_params[] = $data_inicio;
        }

        if (!empty($data_fim)) {
            $where_conditions[] = "CAST(DtEmissao AS DATE) <= ?";
            $query_params[] = $data_fim;
        }

        // 2. Filtro por período rápido
        if (!empty($periodo) && empty($data_inicio) && empty($data_fim)) {
            switch ($periodo) {
                case 'hoje':
                    $where_conditions[] = "CAST(DtEmissao AS DATE) = CAST(GETDATE() AS DATE)";
                    break;
                case 'ontem':
                    $where_conditions[] = "CAST(DtEmissao AS DATE) = CAST(DATEADD(DAY, -1, GETDATE()) AS DATE)";
                    break;
                case 'semana_atual':
                    $where_conditions[] = "DtEmissao >= DATEADD(wk, DATEDIFF(wk, 0, GETDATE()), 0) AND DtEmissao < DATEADD(wk, DATEDIFF(wk, 0, GETDATE()) + 1, 0)";
                    break;
                case 'semana_anterior':
                    $where_conditions[] = "DtEmissao >= DATEADD(wk, DATEDIFF(wk, 0, GETDATE()) - 1, 0) AND DtEmissao < DATEADD(wk, DATEDIFF(wk, 0, GETDATE()), 0)";
                    break;
                case 'mes_atual':
                    $where_conditions[] = "DATEPART(MONTH, DtEmissao) = DATEPART(MONTH, GETDATE()) AND DATEPART(YEAR, DtEmissao) = DATEPART(YEAR, GETDATE())";
                    break;
                case 'mes_anterior':
                    $where_conditions[] = "DATEPART(MONTH, DtEmissao) = DATEPART(MONTH, DATEADD(MONTH, -1, GETDATE())) AND DATEPART(YEAR, DtEmissao) = DATEPART(YEAR, DATEADD(MONTH, -1, GETDATE()))";
                    break;
                case 'trimestre_atual':
                    $where_conditions[] = "DATEPART(QUARTER, DtEmissao) = DATEPART(QUARTER, GETDATE()) AND DATEPART(YEAR, DtEmissao) = DATEPART(YEAR, GETDATE())";
                    break;
                case 'ano_atual':
                    $where_conditions[] = "DATEPART(YEAR, DtEmissao) = DATEPART(YEAR, GETDATE())";
                    break;
                case 'ano_anterior':
                    $where_conditions[] = "DATEPART(YEAR, DtEmissao) = DATEPART(YEAR, GETDATE()) - 1";
                    break;
            }
        }

        // 3. Filtro por mês/ano específico
        if (!empty($mes_ano) && empty($periodo) && empty($data_inicio) && empty($data_fim)) {
            list($ano_filtro, $mes_filtro) = explode('-', $mes_ano);
            $where_conditions[] = "DATEPART(YEAR, DtEmissao) = ? AND DATEPART(MONTH, DtEmissao) = ?";
            $query_params[] = $ano_filtro;
            $query_params[] = $mes_filtro;
        }

        // 4. Filtro por ano específico
        if (!empty($ano) && empty($periodo) && empty($mes_ano) && empty($data_inicio) && empty($data_fim)) {
            $where_conditions[] = "DATEPART(YEAR, DtEmissao) = ?";
            $query_params[] = $ano;
        }
        
        // 5. Filtro de status
        if (!empty($status_filtro)) {
            $status_map = [
                'ATRASADO' => 20,
                'ENTREGUE' => 30, 
                'NO PRAZO' => 10
            ];
            
            if (isset($status_map[$status_filtro])) {
                $where_conditions[] = "CodStatusPedido = ?";
                $query_params[] = $status_map[$status_filtro];
            }
        }

        // Combinação final do WHERE
        $where_sql = "";
        if (!empty($where_conditions)) {
            $where_sql = "WHERE " . implode(" AND ", $where_conditions);
        }

        // Total de Pedidos
        $sql_total = "SELECT COUNT(*) as total FROM vW_Ind46 $where_sql";
        $stmt_total = sqlsrv_query($conn, $sql_total, $query_params);
        if ($stmt_total !== false) {
            $row_total = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC);
            $total_pedidos = $row_total ? $row_total['total'] : 0;
        }

        // Pedidos Atrasados
        $atrasados_conditions = [];
        $atrasados_params = [];
        $param_index = 0;
        
        foreach ($where_conditions as $condition) {
            if (strpos($condition, 'CodStatusPedido = ?') === false) {
                $atrasados_conditions[] = $condition;
                if (isset($query_params[$param_index])) {
                    $atrasados_params[] = $query_params[$param_index];
                }
            }
            $param_index++;
        }

        $atrasados_where = "WHERE CodStatusPedido = 20";
        if (!empty($atrasados_conditions)) {
            $atrasados_where .= " AND " . implode(" AND ", $atrasados_conditions);
        }
        
        if ($status_filtro === 'ATRASADO') {
            $atrasados = $total_pedidos;
        } else {
            $sql_atrasados = "SELECT COUNT(*) as atrasados FROM vW_Ind46 $atrasados_where";
            $stmt_atrasados = sqlsrv_query($conn, $sql_atrasados, $atrasados_params);
            if ($stmt_atrasados !== false) {
                $row_atrasados = sqlsrv_fetch_array($stmt_atrasados, SQLSRV_FETCH_ASSOC);
                $atrasados = $row_atrasados ? $row_atrasados['atrasados'] : 0;
            }
        }
        
        // Total de Clientes e Produtos
        $sql_clientes = "SELECT COUNT(DISTINCT Cliente) as clientes FROM vW_Ind46 $where_sql";
        $stmt_clientes = sqlsrv_query($conn, $sql_clientes, $query_params);
        if ($stmt_clientes !== false) {
            $row_clientes = sqlsrv_fetch_array($stmt_clientes, SQLSRV_FETCH_ASSOC);
            $total_clientes = $row_clientes ? $row_clientes['clientes'] : 0;
        }

        $sql_produtos = "SELECT COUNT(DISTINCT Produto) as produtos FROM vW_Ind46 $where_sql";
        $stmt_produtos = sqlsrv_query($conn, $sql_produtos, $query_params);
        if ($stmt_produtos !== false) {
            $row_produtos = sqlsrv_fetch_array($stmt_produtos, SQLSRV_FETCH_ASSOC);
            $total_produtos = $row_produtos ? $row_produtos['produtos'] : 0;
        }

        // Obter vendas dos últimos 12 meses
        $vendas_ultimos_12_meses = obterVendasUltimos12Meses($conn, $where_conditions, $query_params);
        $dados_temporais['vendas_ultimos_12_meses'] = $vendas_ultimos_12_meses;

        // Dados para gráficos - CORRIGIDO: garantir arrays vazios quando não há dados
        $sql_status = "SELECT 
                        CASE 
                            WHEN CodStatusPedido = 20 THEN 'ATRASADO'
                            WHEN CodStatusPedido = 10 THEN 'NO PRAZO' 
                            WHEN CodStatusPedido = 30 THEN 'ENTREGUE'
                            ELSE 'OUTROS'
                        END as StatusEntrega, 
                        COUNT(*) as total 
                      FROM vW_Ind46 $where_sql 
                      GROUP BY CodStatusPedido";
        $stmt_status = sqlsrv_query($conn, $sql_status, $query_params);
        if ($stmt_status !== false) {
            while ($row = sqlsrv_fetch_array($stmt_status, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['status'][$row['StatusEntrega']] = (int)$row['total'];
            }
        }

        $sql_top_clientes = "SELECT TOP 10 Cliente, COUNT(*) as total_pedidos FROM vW_Ind46 $where_sql GROUP BY Cliente ORDER BY total_pedidos DESC";
        $stmt_clientes_top = sqlsrv_query($conn, $sql_top_clientes, $query_params);
        if ($stmt_clientes_top !== false) {
            while ($row = sqlsrv_fetch_array($stmt_clientes_top, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['top_clientes'][$row['Cliente']] = (int)$row['total_pedidos'];
            }
        }

        $sql_top_produtos = "SELECT TOP 10 Produto, COUNT(*) as total_pedidos FROM vW_Ind46 $where_sql GROUP BY Produto ORDER BY total_pedidos DESC";
        $stmt_produtos_top = sqlsrv_query($conn, $sql_top_produtos, $query_params);
        if ($stmt_produtos_top !== false) {
            while ($row = sqlsrv_fetch_array($stmt_produtos_top, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['top_produtos'][$row['Produto']] = (int)$row['total_pedidos'];
            }
        }

        // Distribuição de dias de atraso
        $sql_dias = "SELECT 
                        CASE 
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) <= 0 THEN 'No Prazo'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) BETWEEN 1 AND 7 THEN '1-7 Dias'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) BETWEEN 8 AND 15 THEN '8-15 Dias'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) BETWEEN 16 AND 30 THEN '16-30 Dias'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) > 30 THEN 'Mais de 30 Dias'
                            ELSE 'Sem Data'
                        END as faixa_dias,
                        COUNT(*) as total
                    FROM vW_Ind46 
                    $atrasados_where
                    GROUP BY 
                        CASE 
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) <= 0 THEN 'No Prazo'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) BETWEEN 1 AND 7 THEN '1-7 Dias'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) BETWEEN 8 AND 15 THEN '8-15 Dias'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) BETWEEN 16 AND 30 THEN '16-30 Dias'
                            WHEN DATEDIFF(DAY, DtEmissao, DtEntregaVendas) > 30 THEN 'Mais de 30 Dias'
                            ELSE 'Sem Data'
                        END";
        
        if (empty($status_filtro) || $status_filtro === 'ATRASADO') {
            $stmt_dias = sqlsrv_query($conn, $sql_dias, $atrasados_params);
            if ($stmt_dias !== false) {
                while ($row = sqlsrv_fetch_array($stmt_dias, SQLSRV_FETCH_ASSOC)) {
                    $dados_graficos['dias_atraso'][$row['faixa_dias']] = (int)$row['total'];
                }
            }
        }
        
        // Vendas Mensais (últimos 12 meses)
        $sql_vendas_mensais = "
        SELECT 
            YEAR(DtEmissao) as Ano,
            MONTH(DtEmissao) as Mes,
            COUNT(*) as TotalPedidos
        FROM vW_Ind46 
        WHERE DtEmissao >= DATEADD(MONTH, -12, GETDATE())
        GROUP BY YEAR(DtEmissao), MONTH(DtEmissao)
        ORDER BY Ano ASC, Mes ASC
        ";

        $stmt_mensais = sqlsrv_query($conn, $sql_vendas_mensais);
        if ($stmt_mensais !== false) {
            $dados_graficos['vendas_mensais'] = [];
            while ($row = sqlsrv_fetch_array($stmt_mensais, SQLSRV_FETCH_ASSOC)) {
                $mes_formatado = $row['Ano'] . '-' . str_pad($row['Mes'], 2, '0', STR_PAD_LEFT);
                $dados_graficos['vendas_mensais'][$mes_formatado] = (int)$row['TotalPedidos'];
            }
        }

        // Vendas Anuais (últimos 5 anos)
        $sql_vendas_anuais = "
        SELECT 
            YEAR(DtEmissao) as Ano,
            COUNT(*) as TotalPedidos
        FROM vW_Ind46 
        WHERE YEAR(DtEmissao) BETWEEN YEAR(GETDATE()) - 4 AND YEAR(GETDATE())
        GROUP BY YEAR(DtEmissao)
        ORDER BY Ano ASC
        ";
        
        $stmt_anuais = sqlsrv_query($conn, $sql_vendas_anuais);
        if ($stmt_anuais !== false) {
            while ($row = sqlsrv_fetch_array($stmt_anuais, SQLSRV_FETCH_ASSOC)) {
                $dados_graficos['vendas_anuais'][$row['Ano']] = (int)$row['TotalPedidos'];
            }
        }

        // Dados temporais para os cards
        $sql_mes_atual = "SELECT COUNT(*) as total FROM vW_Ind46 WHERE MONTH(DtEmissao) = MONTH(GETDATE()) AND YEAR(DtEmissao) = YEAR(GETDATE())";
        $stmt_mes_atual = sqlsrv_query($conn, $sql_mes_atual);
        if ($stmt_mes_atual !== false) {
            $row = sqlsrv_fetch_array($stmt_mes_atual, SQLSRV_FETCH_ASSOC);
            $dados_temporais['vendas_mes_atual'] = $row ? $row['total'] : 0;
        }

        $sql_ano_atual = "SELECT COUNT(*) as total FROM vW_Ind46 WHERE YEAR(DtEmissao) = YEAR(GETDATE())";
        $stmt_ano_atual = sqlsrv_query($conn, $sql_ano_atual);
        if ($stmt_ano_atual !== false) {
            $row = sqlsrv_fetch_array($stmt_ano_atual, SQLSRV_FETCH_ASSOC);
            $dados_temporais['vendas_ano_atual'] = $row ? $row['total'] : 0;
        }

        $sql_melhor_mes = "
        SELECT TOP 1 
            YEAR(DtEmissao) as Ano,
            MONTH(DtEmissao) as Mes,
            COUNT(*) as Total
        FROM vW_Ind46 
        WHERE DtEmissao >= DATEADD(YEAR, -1, GETDATE())
        GROUP BY YEAR(DtEmissao), MONTH(DtEmissao)
        ORDER BY Total DESC
        ";
        $stmt_melhor_mes = sqlsrv_query($conn, $sql_melhor_mes);
        if ($stmt_melhor_mes !== false) {
            $row = sqlsrv_fetch_array($stmt_melhor_mes, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $dados_temporais['melhor_mes'] = str_pad($row['Mes'], 2, '0', STR_PAD_LEFT) . '/' . $row['Ano'];
            }
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INDICADORES 46 - EMBAQUIM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #3b82f6;
            --accent-glow: rgba(59, 130, 246, 0.5);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #334155;
            --card-bg: rgba(30, 41, 59, 0.7);
            --glass: rgba(255, 255, 255, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #1e1b4b 100%);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            cursor: default;
        }

        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.3;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: var(--accent-glow);
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .header {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--success), var(--warning), var(--danger));
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            font-size: 2.5em;
            color: var(--accent);
            filter: drop-shadow(0 0 10px var(--accent-glow));
        }

        .header-text h1 {
            font-size: 2.5em;
            font-weight: 700;
            background: linear-gradient(90deg, var(--text), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .header-text p {
            color: var(--text-muted);
            font-size: 1.1em;
        }

        .filtros {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
            transition: var(--transition);
        }

        .filtros:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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
            color: var(--text);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input, .form-group select {
            padding: 12px 15px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text);
            transition: var(--transition);
            cursor: pointer;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .btn {
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, #475569 100%);
            box-shadow: 0 4px 15px rgba(71, 85, 105, 0.3);
        }

        .btn-secondary:hover {
            box-shadow: 0 6px 20px rgba(71, 85, 105, 0.5);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--success));
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            opacity: 0.8;
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
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .card h3 {
            margin-bottom: 20px;
            color: var(--text);
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        /* ESTILOS OTIMIZADOS PARA GRÁFICOS */
        .chart-wrapper {
            position: relative;
            height: 350px;
            width: 100%;
            margin: 0 auto;
        }

        .chart-container {
            position: relative;
            height: 100%;
            width: 100%;
        }

        .chart-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            text-align: center;
            padding: 20px;
        }

        .chart-placeholder i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .chart-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 15px;
        }

        .chart-btn {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8em;
            transition: var(--transition);
        }

        .chart-btn:hover {
            background: var(--accent);
            border-color: var(--accent);
        }

        .tabela-section {
            margin-top: 40px;
        }

        .tabela-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass);
            border-radius: 16px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .tabela-header {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }

        .tabela-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tabela-content {
            padding: 0;
            max-height: 600px;
            overflow: auto;
            cursor: default;
        }

        /* REMOVER CURSOR DA TABELA */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            cursor: default;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            cursor: default;
        }

        th {
            background: rgba(15, 23, 42, 0.7);
            font-weight: 600;
            color: var(--text);
            position: sticky;
            top: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85em;
            cursor: default;
        }

        tr {
            transition: var(--transition);
            cursor: default;
        }

        tr:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .status-atrasado {
            color: var(--danger);
            font-weight: bold;
            position: relative;
            padding-left: 15px;
        }

        .status-atrasado::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--danger);
        }

        .status-prazo {
            color: var(--success);
            font-weight: bold;
            position: relative;
            padding-left: 15px;
        }

        .status-prazo::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: var(--text-muted);
        }

        .error {
            text-align: center;
            padding: 20px;
            color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
            border-radius: 10px;
            margin: 10px 0;
            border-left: 4px solid var(--danger);
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .info-total {
            background: rgba(59, 130, 246, 0.1);
            padding: 15px;
            text-align: center;
            color: var(--accent);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            cursor: default;
        }

        .search-container {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            transition: var(--transition);
            cursor: text;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent-glow);
        }

        @media (max-width: 1200px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .filtros {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
            }
            
            table {
                font-size: 10px;
            }
            
            th, td {
                padding: 8px 10px;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .chart-wrapper {
                height: 300px;
            }
        }

        /* Animações de entrada otimizadas */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .delay-1 { animation-delay: 0.1s; opacity: 0; }
        .delay-2 { animation-delay: 0.2s; opacity: 0; }
        .delay-3 { animation-delay: 0.3s; opacity: 0; }
        .delay-4 { animation-delay: 0.4s; opacity: 0; }
        
        .periodo-active {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 2px var(--accent-glow) !important;
        }

        /* Animações otimizadas para gráficos */
        @keyframes chartFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chart-fade-in {
            animation: chartFadeIn 0.5s ease-out;
        }

        /* Otimização de performance para gráficos */
        canvas {
            display: block;
            will-change: transform;
        }
    </style>
</head>
<body>
    <div class="bg-animation" id="bgAnimation"></div>

    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-chart-network"></i>
                    </div>
                    <div class="header-text">
                        <h1>INDICADORES 46 - EMBAQUIM</h1>
                        <p>EMBAQUIM - Análise de Performance Completa</p>
                    </div>
                </div>
                <div class="system-status">
                    <div class="btn">
                        <a href="dashboard.php" style="color: white; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <form method="POST" class="filtros fade-in">
            <div class="form-group">
                <label for="data_inicio"><i class="fas fa-calendar-alt"></i> Data Início:</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
            </div>
            
            <div class="form-group">
                <label for="data_fim"><i class="fas fa-calendar-check"></i> Data Fim:</label>
                <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            
            <div class="form-group">
                <label for="periodo"><i class="fas fa-calendar-week"></i> Período Rápido:</label>
                <select id="periodo" name="periodo">
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
                </select>
            </div>

            <div class="form-group">
                <label for="mes_ano"><i class="fas fa-calendar-month"></i> Mês/Ano:</label>
                <select id="mes_ano" name="mes_ano">
                    <option value="">Todos os meses</option>
                    <?php
                    for ($i = 0; $i < 24; $i++) {
                        $mes_ano_value = date('Y-m', strtotime("-$i months"));
                        $mes_nome = date('m/Y', strtotime($mes_ano_value));
                        $selected = $mes_ano_value == $mes_ano ? 'selected' : '';
                        echo "<option value='$mes_ano_value' $selected>$mes_nome</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="ano"><i class="fas fa-calendar-star"></i> Ano:</label>
                <select id="ano" name="ano">
                    <option value="">Todos os anos</option>
                    <?php
                    for ($i = 0; $i < 5; $i++) {
                        $ano_opcao = date('Y') - $i;
                        $selected = $ano_opcao == $ano ? 'selected' : '';
                        echo "<option value='$ano_opcao' $selected>$ano_opcao</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status_filtro"><i class="fas fa-filter"></i> Status:</label>
                <select id="status_filtro" name="status_filtro">
                    <option value="">Todos</option>
                    <option value="ATRASADO" <?= $status_filtro == 'ATRASADO' ? 'selected' : '' ?>>Atrasado</option>
                    <option value="ENTREGUE" <?= $status_filtro == 'ENTREGUE' ? 'selected' : '' ?>>Entregue</option>
                    <option value="NO PRAZO" <?= $status_filtro == 'NO PRAZO' ? 'selected' : '' ?>>No Prazo</option>
                </select>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-sliders-h"></i> Aplicar Filtros</button>
            <a href="?" class="btn btn-secondary"><i class="fas fa-broom"></i> Limpar Filtros</a>
        </form>

        <?php if ($conn): ?>
            <?php if (isset($error_message)): ?>
                <div class='error fade-in'><i class='fas fa-exclamation-triangle'></i> Erro: <?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card fade-in delay-1">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-number"><?= number_format($total_pedidos, 0, ',', '.') ?></div>
                    <div class="stat-label">Total de Pedidos</div>
                </div>

                <div class="stat-card fade-in delay-2">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= number_format($atrasados, 0, ',', '.') ?></div>
                    <div class="stat-label">Pedidos Atrasados</div>
                </div>

                <div class="stat-card fade-in delay-3">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-number"><?= number_format($dados_temporais['vendas_mes_atual'], 0, ',', '.') ?></div>
                    <div class="stat-label">Vendas Este Mês</div>
                </div>

                <div class="stat-card fade-in delay-4">
                    <div class="stat-icon"><i class="fas fa-calendar-star"></i></div>
                    <div class="stat-number"><?= number_format($dados_temporais['vendas_ano_atual'], 0, ',', '.') ?></div>
                    <div class="stat-label">Vendas Este Ano</div>
                </div>

                <div class="stat-card fade-in delay-1">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-number"><?= number_format($vendas_ultimos_12_meses, 0, ',', '.') ?></div>
                    <div class="stat-label">Vendas Últimos 12 Meses</div>
                </div>

                <div class="stat-card fade-in delay-2">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?= number_format($total_clientes, 0, ',', '.') ?></div>
                    <div class="stat-label">Clientes Únicos</div>
                </div>

                <div class="stat-card fade-in delay-3">
                    <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                    <div class="stat-number"><?= number_format($total_produtos, 0, ',', '.') ?></div>
                    <div class="stat-label">Produtos Diferentes</div>
                </div>

                <div class="stat-card fade-in delay-4">
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                    <div class="stat-number"><?= htmlspecialchars($dados_temporais['melhor_mes']) ?></div>
                    <div class="stat-label">Melhor Mês</div>
                </div>
            </div>

            <div class="dashboard">
                <!-- Gráfico 1: Status dos Pedidos -->
                <div class="card fade-in delay-1">
                    <div class="chart-toolbar">
                        <button class="chart-btn" onclick="downloadChart('chartStatus')">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <h3><i class="fas fa-chart-pie"></i> Status dos Pedidos</h3>
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <canvas id="chartStatus"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 2: Top 10 Clientes -->
                <div class="card fade-in delay-2">
                    <div class="chart-toolbar">
                        <button class="chart-btn" onclick="downloadChart('chartClientes')">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <h3><i class="fas fa-user-chart"></i> Top 10 Clientes</h3>
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <canvas id="chartClientes"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 3: Top 10 Produtos -->
                <div class="card fade-in delay-3">
                    <div class="chart-toolbar">
                        <button class="chart-btn" onclick="downloadChart('chartProdutos')">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <h3><i class="fas fa-chart-bar"></i> Top 10 Produtos</h3>
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <canvas id="chartProdutos"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 4: Distribuição de Atrasos -->
                <div class="card fade-in delay-4">
                    <div class="chart-toolbar">
                        <button class="chart-btn" onclick="downloadChart('chartAtrasos')">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <h3><i class="fas fa-hourglass-half"></i> Distribuição de Atrasos</h3>
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <canvas id="chartAtrasos"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 5: Vendas Mensais -->
                <div class="card fade-in delay-1">
                    <div class="chart-toolbar">
                        <button class="chart-btn" onclick="downloadChart('chartVendasMensais')">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <h3><i class="fas fa-chart-line"></i> Vendas Mensais (Últimos 12 Meses)</h3>
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <canvas id="chartVendasMensais"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 6: Vendas Anuais -->
                <div class="card fade-in delay-2">
                    <div class="chart-toolbar">
                        <button class="chart-btn" onclick="downloadChart('chartVendasAnuais')">
                            <i class="fas fa-download"></i> PNG
                        </button>
                    </div>
                    <h3><i class="fas fa-chart-bar"></i> Vendas Anuais (Últimos 5 Anos)</h3>
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <canvas id="chartVendasAnuais"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tabela-section">
                <div class="tabela-card fade-in">
                    <div class="tabela-header">
                        <h3><i class="fas fa-table"></i> EMBAQUIM - Todos os Pedidos (<?= number_format($total_pedidos, 0, ',', '.') ?> registros)</h3>
                        <div class="export-buttons">
                            <button class="btn" onclick="exportarParaExcel()"><i class="fas fa-file-excel"></i> Exportar Excel</button>
                        </div>
                    </div>
                    
                    <div class="search-container">
                        <input type="text" class="search-input" id="searchInput" placeholder="Buscar em todos os campos...">
                        <button class="btn" onclick="clearSearch()"><i class="fas fa-times"></i> Limpar</button>
                    </div>
                    
                    <div class="tabela-content">
                        <?php
                        try {
                            $sql46 = "SELECT * FROM vW_Ind46 $where_sql ORDER BY CodPedido DESC";
                            $stmt46 = sqlsrv_query($conn, $sql46, $query_params);
                            
                            if ($stmt46 === false) {
                                throw new Exception("Erro na consulta principal: " . print_r(sqlsrv_errors(), true));
                            }
                            
                            if ($stmt46):
                        ?>
                        <div class="info-total">
                            <i class="fas fa-chart-line"></i> Exibindo todos os <?= number_format($total_pedidos, 0, ',', '.') ?> registros
                        </div>
                        <table id="tabela46">
                            <thead>
                                <tr>
                                    <th>CodPedido</th>
                                    <th>Pedido</th>
                                    <th>Item</th>
                                    <th>Produto</th>
                                    <th>Cliente</th>
                                    <th>DtEmissao</th>
                                    <th>DtEntregaVendas</th>
                                    <th>DtFCP</th>
                                    <th>DtLiberacaoPCP</th>
                                    <th>Qtidade</th>
                                    <th>Qt pend</th>
                                    <th>CodStatusPedido</th>
                                    <th>CodEmpresa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = sqlsrv_fetch_array($stmt46, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['CodPedido'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['Pedido'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['Item'] ?? '') ?></td>
                                    <td title="<?= htmlspecialchars($row['Produto'] ?? '') ?>">
                                        <?= isset($row['Produto']) ? (strlen($row['Produto']) > 20 ? substr($row['Produto'], 0, 20) . '...' : $row['Produto']) : '' ?>
                                    </td>
                                    <td title="<?= htmlspecialchars($row['Cliente'] ?? '') ?>">
                                        <?= isset($row['Cliente']) ? (strlen($row['Cliente']) > 25 ? substr($row['Cliente'], 0, 25) . '...' : $row['Cliente']) : '' ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['DtEmissao'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['DtEntregaVendas'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['DtFCP'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['DtLiberacaoPCP'] ?? 'NULL') ?></td>
                                    <td><?= htmlspecialchars($row['Qtidade'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['Qt pend'] ?? '') ?></td>
                                    <td class="<?= ($row['CodStatusPedido'] ?? '') == 20 ? 'status-atrasado' : 'status-prazo' ?>">
                                        <?= 
                                            ($row['CodStatusPedido'] ?? '') == 20 ? 'ATRASADO' : 
                                            (($row['CodStatusPedido'] ?? '') == 10 ? 'NO PRAZO' : 
                                            (($row['CodStatusPedido'] ?? '') == 30 ? 'ENTREGUE' : 'OUTROS'))
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['CodEmpresa'] ?? '') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php 
                            else: 
                                throw new Exception("Falha na execução da consulta.");
                            endif;
                            
                        } catch (Exception $e) {
                            echo "<div class='error fade-in'><i class='fas fa-exclamation-circle'></i> Erro ao carregar dados: " . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <script>
                // Background Animation
                function createParticles() {
                    const container = document.getElementById('bgAnimation');
                    const particleCount = 20;
                    
                    for (let i = 0; i < particleCount; i++) {
                        const particle = document.createElement('div');
                        particle.classList.add('particle');
                        
                        const size = Math.random() * 4 + 1;
                        const left = Math.random() * 100;
                        const delay = Math.random() * 10;
                        
                        particle.style.width = `${size}px`;
                        particle.style.height = `${size}px`;
                        particle.style.left = `${left}%`;
                        particle.style.animationDelay = `${delay}s`;
                        
                        container.appendChild(particle);
                    }
                }

                // Função para download de gráficos
                function downloadChart(chartId) {
                    const chart = Chart.getChart(chartId);
                    if (chart) {
                        const link = document.createElement('a');
                        link.download = `${chartId}.png`;
                        link.href = chart.toBase64Image();
                        link.click();
                    }
                }

                // Função para verificar dados vazios - CORRIGIDA
                function hasValidData(data) {
                    if (!data || data.length === 0) return false;
                    
                    // Se for array, verifica se tem algum valor > 0
                    if (Array.isArray(data)) {
                        return data.some(value => value > 0);
                    }
                    
                    // Se for objeto, verifica os valores
                    if (typeof data === 'object') {
                        return Object.values(data).some(value => value > 0);
                    }
                    
                    return false;
                }

                // Função para criar placeholder quando não há dados
                function createNoDataPlaceholder(canvasId) {
                    const canvas = document.getElementById(canvasId);
                    const container = canvas.parentElement;
                    
                    // Verificar se já existe um placeholder
                    if (container.querySelector('.chart-placeholder')) {
                        return;
                    }
                    
                    const placeholder = document.createElement('div');
                    placeholder.className = 'chart-placeholder';
                    placeholder.innerHTML = `
                        <i class="fas fa-chart-bar"></i>
                        <p>Nenhum dado disponível</p>
                        <small>Para o período selecionado</small>
                    `;
                    
                    container.appendChild(placeholder);
                    canvas.style.display = 'none';
                }

                // Configurações globais do Chart.js
                Chart.defaults.color = '#e2e8f0';
                Chart.defaults.font.family = "'Segoe UI', system-ui, -apple-system, sans-serif";
                Chart.defaults.font.size = 11;
                Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
                Chart.defaults.plugins.tooltip.borderColor = 'rgba(59, 130, 246, 0.5)';
                Chart.defaults.plugins.tooltip.borderWidth = 1;
                Chart.defaults.plugins.legend.labels.usePointStyle = true;
                
                // Configurações de performance
                Chart.defaults.animation.duration = 800;
                Chart.defaults.interaction.mode = 'nearest';
                Chart.defaults.interaction.intersect = false;

                // Preparar dados para JavaScript - CORRIGIDO
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
                    atrasos: {
                        labels: <?= json_encode(array_keys($dados_graficos['dias_atraso'] ?? [])) ?>,
                        data: <?= json_encode(array_values($dados_graficos['dias_atraso'] ?? [])) ?>
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

                // DEBUG: Verificar dados no console
                console.log('Dados dos gráficos:', chartData);

                // Paleta de cores
                const colorPalette = {
                    primary: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#64748b'],
                    sequential: ['#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe'],
                    qualitative: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316']
                };

                // Array para armazenar instâncias dos gráficos
                const chartInstances = {};

                // Inicializar gráficos após o DOM carregar
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM Carregado - Iniciando gráficos...');
                    createParticles();
                    initializeCharts();
                    
                    // Destacar filtros ativos
                    const periodoSelect = document.getElementById('periodo');
                    const mesAnoSelect = document.getElementById('mes_ano');
                    const anoSelect = document.getElementById('ano');

                    if (periodoSelect.value) periodoSelect.classList.add('periodo-active');
                    if (mesAnoSelect.value) mesAnoSelect.classList.add('periodo-active');
                    if (anoSelect.value) anoSelect.classList.add('periodo-active');

                    periodoSelect.addEventListener('change', function() {
                        this.classList.toggle('periodo-active', !!this.value);
                    });

                    mesAnoSelect.addEventListener('change', function() {
                         this.classList.toggle('periodo-active', !!this.value);
                    });

                    anoSelect.addEventListener('change', function() {
                         this.classList.toggle('periodo-active', !!this.value);
                    });

                    // Busca em tempo real
                    const searchInput = document.getElementById('searchInput');
                    searchInput.addEventListener('input', function() {
                        const valor = this.value.toLowerCase();
                        const linhas = document.querySelectorAll('#tabela46 tbody tr');
                        
                        linhas.forEach(function(linha) {
                            const texto = linha.textContent.toLowerCase();
                            linha.style.display = texto.includes(valor) ? '' : 'none';
                        });
                    });
                });

                function initializeCharts() {
                    console.log('Inicializando gráficos...');
                    
                    // 1. Gráfico de Status dos Pedidos
                    if (hasValidData(chartData.status.data)) {
                        console.log('Criando gráfico de Status:', chartData.status);
                        try {
                            chartInstances.chartStatus = new Chart(document.getElementById('chartStatus'), {
                                type: 'doughnut',
                                data: {
                                    labels: chartData.status.labels,
                                    datasets: [{
                                        data: chartData.status.data,
                                        backgroundColor: colorPalette.primary,
                                        borderWidth: 2,
                                        borderColor: 'rgba(30, 41, 59, 0.8)'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    cutout: '60%',
                                    plugins: {
                                        legend: {
                                            position: 'right',
                                            labels: {
                                                padding: 15,
                                                usePointStyle: true,
                                                pointStyle: 'circle'
                                            }
                                        }
                                    }
                                }
                            });
                            document.getElementById('chartStatus').classList.add('chart-fade-in');
                        } catch (error) {
                            console.error('Erro ao criar gráfico de Status:', error);
                            createNoDataPlaceholder('chartStatus');
                        }
                    } else {
                        console.log('Sem dados para gráfico de Status');
                        createNoDataPlaceholder('chartStatus');
                    }

                    // 2. Gráfico de Top Clientes
                    if (hasValidData(chartData.clientes.data)) {
                        console.log('Criando gráfico de Clientes:', chartData.clientes);
                        try {
                            chartInstances.chartClientes = new Chart(document.getElementById('chartClientes'), {
                                type: 'bar',
                                data: {
                                    labels: chartData.clientes.labels.map(label => 
                                        label.length > 25 ? label.substring(0, 25) + '...' : label
                                    ),
                                    datasets: [{
                                        label: 'Nº de Pedidos',
                                        data: chartData.clientes.data,
                                        backgroundColor: colorPalette.sequential[0],
                                        borderColor: colorPalette.sequential[1],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    indexAxis: 'y',
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        x: {
                                            beginAtZero: true,
                                            grid: { color: 'rgba(100, 116, 139, 0.2)' }
                                        },
                                        y: {
                                            grid: { color: 'rgba(100, 116, 139, 0.1)' }
                                        }
                                    }
                                }
                            });
                            document.getElementById('chartClientes').classList.add('chart-fade-in');
                        } catch (error) {
                            console.error('Erro ao criar gráfico de Clientes:', error);
                            createNoDataPlaceholder('chartClientes');
                        }
                    } else {
                        console.log('Sem dados para gráfico de Clientes');
                        createNoDataPlaceholder('chartClientes');
                    }

                    // 3. Gráfico de Top Produtos
                    if (hasValidData(chartData.produtos.data)) {
                        console.log('Criando gráfico de Produtos:', chartData.produtos);
                        try {
                            chartInstances.chartProdutos = new Chart(document.getElementById('chartProdutos'), {
                                type: 'bar',
                                data: {
                                    labels: chartData.produtos.labels.map(label => 
                                        label.length > 20 ? label.substring(0, 20) + '...' : label
                                    ),
                                    datasets: [{
                                        label: 'Nº de Pedidos',
                                        data: chartData.produtos.data,
                                        backgroundColor: colorPalette.qualitative,
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { color: 'rgba(100, 116, 139, 0.2)' }
                                        },
                                        x: {
                                            grid: { color: 'rgba(100, 116, 139, 0.1)' },
                                            ticks: { 
                                                maxRotation: 45,
                                                minRotation: 45
                                            }
                                        }
                                    }
                                }
                            });
                            document.getElementById('chartProdutos').classList.add('chart-fade-in');
                        } catch (error) {
                            console.error('Erro ao criar gráfico de Produtos:', error);
                            createNoDataPlaceholder('chartProdutos');
                        }
                    } else {
                        console.log('Sem dados para gráfico de Produtos');
                        createNoDataPlaceholder('chartProdutos');
                    }

                    // 4. Gráfico de Distribuição de Atrasos
                    if (hasValidData(chartData.atrasos.data)) {
                        console.log('Criando gráfico de Atrasos:', chartData.atrasos);
                        try {
                            chartInstances.chartAtrasos = new Chart(document.getElementById('chartAtrasos'), {
                                type: 'pie',
                                data: {
                                    labels: chartData.atrasos.labels,
                                    datasets: [{
                                        data: chartData.atrasos.data,
                                        backgroundColor: colorPalette.qualitative,
                                        borderWidth: 2,
                                        borderColor: 'rgba(30, 41, 59, 0.8)'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'right',
                                            labels: { padding: 15 }
                                        }
                                    }
                                }
                            });
                            document.getElementById('chartAtrasos').classList.add('chart-fade-in');
                        } catch (error) {
                            console.error('Erro ao criar gráfico de Atrasos:', error);
                            createNoDataPlaceholder('chartAtrasos');
                        }
                    } else {
                        console.log('Sem dados para gráfico de Atrasos');
                        createNoDataPlaceholder('chartAtrasos');
                    }

                    // 5. Gráfico de Vendas Mensais
                    if (hasValidData(chartData.vendasMensais.data)) {
                        console.log('Criando gráfico de Vendas Mensais:', chartData.vendasMensais);
                        try {
                            chartInstances.chartVendasMensais = new Chart(document.getElementById('chartVendasMensais'), {
                                type: 'line',
                                data: {
                                    labels: chartData.vendasMensais.labels.map(label => {
                                        const [year, month] = label.split('-');
                                        return `${month}/${year.substring(2)}`;
                                    }),
                                    datasets: [{
                                        label: 'Pedidos',
                                        data: chartData.vendasMensais.data,
                                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                        borderColor: colorPalette.primary[0],
                                        borderWidth: 3,
                                        tension: 0.4,
                                        fill: true,
                                        pointBackgroundColor: colorPalette.primary[0],
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                        pointHoverRadius: 6
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { color: 'rgba(100, 116, 139, 0.2)' }
                                        },
                                        x: {
                                            grid: { color: 'rgba(100, 116, 139, 0.1)' }
                                        }
                                    }
                                }
                            });
                            document.getElementById('chartVendasMensais').classList.add('chart-fade-in');
                        } catch (error) {
                            console.error('Erro ao criar gráfico de Vendas Mensais:', error);
                            createNoDataPlaceholder('chartVendasMensais');
                        }
                    } else {
                        console.log('Sem dados para gráfico de Vendas Mensais');
                        createNoDataPlaceholder('chartVendasMensais');
                    }

                    // 6. Gráfico de Vendas Anuais
                    if (hasValidData(chartData.vendasAnuais.data)) {
                        console.log('Criando gráfico de Vendas Anuais:', chartData.vendasAnuais);
                        try {
                            chartInstances.chartVendasAnuais = new Chart(document.getElementById('chartVendasAnuais'), {
                                type: 'bar',
                                data: {
                                    labels: chartData.vendasAnuais.labels,
                                    datasets: [{
                                        label: 'Pedidos',
                                        data: chartData.vendasAnuais.data,
                                        backgroundColor: colorPalette.qualitative[1],
                                        borderColor: colorPalette.qualitative[1],
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: { color: 'rgba(100, 116, 139, 0.2)' }
                                        },
                                        x: {
                                            grid: { color: 'rgba(100, 116, 139, 0.1)' }
                                        }
                                    }
                                }
                            });
                            document.getElementById('chartVendasAnuais').classList.add('chart-fade-in');
                        } catch (error) {
                            console.error('Erro ao criar gráfico de Vendas Anuais:', error);
                            createNoDataPlaceholder('chartVendasAnuais');
                        }
                    } else {
                        console.log('Sem dados para gráfico de Vendas Anuais');
                        createNoDataPlaceholder('chartVendasAnuais');
                    }

                    console.log('Finalizada inicialização dos gráficos');
                }

                function exportarParaExcel() {
                    const tabela = document.getElementById('tabela46');
                    const html = tabela.outerHTML;
                    
                    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'vW_Ind46_completo.xls';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }

                function clearSearch() {
                    document.getElementById('searchInput').value = '';
                    const linhas = document.querySelectorAll('#tabela46 tbody tr');
                    linhas.forEach(function(linha) {
                        linha.style.display = '';
                    });
                }
            </script>

        <?php else: ?>
            <div class="loading fade-in">
                <h3><i class="fas fa-exclamation-triangle"></i> Erro de conexão com o banco de dados</h3>
                <p>Verifique as configurações de conexão.</p>
                <?php if (sqlsrv_errors()): ?>
                    <pre><?= htmlspecialchars(print_r(sqlsrv_errors(), true)) ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Fechar conexão
if ($conn) {
    sqlsrv_close($conn);
}
?>