<?php
// =========================================================================
// VERIFICAÇÃO DE LOGIN E CARREGAMENTO DO NOME DO USUÁRIO
// =========================================================================
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit();
}

// Carregar informações do usuário da sessão
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_login = $_SESSION['usuario_login'] ?? '';
$usuario_departamento = $_SESSION['usuario_departamento'] ?? '';

// =========================================================================
// CONFIGURAÇÃO DO BANCO DE DADOS
// =========================================================================

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

// Variáveis
$erro = '';
$dados = [];
$total_registros = 0;
$dados_graficos = ['indicadores' => []];

if ($conn === false) {
    $erro = "Erro de conexão com o Banco de Dados.";
}

// Processar filtros
$filtro_nome = filter_input(INPUT_POST, 'filtro_nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// ----------------------------------------------------
// LÓGICA DE EXECUÇÃO DE CONSULTAS
// ----------------------------------------------------
if (empty($erro) && $conn !== false) {
    try {
        // CONSTRUÇÃO DA CONSULTA - ALTERADO PARA 'Operações'
        $where_conditions = ["Departamento = 'Operações'"]; // ALTERADO: 'Qualidade' para 'Operações'
        $query_params = [];
        
        if (!empty($filtro_nome)) {
            $where_conditions[] = "Nome LIKE ?";
            $query_params[] = '%' . $filtro_nome . '%';
        }

        $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";
        
        $sql_principal = "SELECT CodIndicador, Nome, Departamento, Dt_incl, User_Incl 
                         FROM tbl_Indicadores 
                         $where_clause 
                         ORDER BY CodIndicador DESC";

        // Execução da Consulta Principal
        $stmt = sqlsrv_query($conn, $sql_principal, $query_params);
        
        if ($stmt === false) {
            throw new Exception("Erro ao executar consulta: " . print_r(sqlsrv_errors(), true));
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $dados[] = $row;
            $total_registros++;
            
            // Preparar dados para gráficos (APENAS DADOS REAIS)
            $nome_indicador = $row['Nome'];
            if (!isset($dados_graficos['indicadores'][$nome_indicador])) {
                $dados_graficos['indicadores'][$nome_indicador] = 0;
            }
            $dados_graficos['indicadores'][$nome_indicador]++;
        }
        sqlsrv_free_stmt($stmt);

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Calcular estatísticas REAIS
$total_indicadores = $total_registros;
$percent_ativos = $total_indicadores > 0 ? 100 : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operações - EMBAQUIM</title>
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
            --text: #ffffff;
            --text-light: #f8fafc;
            --text-muted: #cbd5e1;
            --border: #475569;
            --card-bg: rgba(30, 41, 59, 0.95);
            --glass: rgba(255, 255, 255, 0.1);
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
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        
        /* HEADER MELHORADO */
        .header {
            background: var(--card-bg); 
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass); 
            border-radius: 24px;
            padding: 30px 40px; 
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.98) 0%, rgba(15, 23, 42, 0.98) 100%);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }
        
        .logo-icon {
            font-size: 3em;
            color: var(--accent);
            background: linear-gradient(135deg, var(--accent), #1e40af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 4px 8px rgba(59, 130, 246, 0.3));
        }
        
        .logo-text h1 {
            font-size: 2.8em; 
            font-weight: 800;
            background: linear-gradient(135deg, var(--text-light), var(--accent));
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .logo-text p {
            color: var(--text-muted);
            font-size: 1.1em;
            font-weight: 500;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            border-radius: 16px;
            background: var(--glass);
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
        }

        .avatar {
            position: relative;
            margin-right: 15px;
        }

        .avatar i {
            font-size: 40px;
            color: var(--accent);
            transition: all 0.3s ease;
            filter: drop-shadow(0 0 8px rgba(0, 242, 254, 0.5));
        }

        .online-status {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #00ff00;
            border-radius: 50%;
            border: 2px solid var(--dark-bg);
            box-shadow: 0 0 8px #00ff00;
            z-index: 10;
            animation: pulse-online 2s infinite;
        }

        @keyframes pulse-online {
            0% { box-shadow: 0 0 0 0 rgba(0, 255, 0, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(0, 255, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 255, 0, 0); }
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--text-light);
        }

        .user-department {
            font-size: 0.9em;
            color: var(--text-muted);
            font-weight: 500;
        }

        .back-button-container {
            margin-top: 50px;
            padding-top: 35px;
            border-top: 2px solid var(--glass);
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .back-button {
            background: linear-gradient(135deg, var(--secondary), #334155);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 18px 35px;
            font-size: 1.3em;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5);
            min-width: 280px;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .back-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.7);
            background: linear-gradient(135deg, #334155, #1d4ed8);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .filtros {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .filtros-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .filtros-header i {
            font-size: 1.4em;
            color: var(--accent);
        }

        .filtros-header h3 {
            font-size: 1.4em;
            font-weight: 600;
            color: var(--text-light);
        }

        .filtros-content {
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
            min-width: 300px;
        }

        .form-group label {
            color: var(--text-muted);
            font-size: 0.95em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input {
            padding: 14px 18px;
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1em;
            color: var(--text-light);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-group input::placeholder {
            color: rgba(203, 213, 225, 0.7);
            font-weight: 400;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 0.9);
        }

        .btn {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary), #475569);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #475569, #64748b);
        }

        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
            margin-bottom: 40px; 
        }
        
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-icon {
            font-size: 3em;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 3.5em;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--text-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 1.1em;
            font-weight: 600;
        }

        .dashboard { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); 
            gap: 30px; 
            margin-bottom: 40px; 
        }
        
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .card-header i {
            font-size: 1.6em;
            color: var(--accent);
        }

        .card-header h3 {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--text-light);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .grafico-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tipo-grafico-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            padding: 10px 15px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tipo-grafico-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .tipo-grafico-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .chart-wrapper { 
            position: relative; 
            height: 400px; 
            width: 100%; 
        }
        
        .no-data { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            height: 100%; 
            color: var(--text-muted); 
            text-align: center; 
        }
        
        .no-data i { 
            font-size: 4em; 
            margin-bottom: 20px; 
            opacity: 0.6; 
        }

        .no-data h4 {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .no-data p {
            color: var(--text-muted);
            font-weight: 500;
        }

        .tabela-container { 
            background: var(--card-bg); 
            border: 1px solid var(--glass); 
            border-radius: 20px; 
            overflow: hidden; 
            margin-bottom: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .tabela-header {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .tabela-header h3 {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .tabela-actions {
            display: flex;
            gap: 15px;
        }

        .search-container { 
            padding: 20px 30px; 
            border-bottom: 1px solid var(--border); 
            display: flex; 
            gap: 15px; 
            background: rgba(15, 23, 42, 0.6);
            position: relative;
        }
        
        .search-input { 
            flex: 1; 
            padding: 14px 20px; 
            background: rgba(15, 23, 42, 0.8); 
            border: 2px solid var(--border); 
            border-radius: 12px; 
            color: var(--text-light);
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .search-input::placeholder {
            color: rgba(203, 213, 225, 0.7);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 0.9);
        }

        .tabela-content { 
            overflow-x: auto; 
            max-height: 70vh; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 0.95em; 
        }
        
        th, td { 
            padding: 16px 20px; 
            text-align: left; 
            border-bottom: 1px solid var(--border); 
            white-space: nowrap; 
        }
        
        th { 
            background: rgba(15, 23, 42, 0.9); 
            font-weight: 700;
            position: sticky; 
            top: 0;
            color: var(--accent);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--accent);
            cursor: pointer;
        }
        
        tr:hover { 
            background: rgba(59, 130, 246, 0.1); 
        }

        .codigo-cell {
            color: var(--accent);
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .nome-cell {
            font-weight: 600;
            color: var(--text-light);
        }

        .departamento-cell {
            color: var(--success);
            font-weight: 700;
        }

        .null-cell {
            color: var(--text-muted);
            font-style: italic;
            font-weight: 500;
        }

        .error {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white; 
            padding: 20px; 
            border-radius: 16px; 
            margin-bottom: 25px; 
            font-size: 1.1em;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
            text-align: center;
        }

        .no-results {
            padding: 60px 40px;
            text-align: center;
            color: var(--text-muted);
        }

        .no-results i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        .no-results h4 {
            font-size: 1.4em;
            margin-bottom: 10px;
            color: var(--text-light);
            font-weight: 700;
        }

        .no-results p {
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                padding: 25px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .logo {
                justify-content: center;
            }
            
            .logo-text h1 {
                font-size: 2.2em;
            }
            
            .back-button-container {
                position: relative;
                top: auto;
                right: auto;
                margin-top: 15px;
                text-align: center;
            }
            
            .dashboard { 
                grid-template-columns: 1fr; 
            }
            
            .filtros-content {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .tabela-header {
                flex-direction: column;
                text-align: center;
            }
            
            .tabela-actions {
                justify-content: center;
            }

            .grafico-controls {
                flex-direction: column;
                gap: 8px;
            }

            .tipo-grafico-btn {
                padding: 12px 15px;
                font-size: 14px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER MELHORADO -->
        <div class="header">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-cogs"></i> <!-- ALTERADO: Ícone para Operações -->
                    </div>
                    <div class="logo-text">
                        <h1>Operações - EMBAQUIM</h1> <!-- ALTERADO: Título para Operações -->
                        <p>Sistema de Gestão de Indicadores de Operações</p> <!-- ALTERADO: Descrição para Operações -->
                    </div>
                </div>
                
                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i> 
                        <div class="online-status" title="Usuário Online"></div>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($usuario_nome); ?></div>
                        <div class="user-department"><?php echo htmlspecialchars($usuario_departamento); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="back-button-container">
                <a href="dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Voltar 
                </a>
            </div>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="error">
                <strong><i class="fas fa-exclamation-triangle"></i> Erro:</strong><br>
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="filtros">
            <div class="filtros-header">
                <i class="fas fa-sliders-h"></i>
                <h3>Filtros e Pesquisa</h3>
            </div>
            <div class="filtros-content">
                <div class="form-group">
                    <label for="filtro_nome"><i class="fas fa-search"></i> Pesquisar Indicador:</label>
                    <input type="text" id="filtro_nome" name="filtro_nome" 
                           value="<?php echo htmlspecialchars($filtro_nome); ?>" 
                           placeholder="Digite o nome do indicador...">
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-broom"></i> Limpar
                    </a>
                </div>
            </div>
        </form>

        <?php if ($conn && empty($erro)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--accent);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-number"><?= number_format($total_indicadores, 0, ',', '.') ?></div>
                    <div class="stat-label">Total de Indicadores</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--success);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number" style="-webkit-text-fill-color: var(--success);">
                        <?= number_format($total_indicadores, 0, ',', '.') ?>
                    </div>
                    <div class="stat-label">Indicadores Cadastrados</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--warning);">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stat-number" style="-webkit-text-fill-color: var(--warning);">
                        <?= number_format($percent_ativos, 0, ',', '.') ?>%
                    </div>
                    <div class="stat-label">Taxa de Atividade</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--accent);">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div class="stat-number" style="-webkit-text-fill-color: var(--accent);">6</div>
                    <div class="stat-label">Métricas Principais</div>
                </div>
            </div>

            <div class="dashboard">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i>
                        <h3>Distribuição de Indicadores de Operações</h3> <!-- ALTERADO: Título do gráfico -->
                    </div>
                    
                    <div class="grafico-controls">
                        <button class="tipo-grafico-btn active" data-chart="chartIndicadores" data-type="pie">
                            <i class="fas fa-chart-pie"></i> Pizza
                        </button>
                        <button class="tipo-grafico-btn" data-chart="chartIndicadores" data-type="bar">
                            <i class="fas fa-chart-bar"></i> Barras
                        </button>
                        <button class="tipo-grafico-btn" data-chart="chartIndicadores" data-type="horizontalBar">
                            <i class="fas fa-chart-bar"></i> Horizontal
                        </button>
                    </div>

                    <div class="chart-wrapper">
                        <?php if (!empty($dados_graficos['indicadores'])): ?>
                            <canvas id="chartIndicadores"></canvas>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-pie"></i>
                                <h4>Sem dados para exibir</h4>
                                <p>Não há dados disponíveis para o gráfico no momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        <h3>Informações do Sistema</h3>
                    </div>
                    <div class="chart-wrapper">
                        <div style="padding: 30px; color: var(--text-muted); line-height: 1.8;">
                            <p style="color: var(--text-light); font-weight: 600; margin-bottom: 15px;">📊 Sistema de Indicadores de Operações</p> <!-- ALTERADO: Descrição para Operações -->
                            <p style="margin-bottom: 20px; font-weight: 500;">Este dashboard monitora os principais indicadores de desempenho do departamento de Operações da empresa.</p> <!-- ALTERADO: Descrição para Operações -->
                            
                            <div style="margin-top: 25px; background: rgba(15, 23, 42, 0.6); padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                                <p style="color: var(--text-light); font-weight: 600; margin-bottom: 10px;">Estrutura de Dados:</p>
                                <ul style="margin-left: 20px; margin-top: 10px;">
                                    <li style="margin-bottom: 8px;"><strong style="color: var(--accent);">CodIndicador:</strong> Identificador único</li>
                                    <li style="margin-bottom: 8px;"><strong style="color: var(--accent);">Nome:</strong> Descrição do indicador</li>
                                    <li style="margin-bottom: 8px;"><strong style="color: var(--accent);">Departamento:</strong> Área responsável</li>
                                    <li style="margin-bottom: 8px;"><strong style="color: var(--accent);">Dt_incl:</strong> Data de cadastro</li>
                                    <li style="margin-bottom: 8px;"><strong style="color: var(--accent);">User_Incl:</strong> Usuário responsável</li>
                                </ul>
                            </div>
                            
                            <p style="margin-top: 25px; font-weight: 600;"><strong style="color: var(--text-light);">Total de Registros:</strong> <span style="color: var(--accent); font-weight: 700;"><?= $total_registros ?></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tabela-container">
                <div class="tabela-header">
                    <h3><i class="fas fa-table"></i> Detalhes dos Indicadores de Operações</h3> <!-- ALTERADO: Título da tabela -->
                    <div class="tabela-actions">
                        <button class="btn" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </button>
                        <button class="btn btn-secondary" onclick="printReport()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>

                <?php if ($total_registros > 0): ?>
                    <div class="search-container">
                        <input type="text" class="search-input" id="searchInput" 
                               placeholder="🔍 Pesquisar em todos os campos...">
                    </div>

                    <div class="tabela-content">
                        <table id="tabelaCompras">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nome do Indicador</th>
                                    <th>Departamento</th>
                                    <th>Data Inclusão</th>
                                    <th>Usuário</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dados as $row): ?>
                                    <tr>
                                        <td class="codigo-cell"><?= htmlspecialchars($row['CodIndicador'] ?? '') ?></td>
                                        <td class="nome-cell"><?= htmlspecialchars($row['Nome'] ?? '') ?></td>
                                        <td class="departamento-cell"><?= htmlspecialchars($row['Departamento'] ?? '') ?></td>
                                        <td class="<?php echo ($row['Dt_incl'] === null) ? 'null-cell' : ''; ?>">
                                            <?php 
                                            if ($row['Dt_incl'] instanceof DateTime) {
                                                echo $row['Dt_incl']->format('d/m/Y H:i:s');
                                            } else {
                                                echo htmlspecialchars($row['Dt_incl'] ?? 'Não informada');
                                            }
                                            ?>
                                        </td>
                                        <td class="<?php echo empty($row['User_Incl']) ? 'null-cell' : ''; ?>">
                                            <?= htmlspecialchars($row['User_Incl'] ?? 'Não informado') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-database"></i>
                        <h4>Nenhum indicador encontrado</h4>
                        <p>Não foram encontrados registros de indicadores de Operações com os filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // =========================================================================
        // GRÁFICOS INTERATIVOS - VERSÃO PROFISSIONAL
        // =========================================================================
        <?php if (!empty($dados_graficos['indicadores'])): ?>
        let chartIndicadores = null;
        
        function criarGraficoIndicadores(tipo = 'pie') {
            const ctx = document.getElementById('chartIndicadores').getContext('2d');
            
            // Destruir gráfico anterior se existir
            if (chartIndicadores) {
                chartIndicadores.destroy();
            }
            
            const dados = <?php echo json_encode($dados_graficos['indicadores']); ?>;
            const labels = Object.keys(dados);
            const valores = Object.values(dados);
            
            // Configuração de cores profissionais
            const cores = [
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1',
                '#14b8a6', '#f43f5e', '#8b5cf6', '#06b6d4', '#84cc16'
            ];

            // Configuração base profissional
            const configBase = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#f8fafc',
                            font: {
                                size: 11,
                                weight: '600',
                                family: "'Segoe UI', system-ui, -apple-system, sans-serif"
                            },
                            padding: 15,
                            usePointStyle: true,
                            boxWidth: 8,
                        }
                    },
                    title: {
                        display: true,
                        text: 'Distribuição de Indicadores de Operações',
                        color: '#f8fafc',
                        font: {
                            size: 16,
                            weight: 'bold',
                            family: "'Segoe UI', system-ui, -apple-system, sans-serif"
                        },
                        padding: 20
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.98)',
                        titleColor: '#f8fafc',
                        bodyColor: '#f8fafc',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} indicador(es) (${percentage}%)`;
                            }
                        }
                    }
                }
            };

            let config;

            if (tipo === 'pie') {
                config = {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: valores,
                            backgroundColor: cores,
                            borderColor: '#1e293b',
                            borderWidth: 2,
                            hoverBorderWidth: 3,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        ...configBase,
                        cutout: '0%',
                        plugins: {
                            ...configBase.plugins,
                            legend: {
                                ...configBase.plugins.legend,
                                position: 'right'
                            }
                        }
                    }
                };
            } 
            else if (tipo === 'bar') {
                config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: valores,
                            backgroundColor: cores.map(color => color + 'DD'),
                            borderColor: cores,
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        }]
                    },
                    options: {
                        ...configBase,
                        plugins: {
                            ...configBase.plugins,
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)',
                                    drawBorder: false,
                                    drawTicks: false
                                },
                                ticks: {
                                    color: '#cbd5e1',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                        family: "'Segoe UI', system-ui, -apple-system, sans-serif"
                                    },
                                    maxRotation: 45,
                                    minRotation: 45,
                                    padding: 10
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)',
                                    drawBorder: false,
                                    tickLength: 8
                                },
                                ticks: {
                                    color: '#cbd5e1',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                        family: "'Segoe UI', system-ui, -apple-system, sans-serif"
                                    },
                                    padding: 10,
                                    precision: 0,
                                    callback: function(value) {
                                        if (value % 1 === 0) {
                                            return value;
                                        }
                                    }
                                },
                                border: {
                                    display: false
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        },
                        elements: {
                            bar: {
                                backgroundColor: function(context) {
                                    const chart = context.chart;
                                    const {ctx, chartArea} = chart;
                                    if (!chartArea) return null;
                                    
                                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                    gradient.addColorStop(0, cores[context.dataIndex] + '99');
                                    gradient.addColorStop(1, cores[context.dataIndex] + 'FF');
                                    return gradient;
                                }
                            }
                        }
                    }
                };
            } 
            else if (tipo === 'horizontalBar') {
                config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: valores,
                            backgroundColor: cores.map(color => color + 'DD'),
                            borderColor: cores,
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.8,
                            categoryPercentage: 0.7
                        }]
                    },
                    options: {
                        ...configBase,
                        indexAxis: 'y',
                        plugins: {
                            ...configBase.plugins,
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)',
                                    drawBorder: false,
                                    tickLength: 8
                                },
                                ticks: {
                                    color: '#cbd5e1',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                        family: "'Segoe UI', system-ui, -apple-system, sans-serif"
                                    },
                                    padding: 10,
                                    precision: 0,
                                    callback: function(value) {
                                        if (value % 1 === 0) {
                                            return value;
                                        }
                                    }
                                },
                                border: {
                                    display: false
                                }
                            },
                            y: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.05)',
                                    drawBorder: false,
                                    drawTicks: false
                                },
                                ticks: {
                                    color: '#cbd5e1',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                        family: "'Segoe UI', system-ui, -apple-system, sans-serif"
                                    },
                                    padding: 8,
                                    mirror: true
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        },
                        elements: {
                            bar: {
                                backgroundColor: function(context) {
                                    const chart = context.chart;
                                    const {ctx, chartArea} = chart;
                                    if (!chartArea) return null;
                                    
                                    const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                                    gradient.addColorStop(0, cores[context.dataIndex] + '99');
                                    gradient.addColorStop(1, cores[context.dataIndex] + 'FF');
                                    return gradient;
                                }
                            }
                        }
                    }
                };
            }

            chartIndicadores = new Chart(ctx, config);
        }

        // Inicializar gráfico
        document.addEventListener('DOMContentLoaded', function() {
            criarGraficoIndicadores('pie');
            
            // Event listeners para botões de tipo de gráfico
            document.querySelectorAll('.tipo-grafico-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const chartName = this.dataset.chart;
                    const chartType = this.dataset.type;
                    
                    // Atualizar botões ativos
                    document.querySelectorAll(`[data-chart="${chartName}"]`).forEach(b => {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Atualizar gráfico
                    if (chartName === 'chartIndicadores') {
                        criarGraficoIndicadores(chartType);
                    }
                });
            });

            // Adicionar efeitos de hover nos botões
            document.querySelectorAll('.tipo-grafico-btn').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('active')) {
                        this.style.transform = 'translateY(-2px) scale(1.05)';
                    }
                });
                
                btn.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('active')) {
                        this.style.transform = 'translateY(0) scale(1)';
                    }
                });
            });
        });
        <?php endif; ?>

        // =========================================================================
        // FUNÇÕES DE UTILIDADE
        // =========================================================================
        
        // Pesquisa em tempo real na tabela
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#tabelaCompras tbody tr');
                    
                    let visibleCount = 0;
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        const isVisible = text.includes(filter);
                        row.style.display = isVisible ? '' : 'none';
                        if (isVisible) visibleCount++;
                    });

                    // Atualizar contador de resultados
                    let counter = document.getElementById('resultCount');
                    if (!counter) {
                        counter = document.createElement('div');
                        counter.id = 'resultCount';
                        counter.style.cssText = `
                            position: absolute;
                            right: 20px;
                            top: 50%;
                            transform: translateY(-50%);
                            background: var(--accent);
                            color: white;
                            padding: 4px 12px;
                            border-radius: 12px;
                            font-size: 0.8em;
                            font-weight: 600;
                        `;
                        searchInput.parentNode.appendChild(counter);
                    }
                    counter.textContent = `${visibleCount} resultado(s)`;
                });
            }
        });

        // Exportar para Excel
        function exportToExcel() {
            const table = document.getElementById('tabelaCompras');
            const html = table.outerHTML;
            
            // Criar arquivo Blob
            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            
            // Criar link de download
            const a = document.createElement('a');
            a.href = url;
            a.download = 'indicadores_operacoes_' + new Date().toISOString().split('T')[0] + '.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            // Feedback visual
            showNotification('Exportação realizada com sucesso!', 'success');
        }

        // Imprimir relatório
        function printReport() {
            const originalContents = document.body.innerHTML;
            const printContents = document.querySelector('.container').innerHTML;
            
            document.body.innerHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Relatório de Indicadores Operações - EMBAQUIM</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; color: #000; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
                        .stat-card { border: 1px solid #ddd; padding: 15px; text-align: center; border-radius: 8px; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 12px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; font-weight: bold; }
                        .no-print { display: none; }
                        .chart-wrapper { height: 300px; }
                        @media print {
                            .no-print { display: none; }
                            .grafico-controls { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${printContents}
                    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
                        Relatório gerado em ${new Date().toLocaleString('pt-BR')} | 
                        Usuário: <?php echo htmlspecialchars($usuario_nome); ?>
                    </div>
                </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }

        // Sistema de notificações
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--success)' : 'var(--accent)'};
                color: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                z-index: 10000;
                transform: translateX(400px);
                transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                font-weight: 600;
                max-width: 300px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 400);
            }, 3000);
        }

        // Ordenação da tabela
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('tabelaCompras');
            if (table) {
                const headers = table.querySelectorAll('th');
                headers.forEach((header, index) => {
                    header.style.cursor = 'pointer';
                    header.style.position = 'relative';
                    header.addEventListener('click', function() {
                        sortTable(index);
                    });
                });
            }
        });

        function sortTable(columnIndex) {
            const table = document.getElementById('tabelaCompras');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isNumeric = columnIndex === 0;
            
            rows.sort((a, b) => {
                const aCell = a.cells[columnIndex].textContent.trim();
                const bCell = b.cells[columnIndex].textContent.trim();
                
                if (isNumeric) {
                    return parseInt(aCell) - parseInt(bCell);
                } else {
                    return aCell.localeCompare(bCell, 'pt-BR', { sensitivity: 'base' });
                }
            });
            
            // Remove todas as linhas existentes
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            
            // Adiciona as linhas ordenadas
            rows.forEach(row => tbody.appendChild(row));

            showNotification('Tabela ordenada com sucesso!', 'success');
        }

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.stat-card, .card, .filtros');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.6s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Confirmação antes de sair
        window.addEventListener('beforeunload', function(e) {
            const searchValue = document.getElementById('filtro_nome')?.value;
            if (searchValue && searchValue.length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>