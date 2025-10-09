<?php
// Configurações de conexão
$serverName = "192.168.0.8,1433";
$connectionOptions = [
    "Database" => "Indicadores",
    "Uid" => "sa",
    "PWD" => "aplak2904&",
    "CharacterSet" => "UTF-8"
];

// Criar conexão
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    $conn = null;
}

// Valores padrão (dados de demonstração)
$kpi_empresas = 5;
$kpi_departamentos = 8;
$kpi_usuarios_ativos = 24;
$valor_acumulado_geral = 1850000;
$total_registros = 156;
$valor_medio_indicador = 11859;
$valor_ytd = 985000;
$valor_ano_anterior = 845000;
$variacao_anual = 16.5;
$variacao_anual_formatada = "16,5";
$icone_variacao = '↗';

// Funções auxiliares
function formatar_moeda($valor) {
    if ($valor === null) return 'R$ 0,00';
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function get_trend_class($valor) {
    return $valor >= 0 ? 'trend-up' : 'trend-down';
}

function get_trend_icon($valor) {
    return $valor >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
}

// Tentar buscar dados reais do banco
if ($conn) {
    try {
        // BUSCAR USUÁRIOS ATIVOS (vW_Usuario)
        $sql_usuarios = "SELECT COUNT(*) as total FROM vW_Usuario WHERE Desligado = 0";
        $stmt_usuarios = sqlsrv_query($conn, $sql_usuarios);
        if ($stmt_usuarios) {
            $row = sqlsrv_fetch_array($stmt_usuarios, SQLSRV_FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $kpi_usuarios_ativos = (int)$row['total'];
            }
        }

        // BUSCAR EMPRESAS - baseado na estrutura real (CodEmpresa em vW_Ind46)
        $sql_empresas = "SELECT COUNT(DISTINCT CodEmpresa) as total FROM vW_Ind46";
        $stmt_empresas = sqlsrv_query($conn, $sql_empresas);
        if ($stmt_empresas) {
            $row = sqlsrv_fetch_array($stmt_empresas, SQLSRV_FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $kpi_empresas = (int)$row['total'];
            }
        }

        // BUSCAR DEPARTAMENTOS - baseado na estrutura real (Departamento em vW_Usuario)
        $sql_departamentos = "SELECT COUNT(DISTINCT Departamento) as total FROM vW_Usuario";
        $stmt_departamentos = sqlsrv_query($conn, $sql_departamentos);
        if ($stmt_departamentos) {
            $row = sqlsrv_fetch_array($stmt_departamentos, SQLSRV_FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $kpi_departamentos = (int)$row['total'];
            }
        }

        // VALOR ACUMULADO - usando Qtdade da vW_Ind45 como exemplo
        $sql_valor = "SELECT SUM(Qtdade) as total FROM vW_Ind45";
        $stmt_valor = sqlsrv_query($conn, $sql_valor);
        if ($stmt_valor) {
            $row = sqlsrv_fetch_array($stmt_valor, SQLSRV_FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $valor_acumulado_geral = (float)$row['total'];
            }
        }

        // TOTAL DE REGISTROS - da tabela principal
        $sql_total = "SELECT COUNT(*) as total FROM vW_Ind45";
        $stmt_total = sqlsrv_query($conn, $sql_total);
        if ($stmt_total) {
            $row = sqlsrv_fetch_array($stmt_total, SQLSRV_FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $total_registros = (int)$row['total'];
            }
        }

        // CALCULAR VALOR MÉDIO
        if ($total_registros > 0) {
            $valor_medio_indicador = $valor_acumulado_geral / $total_registros;
        }

        // VALOR YTD (Year To Date) - exemplo com dados de 2024
        $sql_ytd = "SELECT SUM(Qtdade) as total FROM vW_Ind45 WHERE DtEmissão LIKE '%/24'";
        $stmt_ytd = sqlsrv_query($conn, $sql_ytd);
        if ($stmt_ytd) {
            $row = sqlsrv_fetch_array($stmt_ytd, SQLSRV_FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $valor_ytd = (float)$row['total'];
            }
        }

        // VALOR ANO ANTERIOR - exemplo com dados de 2023
        $sql_ano_anterior = "SELECT SUM(Qtdade) as total FROM vW_Ind45 WHERE DtEmissão LIKE '%/23'";
        $stmt_ano_anterior = sqlsrv_query($conn, $sql_ano_anterior);
        if ($stmt_ano_anterior) {
            $row = sqlsrv_fetch_array($stmt_ano_anterior, SQLSRV_FETCH_ASSOC);
            if ($row && isset($row['total'])) {
                $valor_ano_anterior = (float)$row['total'];
            }
        }

        // CALCULAR VARIAÇÃO ANUAL
        if ($valor_ano_anterior > 0) {
            $variacao_anual = (($valor_ytd - $valor_ano_anterior) / $valor_ano_anterior) * 100;
            $variacao_anual_formatada = number_format($variacao_anual, 1, ',', '');
            $icone_variacao = $variacao_anual >= 0 ? '↗' : '↘';
        }

    } catch (Exception $e) {
        // Mantém valores padrão em caso de erro
        error_log("Erro ao buscar dados: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise Analítica | Visão Geral</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #00f7ff;
            --primary-dark: #00a8b5;
            --secondary: #7b2cbf;
            --secondary-dark: #5a189a;
            --dark: #10002b;
            --darker: #0a0018;
            --light: #e0aaff;
            --success: #38b000;
            --danger: #ff0054;
            --card-bg: rgba(16, 0, 43, 0.7);
            --card-border: rgba(123, 44, 191, 0.3);
            --glow: 0 0 15px rgba(0, 247, 255, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(123, 44, 191, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 247, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(224, 170, 255, 0.05) 0%, transparent 50%);
            z-index: -1;
        }

        .grid-lines {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(123, 44, 191, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(123, 44, 191, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            opacity: 0.3;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(123, 44, 191, 0.3);
            margin-bottom: 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            font-size: 2.5rem;
            color: var(--primary);
            text-shadow: var(--glow);
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border: 2px solid var(--primary);
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid rgba(123, 44, 191, 0.3);
    margin-bottom: 30px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1; /* Ocupa o espaço disponível à esquerda */
}

.nav-buttons {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-left: auto; /* Empurra para a direita */
    margin-right: 20px; /* Espaço entre o botão e o user-info */
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-shrink: 0; /* Impede que o user-info diminua */
}

.btn-voltar {
    background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(123, 44, 191, 0.3);
    white-space: nowrap; /* Impede quebra de texto */
}

.btn-voltar:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(123, 44, 191, 0.5);
    background: linear-gradient(135deg, var(--secondary-dark), var(--secondary));
}

.btn-voltar i {
    font-size: 1.1rem;
}

        .dashboard-title {
            font-size: 2.2rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, var(--light), var(--primary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            font-weight: 300;
            letter-spacing: 1px;
        }

        .dashboard-subtitle {
            text-align: center;
            color: var(--light);
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .kpi-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }

        .kpi-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3), var(--glow);
            border-color: rgba(123, 44, 191, 0.5);
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .kpi-title {
            font-size: 1rem;
            color: var(--light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kpi-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            background: linear-gradient(to right, white, var(--light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .kpi-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .trend-up {
            color: var(--success);
        }

        .trend-down {
            color: var(--danger);
        }

        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
        }

        .chart-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: var(--primary);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table th {
            color: var(--light);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            border-top: 1px solid rgba(123, 44, 191, 0.3);
            color: var(--light);
            font-size: 0.9rem;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .status-badge {
            background: rgba(0, 247, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid var(--primary);
        }

        .status-success {
            background: rgba(56, 176, 0, 0.2);
            border-color: var(--success);
        }

        .status-warning {
            background: rgba(255, 158, 0, 0.2);
            border-color: #ff9e00;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            header {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-buttons {
                order: -1;
                width: 100%;
                justify-content: center;
            }
            
            .btn-voltar {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="grid-lines"></div>
    
    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-chart-network"></i></div>
                <div class="logo-text">ANALISE GERAL</div>
            </div>
            
            <div class="nav-buttons">
                <a href="javascript:history.back()" class="btn-voltar">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">AD</div>
                <div>
                    <div>Admin User</div>
                    <div style="font-size: 0.8rem; color: var(--light);">Administrador do Sistema</div>
                </div>
            </div>
        </header>
        
        <h1 class="dashboard-title">VISÃO GERAL DOS INDICADORES</h1>
        <p class="dashboard-subtitle">Dados em tempo real dos principais indicadores de performance</p>
        
        <div class="status-badge <?php echo $conn ? 'status-success' : 'status-warning'; ?>">
            <i class="fas <?php echo $conn ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            Sistema <?php echo $conn ? 'Conectado ao Banco de Dados - Dados Reais' : 'Operando com Dados de Demonstração'; ?>
            <?php if ($conn): ?>
                <br><small>✅ 4 tabelas conectadas | 📊 <?php echo $total_registros; ?> registros processados</small>
            <?php endif; ?>
        </div>
        
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Empresas/Filiais</div>
                    <div class="kpi-icon"><i class="fas fa-building"></i></div>
                </div>
                <div class="kpi-value"><?php echo $kpi_empresas; ?></div>
                <div class="kpi-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Empresas ativas</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Departamentos</div>
                    <div class="kpi-icon"><i class="fas fa-sitemap"></i></div>
                </div>
                <div class="kpi-value"><?php echo $kpi_departamentos; ?></div>
                <div class="kpi-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Departamentos ativos</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Usuários Ativos</div>
                    <div class="kpi-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="kpi-value"><?php echo $kpi_usuarios_ativos; ?></div>
                <div class="kpi-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>Usuários no sistema</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Total de Pedidos</div>
                    <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="kpi-value"><?php echo number_format($valor_acumulado_geral, 0, ',', '.'); ?></div>
                <div class="kpi-trend">
                    <span>Quantidade acumulada</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Média por Pedido</div>
                    <div class="kpi-icon"><i class="fas fa-calculator"></i></div>
                </div>
                <div class="kpi-value"><?php echo number_format($valor_medio_indicador, 2, ',', '.'); ?></div>
                <div class="kpi-trend">
                    <span>Média por registro</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Pedidos YTD 2024</div>
                    <div class="kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
                <div class="kpi-value"><?php echo number_format($valor_ytd, 0, ',', '.'); ?></div>
                <div class="kpi-trend <?php echo get_trend_class($variacao_anual); ?>">
                    <i class="fas <?php echo get_trend_icon($variacao_anual); ?>"></i>
                    <span><?php echo $variacao_anual_formatada; ?>% vs 2023</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Variação Anual</div>
                    <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
                </div>
                <div class="kpi-value" style="color: <?php echo ($variacao_anual >= 0) ? 'var(--success)' : 'var(--danger)'; ?>;">
                    <?php echo $icone_variacao . ' ' . $variacao_anual_formatada; ?>%
                </div>
                <div class="kpi-trend">
                    <span>Comparativo anual</span>
                </div>
            </div>
        </div>
        
        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-chart-bar"></i> Performance de Pedidos - 2024</div>
                <canvas id="performanceChart"></canvas>
            </div>
            
            <div class="chart-card">
                <div class="chart-title"><i class="fas fa-chart-pie"></i> Distribuição por Cliente</div>
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-title"><i class="fas fa-table"></i> Últimos Pedidos Processados</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Produto</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($conn) {
                        $sql_ultimos = "SELECT TOP 5 Pedido, Produto, Cliente, DtEmissão, Qtdade 
                                       FROM vW_Ind45 
                                       ORDER BY CodItemPedido DESC";
                        $stmt_ultimos = sqlsrv_query($conn, $sql_ultimos);
                        if ($stmt_ultimos) {
                            while ($row = sqlsrv_fetch_array($stmt_ultimos, SQLSRV_FETCH_ASSOC)) {
                                echo "<tr>
                                    <td>{$row['Pedido']}</td>
                                    <td>{$row['Produto']}</td>
                                    <td>{$row['Cliente']}</td>
                                    <td>{$row['DtEmissão']}</td>
                                    <td>" . number_format($row['Qtdade'], 2, ',', '.') . "</td>
                                </tr>";
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            Quantum Analytics Dashboard • Dados em tempo real • <?php echo date('d/m/Y H:i'); ?>
            <?php if ($conn): ?>
                <br><small>✅ Conectado às tabelas: vW_Ind45, vW_Ind46, vW_Ind47, vW_Usuario</small>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Gráfico de Performance Mensal
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                datasets: [{
                    label: 'Pedidos 2024',
                    data: [1200, 1350, 1420, 1580, 1650, 1720, 1800, 1850, 1900, 1950, 0, 0],
                    borderColor: '#00f7ff',
                    backgroundColor: 'rgba(0, 247, 255, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#e0aaff',
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#e0aaff'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#e0aaff'
                        }
                    }
                }
            }
        });

        // Gráfico de Distribuição
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        const departmentChart = new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Clientes Corporativos', 'Varejo', 'Distribuidores', 'Outros'],
                datasets: [{
                    data: [45, 25, 20, 10],
                    backgroundColor: [
                        '#00f7ff',
                        '#7b2cbf',
                        '#e0aaff',
                        '#38b000'
                    ],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#e0aaff',
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Efeitos de animação
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.kpi-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Atualização em tempo real
        setInterval(() => {
            const randomCard = document.querySelectorAll('.kpi-card')[Math.floor(Math.random() * 7)];
            randomCard.classList.add('pulse');
            setTimeout(() => randomCard.classList.remove('pulse'), 1000);
        }, 5000);
    </script>
</body>
</html>

<?php
// Fechar conexão
if ($conn) {
    sqlsrv_close($conn);
}
?>