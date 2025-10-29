<?php
// =============================================
// VERIFICAÇÃO DE LOGIN E CARREGAMENTO DO NOME DO USUÁRIO
// =============================================
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

// =============================================
// CONEXÃO COM O BANCO DE DADOS
// =============================================
$serverName = "192.168.0.8,1433";
$connectionInfo = array(
    "Database" => "Indicadores", 
    "Uid" => "sa", 
    "PWD" => "aplak2904&",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => false,
    "LoginTimeout" => 30
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    // Em caso de erro, mostra mensagem amigável mas não detalhes técnicos
    $erro_conexao = "Erro na conexão com o banco de dados. Contate o administrador.";
} else {
    // FUNÇÃO PARA GERAR RELATÓRIO MENSAL COMPLETO
    function gerarRelatorioMensal($conn, $tabela) {
        $relatorio = [
            'mensal' => [],
            'trimestral' => [],
            'anual' => [],
            'resumo' => [],
            'top_clientes' => [],
            'top_produtos' => []
        ];
        
        // Dados mensais (últimos 12 meses)
        $queryMensal = "SELECT 
            FORMAT(DtEmissao, 'yyyy-MM') as mes,
            COUNT(*) as total,
            SUM(CASE WHEN StatusEntrega = 'ATRASADO' THEN 1 ELSE 0 END) as atrasados,
            SUM(CASE WHEN StatusEntrega = 'NA DATA' THEN 1 ELSE 0 END) as na_data,
            SUM(CASE WHEN StatusEntrega = 'ADIANTADO' THEN 1 ELSE 0 END) as adiantados,
            AVG(CAST(DIAS as FLOAT)) as media_dias
        FROM $tabela 
        WHERE DtEmissao >= DATEADD(MONTH, -12, GETDATE())
        GROUP BY FORMAT(DtEmissao, 'yyyy-MM')
        ORDER BY mes DESC";
        
        $stmt = sqlsrv_query($conn, $queryMensal);
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $relatorio['mensal'][$row['mes']] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // Dados trimestrais
        $queryTrimestral = "SELECT 
            DATEPART(YEAR, DtEmissao) as ano,
            DATEPART(QUARTER, DtEmissao) as trimestre,
            COUNT(*) as total,
            SUM(CASE WHEN StatusEntrega = 'ATRASADO' THEN 1 ELSE 0 END) as atrasados,
            AVG(CAST(DIAS as FLOAT)) as media_dias
        FROM $tabela 
        WHERE DtEmissao >= DATEADD(YEAR, -2, GETDATE())
        GROUP BY DATEPART(YEAR, DtEmissao), DATEPART(QUARTER, DtEmissao)
        ORDER BY ano DESC, trimestre DESC";
        
        $stmt = sqlsrv_query($conn, $queryTrimestral);
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $relatorio['trimestral'][$row['ano'] . '-T' . $row['trimestre']] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // Dados anuais
        $queryAnual = "SELECT 
            YEAR(DtEmissao) as ano,
            COUNT(*) as total,
            SUM(CASE WHEN StatusEntrega = 'ATRASADO' THEN 1 ELSE 0 END) as atrasados,
            AVG(CAST(DIAS as FLOAT)) as media_dias
        FROM $tabela 
        WHERE DtEmissao >= DATEADD(YEAR, -5, GETDATE())
        GROUP BY YEAR(DtEmissao)
        ORDER BY ano DESC";
        
        $stmt = sqlsrv_query($conn, $queryAnual);
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $relatorio['anual'][$row['ano']] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // Resumo geral
        $queryResumo = "SELECT 
            COUNT(*) as total_geral,
            SUM(CASE WHEN StatusEntrega = 'ATRASADO' THEN 1 ELSE 0 END) as total_atrasados,
            SUM(CASE WHEN StatusEntrega = 'NA DATA' THEN 1 ELSE 0 END) as total_na_data,
            SUM(CASE WHEN StatusEntrega = 'ADIANTADO' THEN 1 ELSE 0 END) as total_adiantados,
            AVG(CAST(DIAS as FLOAT)) as media_dias_geral,
            MIN(DtEmissao) as data_mais_antiga,
            MAX(DtEmissao) as data_mais_recente
        FROM $tabela";
        
        $stmt = sqlsrv_query($conn, $queryResumo);
        if ($stmt) {
            $relatorio['resumo'] = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
        }
        
        // Top 10 Clientes
        $queryClientes = "SELECT TOP 10 
            Cliente,
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN StatusEntrega = 'ATRASADO' THEN 1 ELSE 0 END) as atrasados
        FROM $tabela 
        WHERE Cliente IS NOT NULL AND Cliente != ''
        GROUP BY Cliente
        ORDER BY total_pedidos DESC";
        
        $stmt = sqlsrv_query($conn, $queryClientes);
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $relatorio['top_clientes'][] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // Top 10 Produtos
        $queryProdutos = "SELECT TOP 10 
            Produto,
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN StatusEntrega = 'ATRASADO' THEN 1 ELSE 0 END) as atrasados
        FROM $tabela 
        WHERE Produto IS NOT NULL AND Produto != ''
        GROUP BY Produto
        ORDER BY total_pedidos DESC";
        
        $stmt = sqlsrv_query($conn, $queryProdutos);
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $relatorio['top_produtos'][] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        
        return $relatorio;
    }

    // Gerar relatórios mensais para as 3 tabelas
    $relatorioInd45 = gerarRelatorioMensal($conn, "vW_Ind45_Formatada");
    $relatorioInd46 = gerarRelatorioMensal($conn, "vW_Ind46_Formatada");
    $relatorioInd47 = gerarRelatorioMensal($conn, "vW_Ind47_Formatada");
    
    // Fechar conexão
    sqlsrv_close($conn);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Mensais | Embaquim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="img/favicon.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ------------------------------------- */
        /* 1. VARIÁVEIS E RESET BÁSICO           */
        /* ------------------------------------- */
        :root {
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
            --card-bg: rgba(255, 255, 255, 0.05);
            --glow-color: rgba(0, 242, 254, 0.5);
            
            /* Cores para alertas */
            --alerta-critico: #e74c3c;
            --alerta-alto: #f39c12;
            --alerta-medio: #f1c40f;
            --alerta-baixo: #3498db;
            --alerta-info: #2ecc71;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        html, body, .app-layout {
            height: 100%;
        }

        body {
            background-color: var(--dark-bg);
            overflow: hidden;
            color: var(--text-light);
            position: relative;
        }

        /* ------------------------------------- */
        /* 2. FUNDO ANIMADO E PARTÍCULAS         */
        /* ------------------------------------- */
        .background-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .gradient-bg {
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background: linear-gradient(45deg, var(--primary-gradient-start), var(--secondary-gradient-start), var(--primary-gradient-end), var(--secondary-gradient-end));
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            filter: blur(20px);
            opacity: 0.7;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 50%;
            animation: float 15s infinite linear;
            opacity: 0.7;
        }

        @keyframes float {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 0.7; }
            90% { opacity: 0.7; }
            100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
        }

        /* ------------------------------------- */
        /* 3. LAYOUT PRINCIPAL (GRID)            */
        /* ------------------------------------- */
        .app-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            grid-template-rows: 100vh;
            backdrop-filter: blur(10px);
        }

        /* ------------------------------------- */
        /* 4. SIDEBAR (Barra Lateral)            */
        /* ------------------------------------- */
        .sidebar {
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            padding: 0;
            overflow-y: auto;
            border-right: 1px solid var(--header-border);
            backdrop-filter: blur(10px);
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.3);
            z-index: 10;
        }

        /* ------------------------------------- */
        /* LOGO HARMONIOSO - RELATÓRIOS - COR BRANCA */
        /* ------------------------------------- */
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
            height: 80px;
            border-bottom: 1px solid var(--header-border);
            background: linear-gradient(90deg, rgba(106, 17, 203, 0.2), rgba(37, 117, 252, 0.2));
            position: relative;
            overflow: hidden;
        }

        .logo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.08), transparent);
            transition: left 0.8s;
        }

        .logo-section:hover::before {
            left: 100%;
        }

        .logo-image-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            position: relative;
        }

        .logo-image {
            width: auto;
            height: 45px;
            object-fit: contain;
            /* FILTRO PARA DEIXAR O LOGO BRANCO */
            filter: brightness(0) invert(1) drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
            transition: all 0.3s ease;
        }

        .logo-section:hover .logo-image {
            transform: scale(1.05);
            filter: brightness(0) invert(1) drop-shadow(0 0 15px rgba(255, 255, 255, 0.5));
        }

        /* Efeito de brilho sutil atrás do logo */
        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, 
                rgba(255, 255, 255, 0.15) 0%, 
                rgba(255, 255, 255, 0.1) 30%, 
                transparent 70%);
            filter: blur(15px);
            animation: logoGlowPulse 3s ease-in-out infinite alternate;
            z-index: -1;
            opacity: 0.6;
        }

        @keyframes logoGlowPulse {
            0% { opacity: 0.4; transform: translate(-50%, -50%) scale(0.95); }
            100% { opacity: 0.7; transform: translate(-50%, -50%) scale(1.05); }
        }

        /* Responsividade para o logo */
        @media (max-width: 900px) {
            .logo-section {
                padding: 15px 10px;
                height: 70px;
            }
            
            .logo-image {
                height: 40px;
            }
        }

        @media (max-width: 480px) {
            .logo-section {
                padding: 12px 8px;
                height: 60px;
            }
            
            .logo-image {
                height: 35px;
            }
        }

        /* Menu de Navegação */
        .nav-menu {
            padding: 20px 0;
        }

        .nav-menu ul {
            list-style: none;
            padding: 0;
        }

        .nav-item {
            padding: 0;
            font-size: 15px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: var(--text-light);
            text-decoration: none;
            padding: 15px 20px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background-color: var(--hover-bg);
            padding-left: 25px;
        }
        
        .nav-item.has-submenu > .nav-link:hover {
            padding-left: 20px;
        }
        
        .nav-item.has-submenu > .nav-link {
            cursor: pointer;
        }

        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .nav-link:hover i {
            transform: scale(1.2);
            color: var(--accent-color);
        }

        /* Item Ativo */
        .nav-item.active .nav-link {
            background-color: var(--active-item-bg);
            font-weight: 700;
            box-shadow: 4px 0 0 0 var(--accent-color) inset;
            padding-left: 25px;
        }
        
        .nav-item.active.has-submenu > .nav-link {
             padding-left: 20px;
        }

        .nav-item.active .nav-link i {
            color: var(--accent-color);
            transform: scale(1.1);
        }

        /* Submenu */
        .submenu {
            list-style: none;
            background-color: rgba(26, 0, 64, 0.6);
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out;
        }

        .nav-item.open .submenu {
            max-height: 500px;
        }

        .submenu-item a {
            display: block;
            padding: 12px 20px 12px 60px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
        }

        .submenu-item a::before {
            content: '';
            position: absolute;
            left: 45px;
            top: 50%;
            width: 6px;
            height: 6px;
            background: var(--accent-color);
            border-radius: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .submenu-item a:hover::before {
            opacity: 1;
        }

        .submenu-item a:hover {
            background-color: var(--hover-bg);
            padding-left: 65px;
            color: var(--accent-color);
        }

        .submenu-icon {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.3s;
        }

        .nav-item.open .submenu-icon {
            transform: rotate(180deg);
        }

        /* ------------------------------------- */
        /* 5. MAIN CONTENT (HEADER E CONTEÚDO)   */
        /* ------------------------------------- */
        .main-content {
            display: flex;
            flex-direction: column;
            background-color: var(--content-bg);
            overflow: hidden;
        }

        /* Header Superior */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            height: 80px;
            background: rgba(15, 15, 35, 0.5);
            border-bottom: 1px solid var(--header-border);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-light);
        }

        /* Caixa de Pesquisa */
        .search-box {
            display: flex;
            align-items: center;
            background-color: var(--search-bg);
            border: 1px solid var(--header-border);
            border-radius: 10px;
            padding: 10px 15px;
            width: 350px;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            border-color: var(--accent-color);
            box-shadow: 0 0 10px var(--glow-color);
        }

        .search-box i {
            color: var(--text-light);
            margin-right: 10px;
            font-size: 16px;
        }

        .search-box input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 15px;
            flex-grow: 1;
            padding: 2px 0;
            color: var(--text-light);
        }

        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Perfil do Usuário */
        .user-profile {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
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

        /* Área de Conteúdo Principal */
        .content-area {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Header da Página */
        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(to right, #ffffff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }

        /* ESTILOS PARA RELATÓRIOS MENSAIS */
        .relatorio-container {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .relatorio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .relatorio-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .relatorio-periodo {
            font-size: 14px;
            color: var(--accent-color);
            background: rgba(0, 242, 254, 0.1);
            padding: 5px 12px;
            border-radius: 20px;
        }

        .stats-grid-relatorio {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card-relatorio {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card-relatorio:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .stat-icon-relatorio {
            font-size: 2em;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .stat-number-relatorio {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--accent-color);
        }

        .stat-label-relatorio {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tabela-relatorio {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .tabela-relatorio th {
            background: rgba(0, 242, 254, 0.1);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--accent-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tabela-relatorio td {
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
        }

        .tabela-relatorio tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .badge-percent {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success {
            background: var(--alerta-info);
            color: white;
        }

        .badge-warning {
            background: var(--alerta-alto);
            color: white;
        }

        .badge-danger {
            background: var(--alerta-critico);
            color: white;
        }

        .secao-relatorio {
            margin-bottom: 30px;
        }

        .secao-titulo {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .acoes-relatorio {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-relatorio {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 8px 15px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-relatorio:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn-relatorio.exportar {
            background: var(--alerta-info);
            color: var(--dark-bg);
            border-color: var(--alerta-info);
        }

        .btn-relatorio.imprimir {
            background: var(--accent-color);
            color: var(--dark-bg);
            border-color: var(--accent-color);
        }

        .grafico-relatorio {
            height: 300px;
            margin-bottom: 20px;
        }

        .tendencia {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
        }

        .tendencia.positiva {
            color: var(--alerta-info);
            background: rgba(46, 204, 113, 0.1);
        }

        .tendencia.negativa {
            color: var(--alerta-critico);
            background: rgba(231, 76, 60, 0.1);
        }

        .tendencia.neutra {
            color: var(--alerta-medio);
            background: rgba(241, 196, 15, 0.1);
        }

        .copyright {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            margin-top: 40px;
        }

        /* Mensagem de Erro */
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid var(--alerta-critico);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .error-message i {
            font-size: 48px;
            color: var(--alerta-critico);
            margin-bottom: 15px;
        }

        /* ------------------------------------- */
        /* 6. EFEITOS EXTRAS                     */
        /* ------------------------------------- */
        .glow-effect {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: -1;
        }

        .glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.5;
        }

        .glow-1 {
            width: 200px;
            height: 200px;
            background: var(--primary-gradient-start);
            top: -50px;
            left: -50px;
            animation: glowMove1 10s infinite alternate;
        }

        .glow-2 {
            width: 300px;
            height: 300px;
            background: var(--secondary-gradient-end);
            bottom: -100px;
            right: -100px;
            animation: glowMove2 15s infinite alternate;
        }

        @keyframes glowMove1 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        @keyframes glowMove2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50px, -50px); }
        }

        /* ------------------------------------- */
        /* 7. RESPONSIVIDADE                     */
        /* ------------------------------------- */
        @media (max-width: 900px) {
            .app-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: -260px;
                width: 260px;
                z-index: 1000;
                transition: left 0.3s;
            }

            .sidebar.active {
                left: 0;
            }

            .top-header {
                padding: 15px 20px;
            }

            .search-box {
                width: 200px;
            }

            .content-area {
                padding: 20px;
            }

            .stats-grid-relatorio {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
            }

            .stat-card-relatorio {
                padding: 15px;
            }

            .stat-number-relatorio {
                font-size: 1.5em;
            }

            .acoes-relatorio {
                flex-direction: column;
            }

            .btn-relatorio {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background-animation">
        <div class="gradient-bg"></div>
        <div class="particles" id="particles"></div>
    </div>
    
    <div class="glow-effect">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
    </div>

    <div class="app-layout">
        
        <aside class="sidebar">
            <div class="logo-section">
                <div class="logo-image-container">
                    <div class="logo-glow"></div>
                    <img src="img/logo2025.png" alt="Logo Embaquim 2025" class="logo-image">
                </div>
            </div>

            <nav class="nav-menu">
                <ul>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link" data-menu="dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="meuperfil.php" class="nav-link">
                            <i class="fas fa-user-circle"></i>
                            <span>Meu Perfil</span>
                        </a>
                    </li>

                    <li class="nav-item has-submenu" data-toggle="indicadores">
                        <a href="#" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Indicadores</span>
                            <i class="fas fa-chevron-down submenu-icon"></i>
                        </a>
                        <ul class="submenu" id="submenu-indicadores">
                            <li class="submenu-item"><a href="visaogeral.php">Visão Geral</a></li>
                            <li class="submenu-item"><a href="indicadores45.php">Indicadores 45</a></li>
                            <li class="submenu-item"><a href="indicadores46.php">Indicadores 46</a></li>
                            <li class="submenu-item"><a href="indicadores47.php">Indicadores 47</a></li>
                            <li class="submenu-item"><a href="metasdotrimestre.php">Meta do Trimestre</a></li>
                            <li class="submenu-item"><a href="desempenhodaequipe.php">Desempenho da Equipe</a></li>
                            <li class="submenu-item active"><a href="relatoriosmensais.php">Relatórios Mensais</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="alertasrecentes.php" class="nav-link">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Alertas Recentes</span>
                        </a>
                    </li>

                    <li class="nav-item has-submenu" data-toggle="cadastros">
                        <a href="#" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Cadastros</span>
                            <i class="fas fa-chevron-down submenu-icon"></i>
                        </a>
                        <ul class="submenu" id="submenu-cadastros">
                            <li class="submenu-item"><a href="cadastro_empresas.php">Empresas</a></li>
                            <li class="submenu-item"><a href="cadastro_usuarios.php">Usuários</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="configuracoes.php" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Sair</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            
            <header class="top-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Pesquisar relatórios...">
                </div>

                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i> 
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($usuario_nome); ?></span>
                </div>
            </header>

            <section class="content-area">
                
                <div class="page-header">
                    <h1 class="page-title">Relatórios Mensais</h1>
                    <p class="page-description">Análise detalhada do desempenho mensal dos indicadores do sistema</p>
                </div>

                <?php if (isset($erro_conexao)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Erro de Conexão</h3>
                    <p><?php echo $erro_conexao; ?></p>
                </div>
                <?php else: ?>

                <!-- RELATÓRIO INDICADOR 45 -->
                <div class="relatorio-container">
                    <div class="relatorio-header">
                        <h2 class="relatorio-title">
                            <i class="fas fa-chart-line" style="color: var(--accent-color);"></i>
                            Relatório Mensal - vW_Ind45_Formatada
                        </h2>
                        <span class="relatorio-periodo">Período: Últimos 12 Meses</span>
                    </div>

                    <!-- Resumo Estatístico -->
                    <div class="stats-grid-relatorio">
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--accent-color);">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd45['resumo']['total_geral'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">Total de Registros</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-critico);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd45['resumo']['total_atrasados'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">Pedidos Atrasados</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-info);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd45['resumo']['total_na_data'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">No Prazo</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-baixo);">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd45['resumo']['media_dias_geral'] ?? 0, 1); ?>
                            </div>
                            <div class="stat-label-relatorio">Dias Médios</div>
                        </div>
                    </div>

                    <!-- Dados Mensais -->
                    <div class="secao-relatorio">
                        <h3 class="secao-titulo">
                            <i class="fas fa-calendar-alt"></i>
                            Desempenho Mensal (Últimos 12 meses)
                        </h3>
                        <div class="table-responsive">
                            <table class="tabela-relatorio">
                                <thead>
                                    <tr>
                                        <th>Mês/Ano</th>
                                        <th>Total</th>
                                        <th>Atrasados</th>
                                        <th>No Prazo</th>
                                        <th>Adiantados</th>
                                        <th>Dias Médios</th>
                                        <th>Taxa de Atraso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($relatorioInd45['mensal'] as $mes => $dados): ?>
                                    <tr>
                                        <td><strong><?php echo date('m/Y', strtotime($mes . '-01')); ?></strong></td>
                                        <td><?php echo number_format($dados['total']); ?></td>
                                        <td>
                                            <span style="color: var(--alerta-critico); font-weight: 600;">
                                                <?php echo number_format($dados['atrasados']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--alerta-info); font-weight: 600;">
                                                <?php echo number_format($dados['na_data']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--alerta-baixo); font-weight: 600;">
                                                <?php echo number_format($dados['adiantados']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($dados['media_dias'] ?? 0, 1); ?> dias</td>
                                        <td>
                                            <?php 
                                            $taxaAtraso = $dados['total'] > 0 ? ($dados['atrasados'] / $dados['total']) * 100 : 0;
                                            $badgeClass = $taxaAtraso > 20 ? 'badge-danger' : ($taxaAtraso > 10 ? 'badge-warning' : 'badge-success');
                                            ?>
                                            <span class="badge-percent <?php echo $badgeClass; ?>">
                                                <?php echo number_format($taxaAtraso, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Dados Trimestrais -->
                    <div class="secao-relatorio">
                        <h3 class="secao-titulo">
                            <i class="fas fa-chart-bar"></i>
                            Visão Trimestral
                        </h3>
                        <div class="table-responsive">
                            <table class="tabela-relatorio">
                                <thead>
                                    <tr>
                                        <th>Trimestre</th>
                                        <th>Total</th>
                                        <th>Atrasados</th>
                                        <th>Dias Médios</th>
                                        <th>Taxa de Atraso</th>
                                        <th>Tendência</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $trimestres = array_slice($relatorioInd45['trimestral'], 0, 8, true);
                                    $previous = null;
                                    foreach ($trimestres as $trimestre => $dados): 
                                        $taxaAtraso = $dados['total'] > 0 ? ($dados['atrasados'] / $dados['total']) * 100 : 0;
                                        
                                        // Calcular tendência
                                        $tendencia = 'neutra';
                                        if ($previous !== null) {
                                            $variacao = $taxaAtraso - $previous;
                                            if ($variacao > 2) $tendencia = 'negativa';
                                            elseif ($variacao < -2) $tendencia = 'positiva';
                                        }
                                        $previous = $taxaAtraso;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $trimestre; ?></strong></td>
                                        <td><?php echo number_format($dados['total']); ?></td>
                                        <td><?php echo number_format($dados['atrasados']); ?></td>
                                        <td><?php echo number_format($dados['media_dias'] ?? 0, 1); ?> dias</td>
                                        <td>
                                            <span class="badge-percent <?php echo $taxaAtraso > 20 ? 'badge-danger' : ($taxaAtraso > 10 ? 'badge-warning' : 'badge-success'); ?>">
                                                <?php echo number_format($taxaAtraso, 1); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($tendencia !== 'neutra'): ?>
                                            <span class="tendencia <?php echo $tendencia; ?>">
                                                <i class="fas fa-arrow-<?php echo $tendencia === 'positiva' ? 'down' : 'up'; ?>"></i>
                                                <?php echo $tendencia === 'positiva' ? 'Melhorando' : 'Piorando'; ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="tendencia neutra">Estável</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Clientes -->
                    <div class="secao-relatorio">
                        <h3 class="secao-titulo">
                            <i class="fas fa-users"></i>
                            Top 10 Clientes
                        </h3>
                        <div class="table-responsive">
                            <table class="tabela-relatorio">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Total de Pedidos</th>
                                        <th>Atrasados</th>
                                        <th>Taxa de Atraso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($relatorioInd45['top_clientes'] as $cliente): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cliente['Cliente']); ?></strong></td>
                                        <td><?php echo number_format($cliente['total_pedidos']); ?></td>
                                        <td><?php echo number_format($cliente['atrasados']); ?></td>
                                        <td>
                                            <?php 
                                            $taxaAtraso = $cliente['total_pedidos'] > 0 ? ($cliente['atrasados'] / $cliente['total_pedidos']) * 100 : 0;
                                            $badgeClass = $taxaAtraso > 20 ? 'badge-danger' : ($taxaAtraso > 10 ? 'badge-warning' : 'badge-success');
                                            ?>
                                            <span class="badge-percent <?php echo $badgeClass; ?>">
                                                <?php echo number_format($taxaAtraso, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Ações do Relatório -->
                    <div class="acoes-relatorio">
                        <a href="#" class="btn-relatorio exportar" onclick="exportarRelatorio('ind45')">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </a>
                        <a href="#" class="btn-relatorio imprimir" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir Relatório
                        </a>
                    </div>
                </div>

                <!-- RELATÓRIO INDICADOR 46 -->
                <div class="relatorio-container">
                    <div class="relatorio-header">
                        <h2 class="relatorio-title">
                            <i class="fas fa-chart-line" style="color: var(--alerta-info);"></i>
                            Relatório Mensal - vW_Ind46_Formatada
                        </h2>
                        <span class="relatorio-periodo">Período: Últimos 12 Meses</span>
                    </div>

                    <!-- Resumo Estatístico -->
                    <div class="stats-grid-relatorio">
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--accent-color);">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd46['resumo']['total_geral'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">Total de Registros</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-critico);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd46['resumo']['total_atrasados'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">Pedidos Atrasados</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-info);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd46['resumo']['total_na_data'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">No Prazo</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-baixo);">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd46['resumo']['media_dias_geral'] ?? 0, 1); ?>
                            </div>
                            <div class="stat-label-relatorio">Dias Médios</div>
                        </div>
                    </div>

                    <!-- Tabelas similares para Indicador 46 -->
                    <div class="secao-relatorio">
                        <h3 class="secao-titulo">
                            <i class="fas fa-calendar-alt"></i>
                            Desempenho Mensal (Últimos 12 meses)
                        </h3>
                        <div class="table-responsive">
                            <table class="tabela-relatorio">
                                <thead>
                                    <tr>
                                        <th>Mês/Ano</th>
                                        <th>Total</th>
                                        <th>Atrasados</th>
                                        <th>No Prazo</th>
                                        <th>Adiantados</th>
                                        <th>Dias Médios</th>
                                        <th>Taxa de Atraso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($relatorioInd46['mensal'] as $mes => $dados): ?>
                                    <tr>
                                        <td><strong><?php echo date('m/Y', strtotime($mes . '-01')); ?></strong></td>
                                        <td><?php echo number_format($dados['total']); ?></td>
                                        <td>
                                            <span style="color: var(--alerta-critico); font-weight: 600;">
                                                <?php echo number_format($dados['atrasados']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--alerta-info); font-weight: 600;">
                                                <?php echo number_format($dados['na_data']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--alerta-baixo); font-weight: 600;">
                                                <?php echo number_format($dados['adiantados']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($dados['media_dias'] ?? 0, 1); ?> dias</td>
                                        <td>
                                            <?php 
                                            $taxaAtraso = $dados['total'] > 0 ? ($dados['atrasados'] / $dados['total']) * 100 : 0;
                                            $badgeClass = $taxaAtraso > 20 ? 'badge-danger' : ($taxaAtraso > 10 ? 'badge-warning' : 'badge-success');
                                            ?>
                                            <span class="badge-percent <?php echo $badgeClass; ?>">
                                                <?php echo number_format($taxaAtraso, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="acoes-relatorio">
                        <a href="#" class="btn-relatorio exportar" onclick="exportarRelatorio('ind46')">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </a>
                        <a href="#" class="btn-relatorio imprimir" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir Relatório
                        </a>
                    </div>
                </div>

                <!-- RELATÓRIO INDICADOR 47 -->
                <div class="relatorio-container">
                    <div class="relatorio-header">
                        <h2 class="relatorio-title">
                            <i class="fas fa-chart-line" style="color: var(--alerta-baixo);"></i>
                            Relatório Mensal - vW_Ind47_Formatada
                        </h2>
                        <span class="relatorio-periodo">Período: Últimos 12 Meses</span>
                    </div>

                    <!-- Resumo Estatístico -->
                    <div class="stats-grid-relatorio">
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--accent-color);">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd47['resumo']['total_geral'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">Total de Registros</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-critico);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd47['resumo']['total_atrasados'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">Pedidos Atrasados</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-info);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd47['resumo']['total_na_data'] ?? 0); ?>
                            </div>
                            <div class="stat-label-relatorio">No Prazo</div>
                        </div>
                        
                        <div class="stat-card-relatorio">
                            <div class="stat-icon-relatorio" style="color: var(--alerta-baixo);">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-number-relatorio">
                                <?php echo number_format($relatorioInd47['resumo']['media_dias_geral'] ?? 0, 1); ?>
                            </div>
                            <div class="stat-label-relatorio">Dias Médios</div>
                        </div>
                    </div>

                    <!-- Tabelas similares para Indicador 47 -->
                    <div class="secao-relatorio">
                        <h3 class="secao-titulo">
                            <i class="fas fa-calendar-alt"></i>
                            Desempenho Mensal (Últimos 12 meses)
                        </h3>
                        <div class="table-responsive">
                            <table class="tabela-relatorio">
                                <thead>
                                    <tr>
                                        <th>Mês/Ano</th>
                                        <th>Total</th>
                                        <th>Atrasados</th>
                                        <th>No Prazo</th>
                                        <th>Adiantados</th>
                                        <th>Dias Médios</th>
                                        <th>Taxa de Atraso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($relatorioInd47['mensal'] as $mes => $dados): ?>
                                    <tr>
                                        <td><strong><?php echo date('m/Y', strtotime($mes . '-01')); ?></strong></td>
                                        <td><?php echo number_format($dados['total']); ?></td>
                                        <td>
                                            <span style="color: var(--alerta-critico); font-weight: 600;">
                                                <?php echo number_format($dados['atrasados']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--alerta-info); font-weight: 600;">
                                                <?php echo number_format($dados['na_data']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--alerta-baixo); font-weight: 600;">
                                                <?php echo number_format($dados['adiantados']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($dados['media_dias'] ?? 0, 1); ?> dias</td>
                                        <td>
                                            <?php 
                                            $taxaAtraso = $dados['total'] > 0 ? ($dados['atrasados'] / $dados['total']) * 100 : 0;
                                            $badgeClass = $taxaAtraso > 20 ? 'badge-danger' : ($taxaAtraso > 10 ? 'badge-warning' : 'badge-success');
                                            ?>
                                            <span class="badge-percent <?php echo $badgeClass; ?>">
                                                <?php echo number_format($taxaAtraso, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="acoes-relatorio">
                        <a href="#" class="btn-relatorio exportar" onclick="exportarRelatorio('ind47')">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </a>
                        <a href="#" class="btn-relatorio imprimir" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir Relatório
                        </a>
                    </div>
                </div>

                <?php endif; ?>

                <p class="copyright">© 2025 Embaquim - Tecnologia & Inovação</p>
            </section>
        </main>
    </div>

    <script>
        // Função para exportar relatório
        function exportarRelatorio(indicador) {
            // Encontrar o container do relatório correto
            const containers = document.querySelectorAll('.relatorio-container');
            let targetTable = null;
            
            containers.forEach(container => {
                const title = container.querySelector('.relatorio-title').textContent;
                if (title.includes(indicador.toUpperCase())) {
                    targetTable = container.querySelector('.tabela-relatorio');
                }
            });

            if (!targetTable) return;

            const html = targetTable.outerHTML;
            const finalHtml = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                    <title>Relatório ${indicador.toUpperCase()}</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                    </style>
                </head>
                <body>
                    <h2>Relatório Mensal - ${indicador.toUpperCase()}</h2>
                    <p>Data de exportação: ${new Date().toLocaleDateString('pt-BR')}</p>
                    ${html}
                </body>
                </html>`;

            const blob = new Blob([finalHtml], { type: 'application/vnd.ms-excel' });
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `relatorio_${indicador}_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}.xls`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Menu com submenus
        document.addEventListener('DOMContentLoaded', () => {
            const menuItemsWithSubmenu = document.querySelectorAll('.nav-item.has-submenu');

            menuItemsWithSubmenu.forEach(item => {
                const navLink = item.querySelector('.nav-link');
                
                navLink.addEventListener('click', (event) => {
                    if (navLink.getAttribute('href') === '#') {
                        event.preventDefault();
                    }

                    item.classList.toggle('open');
                    
                    document.querySelectorAll('.nav-item.active').forEach(activeItem => {
                        activeItem.classList.remove('active');
                    });
                    
                    menuItemsWithSubmenu.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('open')) {
                            otherItem.classList.remove('open');
                        }
                    });
                });
            });

            // Criar partículas dinâmicas
            const particlesContainer = document.getElementById('particles');
            const particleCount = 80;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                
                const size = Math.random() * 3 + 1;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                const delay = Math.random() * 15;
                particle.style.animationDelay = `${delay}s`;
                
                particlesContainer.appendChild(particle);
            }

            // Pesquisa em tempo real
            const searchInput = document.querySelector('.search-box input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const containers = document.querySelectorAll('.relatorio-container');
                    
                    containers.forEach(container => {
                        const title = container.querySelector('.relatorio-title').textContent.toLowerCase();
                        if (title.includes(searchTerm)) {
                            container.style.display = 'block';
                        } else {
                            container.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>