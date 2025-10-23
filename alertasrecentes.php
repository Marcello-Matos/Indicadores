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

// Carregar informações do usuário da sessão (conforme seu código de referência)
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_login = $_SESSION['usuario_login'] ?? '';
$usuario_departamento = $_SESSION['usuario_departamento'] ?? '';

// =========================================================================
// ATENÇÃO: LINHAS DE EXIBIÇÃO DE ERROS - ESSENCIAIS PARA DEPURAR
// Se a página ficar em branco, essas linhas forçarão a mensagem de erro.
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================================================================

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
    // Buscar dados das 3 tabelas
    function buscarDados($conn, $tabela) {
        $query = "SELECT TOP 50 * FROM $tabela ORDER BY 1 DESC";
        $stmt = sqlsrv_query($conn, $query);
        
        if ($stmt === false) {
            return array();
        }
        
        $dados = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $dados[] = $row;
        }
        
        return $dados;
    }

    // Função para gerar dados de gráficos baseados nos dados
    function gerarDadosGraficos($dados) {
        $graficos = [];
        
        if (empty($dados)) {
            return $graficos;
        }
        
        // Análise do StatusEntrega
        $statusCount = [];
        $clienteCount = [];
        $produtoCount = [];
        
        foreach ($dados as $row) {
            // StatusEntrega
            $status = $row['StatusEntrega'] ?? 'N/A';
            $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
            
            // Clientes
            $cliente = $row['Cliente'] ?? 'N/A';
            if (strlen($cliente) > 0 && $cliente != 'N/A') {
                $clienteCount[$cliente] = ($clienteCount[$cliente] ?? 0) + 1;
            }
            
            // Produtos
            $produto = $row['Produto'] ?? 'N/A';
            if (strlen($produto) > 0 && $produto != 'N/A') {
                $produtoCount[$produto] = ($produtoCount[$produto] ?? 0) + 1;
            }
        }
        
        // Ordenar e limitar para os top 10
        arsort($statusCount);
        arsort($clienteCount);
        arsort($produtoCount);
        
        $graficos['status'] = array_slice($statusCount, 0, 10, true);
        $graficos['clientes'] = array_slice($clienteCount, 0, 10, true);
        $graficos['produtos'] = array_slice($produtoCount, 0, 10, true);
        
        return $graficos;
    }

    // Buscar dados
    $dadosInd45 = buscarDados($conn, "vW_Ind45_Formatada");
    $dadosInd46 = buscarDados($conn, "vW_Ind46_Formatada");
    $dadosInd47 = buscarDados($conn, "vW_Ind47_Formatada");
    
    // Gerar dados para gráficos
    $graficosInd45 = gerarDadosGraficos($dadosInd45);
    $graficosInd46 = gerarDadosGraficos($dadosInd46);
    $graficosInd47 = gerarDadosGraficos($dadosInd47);
    
    // Fechar conexão
    sqlsrv_close($conn);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas Recentes | Embaquim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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
        /* LOGO HARMONIOSO - ALERTAS - COR BRANCA */
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

        /* Perfil do Usuário - COM BOLINHA DE STATUS ONLINE */
        .user-profile {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-profile:hover .user-dropdown {
            display: block;
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

        /* BOLINHA DE STATUS ONLINE - ADICIONADA CONFORME SEU CÓDIGO DE REFERÊNCIA */
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

        .user-name {
            font-size: 16px;
            font-weight: 600;
            background: linear-gradient(to right, #ffffff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Dropdown do usuário */
        .user-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--sidebar-bg);
            border: 1px solid var(--header-border);
            border-radius: 10px;
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .user-dropdown a:hover {
            background-color: var(--hover-bg);
            color: var(--accent-color);
        }

        .user-dropdown a i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }

        /* Área de Conteúdo Principal */
        .content-area {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Header da Página de Alertas */
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

        /* Container de Alertas */
        .alertas-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        /* Cards de Alerta */
        .alerta-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border-left: 6px solid var(--alerta-info);
        }

        .alerta-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.7s;
        }

        .alerta-card:hover::before {
            left: 100%;
        }

        .alerta-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        /* Tipos de Alerta */
        .alerta-card.critico {
            border-left-color: var(--alerta-critico);
        }

        .alerta-card.alto {
            border-left-color: var(--alerta-alto);
        }

        .alerta-card.medio {
            border-left-color: var(--alerta-medio);
        }

        .alerta-card.baixo {
            border-left-color: var(--alerta-baixo);
        }

        .alerta-card.info {
            border-left-color: var(--alerta-info);
        }

        .alerta-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .alerta-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-light);
            flex: 1;
        }

        .alerta-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 15px;
        }

        .badge-critico {
            background: var(--alerta-critico);
            color: white;
        }

        .badge-alto {
            background: var(--alerta-alto);
            color: white;
        }

        .badge-medio {
            background: var(--alerta-medio);
            color: #333;
        }

        .badge-baixo {
            background: var(--alerta-baixo);
            color: white;
        }

        .badge-info {
            background: var(--alerta-info);
            color: white;
        }

        .alerta-content {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .alerta-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        .alerta-origem {
            font-weight: 600;
        }

        .alerta-data {
            font-style: italic;
        }

        /* NOVOS ESTILOS PARA GRÁFICOS */
        .graficos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .grafico-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .grafico-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .grafico-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-light);
        }

        .grafico-controls {
            display: flex;
            gap: 10px;
        }

        .tipo-grafico-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 8px 12px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .tipo-grafico-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .tipo-grafico-btn.active {
            background: var(--accent-color);
            color: var(--dark-bg);
            border-color: var(--accent-color);
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
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
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Tabelas de Dados */
        .dados-container {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .dados-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .dados-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-light);
        }

        .dados-count {
            background: var(--accent-color);
            color: var(--dark-bg);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
        }

        .tabela-dados {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 100%;
            table-layout: fixed;
        }

        .tabela-dados th {
            background: rgba(0, 242, 254, 0.1);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--accent-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .tabela-dados td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: top;
        }

        .tabela-dados tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .tabela-dados thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        /* Garantir que as colunas tenham largura adequada */
        .tabela-dados th:nth-child(1),
        .tabela-dados td:nth-child(1) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(2),
        .tabela-dados td:nth-child(2) { width: 100px; min-width: 100px; }
        .tabela-dados th:nth-child(3),
        .tabela-dados td:nth-child(3) { width: 80px; min-width: 80px; }
        .tabela-dados th:nth-child(4),
        .tabela-dados td:nth-child(4) { width: 60px; min-width: 60px; }
        .tabela-dados th:nth-child(5),
        .tabela-dados td:nth-child(5) { width: 150px; min-width: 150px; }
        .tabela-dados th:nth-child(6),
        .tabela-dados td:nth-child(6) { width: 200px; min-width: 200px; }
        .tabela-dados th:nth-child(7),
        .tabela-dados td:nth-child(7) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(8),
        .tabela-dados td:nth-child(8) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(9),
        .tabela-dados td:nth-child(9) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(10),
        .tabela-dados td:nth-child(10) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(11),
        .tabela-dados td:nth-child(11) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(12),
        .tabela-dados td:nth-child(12) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(13),
        .tabela-dados td:nth-child(13) { width: 120px; min-width: 120px; }
        .tabela-dados th:nth-child(14),
        .tabela-dados td:nth-child(14) { width: 80px; min-width: 80px; }

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

            .alertas-container {
                grid-template-columns: 1fr;
            }

            .graficos-container {
                grid-template-columns: 1fr;
            }

            .content-area {
                padding: 20px;
            }

            /* Ajustes para tabelas em mobile */
            .tabela-dados {
                font-size: 12px;
            }
            
            .tabela-dados th,
            .tabela-dados td {
                padding: 8px 10px;
            }

            .grafico-controls {
                flex-direction: column;
                gap: 5px;
            }

            .tipo-grafico-btn {
                padding: 6px 10px;
                font-size: 11px;
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
                            <li class="submenu-item"><a href="relatoriosmensais.php">Relatórios Mensais</a></li>
                        </ul>
                    </li>

                    <li class="nav-item active">
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
                    <input type="text" placeholder="Pesquisar Alertas...">
                </div>

                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i> 
                        <!-- BOLINHA DE STATUS ONLINE ADICIONADA CONFORME SEU CÓDIGO DE REFERÊNCIA -->
                        <div class="online-status" title="Usuário Online"></div>
                    </div>
                    <!-- CORREÇÃO: AGORA MOSTRA O NOME REAL DO USUÁRIO LOGADO CONFORME SEU CÓDIGO DE REFERÊNCIA -->
                    <span class="user-name" id="userDisplayName"><?php echo htmlspecialchars($usuario_nome); ?></span>
                    <div class="user-dropdown">
                        <a href="meuperfil.php">
                            <i class="fas fa-user"></i>
                            Meu Perfil
                        </a>
                        <a href="configuracoes.php">
                            <i class="fas fa-cog"></i>
                            Configurações
                        </a>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            Sair
                        </a>
                    </div>
                </div>
            </header>

            <section class="content-area">
                
                <div class="page-header">
                    <h1 class="page-title">Alertas Recentes</h1>
                    <p class="page-description">Monitoramento em tempo real dos indicadores críticos do sistema</p>
                </div>

                <?php if (isset($erro_conexao)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Erro de Conexão</h3>
                    <p><?php echo $erro_conexao; ?></p>
                </div>
                <?php else: ?>

                <!-- Cards de Alertas -->
                <div class="alertas-container">
                    
                    <!-- Alerta Crítico -->
                    <div class="alerta-card critico">
                        <div class="alerta-header">
                            <h3 class="alerta-title">Atraso Crítico na Produção</h3>
                            <span class="alerta-badge badge-critico">Crítico</span>
                        </div>
                        <div class="alerta-content">
                            <p>Pedidos com atraso superior a 7 dias na linha de produção. Necessária intervenção imediata.</p>
                        </div>
                        <div class="alerta-meta">
                            <span class="alerta-origem">Indicador 45 - Produção</span>
                            <span class="alerta-data">Há 2 horas</span>
                        </div>
                    </div>

                    <!-- Alerta Alto -->
                    <div class="alerta-card alto">
                        <div class="alerta-header">
                            <h3 class="alerta-title">Qualidade Abaixo do Esperado</h3>
                            <span class="alerta-badge badge-alto">Alto</span>
                        </div>
                        <div class="alerta-content">
                            <p>Taxa de qualidade caiu para 85% - abaixo da meta mínima of 95%. Análise necessária.</p>
                        </div>
                        <div class="alerta-meta">
                            <span class="alerta-origem">Indicador 46 - Qualidade</span>
                            <span class="alerta-data">Há 4 horas</span>
                        </div>
                    </div>

                    <!-- Alerta Médio -->
                    <div class="alerta-card medio">
                        <div class="alerta-header">
                            <h3 class="alerta-title">Eficiência em Declínio</h3>
                            <span class="alerta-badge badge-medio">Médio</span>
                        </div>
                        <div class="alerta-content">
                            <p>Eficiência operacional caiu 8% nesta semana. Monitorar tendência.</p>
                        </div>
                        <div class="alerta-meta">
                            <span class="alerta-origem">Indicador 47 - Eficiência</span>
                            <span class="alerta-data">Hoje</span>
                        </div>
                    </div>

                    <!-- Alerta Baixo -->
                    <div class="alerta-card baixo">
                        <div class="alerta-header">
                            <h3 class="alerta-title">Manutenção Preventiva</h3>
                            <span class="alerta-badge badge-baixo">Baixo</span>
                        </div>
                        <div class="alerta-content">
                            <p>Equipamento #B23 necessita de manutenção preventiva agendada.</p>
                        </div>
                        <div class="alerta-meta">
                            <span class="alerta-origem">Indicador 45 - Manutenção</span>
                            <span class="alerta-data">Há 1 dia</span>
                        </div>
                    </div>

                    <!-- Alerta Info -->
                    <div class="alerta-card info">
                        <div class="alerta-header">
                            <h3 class="alerta-title">Meta Trimestral Atingida</h3>
                            <span class="alerta-badge badge-info">Positivo</span>
                        </div>
                        <div class="alerta-content">
                            <p>Meta de produtividade do trimestre foi superada em 12%. Excelente desempenho!</p>
                        </div>
                        <div class="alerta-meta">
                            <span class="alerta-origem">Indicador 46 - Produtividade</span>
                            <span class="alerta-data">Há 2 dias</span>
                        </div>
                    </div>

                </div>

                <!-- GRÁFICOS PARA INDICADOR 45 -->
                <div class="dados-container">
                    <div class="dados-header">
                        <h2 class="dados-title">Análise Gráfica - vW_Ind45_Formatada</h2>
                        <span class="dados-count"><?php echo count($dadosInd45); ?> registros</span>
                    </div>
                    
                    <div class="graficos-container">
                        <!-- Gráfico de Status -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Distribuição por Status</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartStatus45" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartStatus45" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartStatus45" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd45['status'])): ?>
                                    <canvas id="chartStatus45"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-chart-pie"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Gráfico de Clientes -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Top 10 Clientes</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartClientes45" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartClientes45" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartClientes45" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd45['clientes'])): ?>
                                    <canvas id="chartClientes45"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-users"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Gráfico de Produtos -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Top 10 Produtos</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartProdutos45" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartProdutos45" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartProdutos45" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd45['produtos'])): ?>
                                    <canvas id="chartProdutos45"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-box"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela com Dados do Indicador 45 -->
                <div class="dados-container">
                    <div class="dados-header">
                        <h2 class="dados-title">Dados - vW_Ind45_Formatada</h2>
                        <span class="dados-count"><?php echo count($dadosInd45); ?> registros</span>
                    </div>
                    <div class="table-responsive">
                        <table class="tabela-dados">
                            <thead>
                                <tr>
                                    <?php if (!empty($dadosInd45)): ?>
                                        <?php foreach (array_keys($dadosInd45[0]) as $coluna): ?>
                                            <th><?php echo htmlspecialchars($coluna); ?></th>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <th>Nenhum dado disponível</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($dadosInd45, 0, 10) as $linha): ?>
                                    <tr>
                                        <?php foreach ($linha as $valor): ?>
                                            <td><?php echo htmlspecialchars($valor); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- GRÁFICOS PARA INDICADOR 46 -->
                <div class="dados-container">
                    <div class="dados-header">
                        <h2 class="dados-title">Análise Gráfica - vW_Ind46_Formatada</h2>
                        <span class="dados-count"><?php echo count($dadosInd46); ?> registros</span>
                    </div>
                    
                    <div class="graficos-container">
                        <!-- Gráfico de Status -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Distribuição por Status</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartStatus46" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartStatus46" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartStatus46" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd46['status'])): ?>
                                    <canvas id="chartStatus46"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-chart-pie"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Gráfico de Clientes -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Top 10 Clientes</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartClientes46" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartClientes46" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartClientes46" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd46['clientes'])): ?>
                                    <canvas id="chartClientes46"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-users"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Gráfico de Produtos -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Top 10 Produtos</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartProdutos46" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartProdutos46" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartProdutos46" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd46['produtos'])): ?>
                                    <canvas id="chartProdutos46"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-box"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela com Dados do Indicador 46 -->
                <div class="dados-container">
                    <div class="dados-header">
                        <h2 class="dados-title">Dados - vW_Ind46_Formatada</h2>
                        <span class="dados-count"><?php echo count($dadosInd46); ?> registros</span>
                    </div>
                    <div class="table-responsive">
                        <table class="tabela-dados">
                            <thead>
                                <tr>
                                    <?php if (!empty($dadosInd46)): ?>
                                        <?php foreach (array_keys($dadosInd46[0]) as $coluna): ?>
                                            <th><?php echo htmlspecialchars($coluna); ?></th>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <th>Nenhum dado disponível</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($dadosInd46, 0, 10) as $linha): ?>
                                    <tr>
                                        <?php foreach ($linha as $valor): ?>
                                            <td><?php echo htmlspecialchars($valor); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- GRÁFICOS PARA INDICADOR 47 -->
                <div class="dados-container">
                    <div class="dados-header">
                        <h2 class="dados-title">Análise Gráfica - vW_Ind47_Formatada</h2>
                        <span class="dados-count"><?php echo count($dadosInd47); ?> registros</span>
                    </div>
                    
                    <div class="graficos-container">
                        <!-- Gráfico de Status -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Distribuição por Status</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartStatus47" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartStatus47" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartStatus47" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd47['status'])): ?>
                                    <canvas id="chartStatus47"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-chart-pie"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Gráfico de Clientes -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Top 10 Clientes</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartClientes47" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartClientes47" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartClientes47" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd47['clientes'])): ?>
                                    <canvas id="chartClientes47"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-users"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Gráfico de Produtos -->
                        <div class="grafico-card">
                            <div class="grafico-header">
                                <h3 class="grafico-title">Top 10 Produtos</h3>
                                <div class="grafico-controls">
                                    <button class="tipo-grafico-btn active" data-chart="chartProdutos47" data-type="pie">
                                        <i class="fas fa-chart-pie"></i> Pizza
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartProdutos47" data-type="bar">
                                        <i class="fas fa-chart-bar"></i> Barras
                                    </button>
                                    <button class="tipo-grafico-btn" data-chart="chartProdutos47" data-type="horizontalBar">
                                        <i class="fas fa-chart-bar"></i> Horizontal
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <?php if (!empty($graficosInd47['produtos'])): ?>
                                    <canvas id="chartProdutos47"></canvas>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-box"></i>
                                        <p>Nenhum dado disponível para gráfico</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela com Dados do Indicador 47 -->
                <div class="dados-container">
                    <div class="dados-header">
                        <h2 class="dados-title">Dados - vW_Ind47_Formatada</h2>
                        <span class="dados-count"><?php echo count($dadosInd47); ?> registros</span>
                    </div>
                    <div class="table-responsive">
                        <table class="tabela-dados">
                            <thead>
                                <tr>
                                    <?php if (!empty($dadosInd47)): ?>
                                        <?php foreach (array_keys($dadosInd47[0]) as $coluna): ?>
                                            <th><?php echo htmlspecialchars($coluna); ?></th>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <th>Nenhum dado disponível</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($dadosInd47, 0, 10) as $linha): ?>
                                    <tr>
                                        <?php foreach ($linha as $valor): ?>
                                            <td><?php echo htmlspecialchars($valor); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php endif; ?>

                <p class="copyright">© 2025 Embaquim - Tecnologia & Inovação</p>
            </section>
        </main>
    </div>

    <script>
        // Dados para os gráficos (passados do PHP)
        const graficosData = {
            ind45: {
                status: <?php echo json_encode($graficosInd45['status'] ?? []); ?>,
                clientes: <?php echo json_encode($graficosInd45['clientes'] ?? []); ?>,
                produtos: <?php echo json_encode($graficosInd45['produtos'] ?? []); ?>
            },
            ind46: {
                status: <?php echo json_encode($graficosInd46['status'] ?? []); ?>,
                clientes: <?php echo json_encode($graficosInd46['clientes'] ?? []); ?>,
                produtos: <?php echo json_encode($graficosInd46['produtos'] ?? []); ?>
            },
            ind47: {
                status: <?php echo json_encode($graficosInd47['status'] ?? []); ?>,
                clientes: <?php echo json_encode($graficosInd47['clientes'] ?? []); ?>,
                produtos: <?php echo json_encode($graficosInd47['produtos'] ?? []); ?>
            }
        };

        // Cores para os gráficos
        const chartColors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#64748b',
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'
        ];

        // Configuração padrão do Chart.js
        Chart.defaults.color = '#e2e8f0';
        Chart.defaults.font.family = 'Montserrat, sans-serif';

        // Objetos para armazenar as instâncias dos gráficos
        const chartInstances = {};

        // Função para criar gráfico
        function criarGrafico(canvasId, dados, tipo = 'pie') {
            const ctx = document.getElementById(canvasId).getContext('2d');
            const labels = Object.keys(dados);
            const values = Object.values(dados);

            // Destruir gráfico existente se houver
            if (chartInstances[canvasId]) {
                chartInstances[canvasId].destroy();
            }

            let config = {
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: chartColors.slice(0, labels.length),
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#e2e8f0',
                                font: {
                                    family: 'Montserrat'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 15, 35, 0.9)',
                            titleColor: '#e2e8f0',
                            bodyColor: '#e2e8f0',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1
                        }
                    }
                }
            };

            // Configurações específicas por tipo
            switch(tipo) {
                case 'pie':
                    config.type = 'pie';
                    break;
                case 'bar':
                    config.type = 'bar';
                    config.options.indexAxis = 'x';
                    config.options.scales = {
                        x: {
                            ticks: { color: '#e2e8f0' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        y: {
                            ticks: { color: '#e2e8f0' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            beginAtZero: true
                        }
                    };
                    break;
                case 'horizontalBar':
                    config.type = 'bar';
                    config.options.indexAxis = 'y';
                    config.options.scales = {
                        x: {
                            ticks: { color: '#e2e8f0' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' },
                            beginAtZero: true
                        },
                        y: {
                            ticks: { color: '#e2e8f0' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    };
                    break;
            }

            chartInstances[canvasId] = new Chart(ctx, config);
        }

        // Função para determinar qual dados usar baseado no ID do canvas
        function obterDadosPorCanvasId(canvasId) {
            const parts = canvasId.replace('chart', '').toLowerCase();
            if (parts.includes('45')) {
                if (parts.includes('status')) return graficosData.ind45.status;
                if (parts.includes('clientes')) return graficosData.ind45.clientes;
                if (parts.includes('produtos')) return graficosData.ind45.produtos;
            }
            if (parts.includes('46')) {
                if (parts.includes('status')) return graficosData.ind46.status;
                if (parts.includes('clientes')) return graficosData.ind46.clientes;
                if (parts.includes('produtos')) return graficosData.ind46.produtos;
            }
            if (parts.includes('47')) {
                if (parts.includes('status')) return graficosData.ind47.status;
                if (parts.includes('clientes')) return graficosData.ind47.clientes;
                if (parts.includes('produtos')) return graficosData.ind47.produtos;
            }
            return {};
        }

        // Inicializar gráficos quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar todos os gráficos com tipo pizza
            const botoesAtivos = document.querySelectorAll('.tipo-grafico-btn.active');
            botoesAtivos.forEach(botao => {
                const canvasId = botao.dataset.chart;
                const dados = obterDadosPorCanvasId(canvasId);
                if (Object.keys(dados).length > 0) {
                    criarGrafico(canvasId, dados, 'pie');
                }
            });

            // Event listeners para os botões de tipo de gráfico
            document.querySelectorAll('.tipo-grafico-btn').forEach(botao => {
                botao.addEventListener('click', function() {
                    const canvasId = this.dataset.chart;
                    const tipo = this.dataset.type;
                    
                    // Atualizar estado ativo dos botões
                    this.parentElement.querySelectorAll('.tipo-grafico-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Atualizar gráfico
                    const dados = obterDadosPorCanvasId(canvasId);
                    if (Object.keys(dados).length > 0) {
                        criarGrafico(canvasId, dados, tipo);
                    }
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
            
            // Menu com submenus
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
            
            // Efeitos nos cards de alerta
            const alertaCards = document.querySelectorAll('.alerta-card');
            alertaCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Pesquisa em tempo real
            const searchInput = document.querySelector('.search-box input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const cards = document.querySelectorAll('.alerta-card');
                    
                    cards.forEach(card => {
                        const title = card.querySelector('.alerta-title').textContent.toLowerCase();
                        const content = card.querySelector('.alerta-content').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || content.includes(searchTerm)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Dropdown do usuário
            const userProfile = document.querySelector('.user-profile');
            if (userProfile) {
                userProfile.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const dropdown = this.querySelector('.user-dropdown');
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                });

                // Fechar dropdown ao clicar fora
                document.addEventListener('click', function() {
                    const dropdowns = document.querySelectorAll('.user-dropdown');
                    dropdowns.forEach(dropdown => {
                        dropdown.style.display = 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>