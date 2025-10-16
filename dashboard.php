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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores | Embaquim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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

        /* Logo */
        .logo-section {
            display: flex;
            align-items: center;
            padding: 25px 20px;
            height: 80px;
            border-bottom: 1px solid var(--header-border);
            background: linear-gradient(90deg, rgba(106, 17, 203, 0.3), rgba(37, 117, 252, 0.3));
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.8s;
        }

        .logo-section:hover::before {
            left: 100%;
        }

        .logo-icon {
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
            margin-right: 10px;
            background: linear-gradient(to bottom, var(--accent-color), #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 5px var(--glow-color));
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(to right, #ffffff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
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
        
        /* Ajuste para o submenu, para que o hover no <a> funcione corretamente */
        .nav-item.has-submenu > .nav-link:hover {
            padding-left: 20px; /* Mantém o padding original para o item pai de submenu */
        }
        
        .nav-item.has-submenu > .nav-link {
            cursor: pointer; /* Garante que o cursor de clique apareça */
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
        
        /* Ajuste para o item pai do submenu ativo */
        .nav-item.active.has-submenu > .nav-link {
             padding-left: 20px; /* Remove o padding extra que interfere no ícone de seta */
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
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            align-content: start;
        }

        /* Cards de Conteúdo - AGORA CLICÁVEIS */
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer; /* Muda o cursor para indicar que é clicável */
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.7s;
        }

        .dashboard-card:hover::before {
            left: 100%;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: rgba(0, 242, 254, 0.3);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-light);
        }

        /* Estilo do Link (<a>) no Card Header */
        .card-header a {
            text-decoration: none; /* Remove sublinhado padrão do link */
            color: inherit;        /* Garante que a cor do ícone seja mantida ou herdada */
            display: flex; /* Para centralizar o ícone se ele tiver margens */
        }
        .card-header a:hover {
            opacity: 0.9;
        }

        /* Estilo do Ícone (<i>) */
        .card-icon {
            font-size: 24px;
            color: var(--accent-color);
            filter: drop-shadow(0 0 5px var(--glow-color));
            cursor: pointer; /* Garante que o cursor mude para a mãozinha */
            transition: color 0.2s, transform 0.2s;  
        }

        /* Opcional: Efeito visual ao passar o mouse */
        .card-header a:hover .card-icon {
            color: #ffffff; /* Mudar a cor ao passar o mouse */
            transform: scale(1.1); /* Um pequeno zoom */
        }
        
        .card-content {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        .card-chart {
            height: 120px;
            margin-top: 15px;
            background: linear-gradient(90deg, rgba(0, 242, 254, 0.1), rgba(106, 17, 203, 0.1));
            border-radius: 8px;
            display: flex;
            align-items: flex-end;
            padding: 10px;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, var(--accent-color), var(--primary-gradient-start));
            margin: 0 2px;
            border-radius: 2px 2px 0 0;
            animation: growBar 1.5s ease-out;
        }

        @keyframes growBar {
            from { height: 0; }
        }

        .copyright {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            margin-top: 40px;
            grid-column: 1 / -1;
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
                grid-template-columns: 1fr;
                padding: 20px;
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
                <i class="logo-icon">E</i>
                <span class="logo-text">embaquim</span>
            </div>

            <nav class="nav-menu">
                <ul>
                    <li class="nav-item active">
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
                            <li class="submenu-item"><a href="indicadores45.php">Indicadores Ativos</a></li>
                            <li class="submenu-item"><a href="metasdotrimestre.php">Meta do Trimestre</a></li>
                            <li class="submenu-item"><a href="alertasrecentes.php">Alertas Recentes</a></li>
                            <li class="submenu-item"><a href="desempenhodaequipe.php">Desempenho da Equipe</a></li>
                            <li class="submenu-item"><a href="relatoriosmensais.php">Relatórios Mensais</a></li>
                        </ul>
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
                    <input type="text" placeholder="Pesquisar Indicador">
                </div>

                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i> 
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($usuario_nome); ?></span>
                </div>
            </header>

            <section class="content-area">
                
                <div class="dashboard-card" onclick="window.location.href='visaogeral.php'">
                    <div class="card-header">
                        <h3 class="card-title">Visão Geral</h3>
                        <a href="visaogeral.php"> 
                            <i class="fas fa-chart-line card-icon"></i>
                        </a>
                    </div>
                    <div class="card-content">
                        <p>Resumo completo dos principais indicadores de desempenho.</p>
                        <div class="card-chart">
                            <div class="chart-bar" style="height: 70%"></div>
                            <div class="chart-bar" style="height: 85%"></div>
                            <div class="chart-bar" style="height: 60%"></div>
                            <div class="chart-bar" style="height: 90%"></div>
                            <div class="chart-bar" style="height: 75%"></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="window.location.href='indicadores45.php'">
                    <div class="card-header">
                        <h3 class="card-title">Indicadores Ativos 45</h3>
                        <a href="indicadores45.php">
                            <i class="fas fa-chart-pie card-icon"></i>
                        </a>
                    </div>
                    <div class="card-content">
                        <p>Total de 24 indicadores em monitoramento ativo.</p>
                        <div class="card-chart">
                            <div class="chart-bar" style="height: 50%"></div>
                            <div class="chart-bar" style="height: 80%"></div>
                            <div class="chart-bar" style="height: 65%"></div>
                            <div class="chart-bar" style="height: 45%"></div>
                            <div class="chart-bar" style="height: 95%"></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="window.location.href='indicadores46.php'">
                    <div class="card-header">
                        <h3 class="card-title">Indicadores Ativos 46</h3>
                        <a href="indicadores46.php">
                            <i class="fas fa-chart-pie card-icon"></i>
                        </a>
                    </div>
                    <div class="card-content">
                        <p>Total de 24 indicadores em monitoramento ativo.</p>
                        <div class="card-chart">
                            <div class="chart-bar" style="height: 50%"></div>
                            <div class="chart-bar" style="height: 80%"></div>
                            <div class="chart-bar" style="height: 65%"></div>
                            <div class="chart-bar" style="height: 45%"></div>
                            <div class="chart-bar" style="height: 95%"></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="window.location.href='indicadores47.php'">
                    <div class="card-header">
                        <h3 class="card-title">Indicadores Ativos 47</h3>
                        <a href="indicadores47.php">
                            <i class="fas fa-chart-pie card-icon"></i>
                        </a>
                    </div>
                    <div class="card-content">
                        <p>Total de 24 indicadores em monitoramento ativo.</p>
                        <div class="card-chart">
                            <div class="chart-bar" style="height: 50%"></div>
                            <div class="chart-bar" style="height: 80%"></div>
                            <div class="chart-bar" style="height: 65%"></div>
                            <div class="chart-bar" style="height: 45%"></div>
                            <div class="chart-bar" style="height: 95%"></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="window.location.href='alertasrecentes.php'">
                    <div class="card-header">
                        <h3 class="card-title">Alertas Recentes</h3>
                        <a href="alertasrecentes.php">
                            <i class="fas fa-exclamation-triangle card-icon"></i>
                        </a>
                    </div>
                    <div class="card-content">
                        <p>3 alertas necessitam de sua atenção imediata.</p>
                        <div class="card-chart">
                            <div class="chart-bar" style="height: 30%"></div>
                            <div class="chart-bar" style="height: 50%"></div>
                            <div class="chart-bar" style="height: 20%"></div>
                            <div class="chart-bar" style="height: 40%"></div>
                            <div class="chart-bar" style="height: 10%"></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="window.location.href='relatoriosmensais.php'">
                    <div class="card-header">
                        <h3 class="card-title">Relatórios Mensais</h3>
                        <a href="relatoriosmensais.php">
                            <i class="fas fa-file-alt card-icon"></i>
                        </a>
                    </div>
                    <div class="card-content">
                        <p>5 relatórios gerados este mês com análises detalhadas.</p>
                        <div class="card-chart">
                            <div class="chart-bar" style="height: 60%"></div>
                            <div class="chart-bar" style="height: 75%"></div>
                            <div class="chart-bar" style="height: 50%"></div>
                            <div class="chart-bar" style="height: 80%"></div>
                            <div class="chart-bar" style="height: 65%"></div>
                        </div>
                    </div>
                </div>
                
                <p class="copyright">© 2025 Embaquim - Tecnologia & Inovação</p>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Criar partículas dinâmicas
            const particlesContainer = document.getElementById('particles');
            const particleCount = 80;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Posição aleatória
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                
                // Tamanho aleatório
                const size = Math.random() * 3 + 1;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Atraso aleatório na animação
                const delay = Math.random() * 15;
                particle.style.animationDelay = `${delay}s`;
                
                particlesContainer.appendChild(particle);
            }
            
            // Seleciona todos os itens do menu que possuem um submenu
            const menuItemsWithSubmenu = document.querySelectorAll('.nav-item.has-submenu');

            menuItemsWithSubmenu.forEach(item => {
                const navLink = item.querySelector('.nav-link');
                
                // Adiciona um ouvinte de evento de clique APENAS no link principal (Indicadores)
                navLink.addEventListener('click', (event) => {
                    // Previne o comportamento padrão do link para que ele não navegue, mas abra o submenu
                    if (navLink.getAttribute('href') === '#') {
                        event.preventDefault();
                    }

                    // Alterna a classe 'open' no item clicado (item pai)
                    item.classList.toggle('open');
                    
                    // Remove a classe 'active' de todos os itens principais (para não ficar marcado)
                    document.querySelectorAll('.nav-item.active').forEach(activeItem => {
                        activeItem.classList.remove('active');
                    });
                    
                    // Fecha outros submenus abertos
                    menuItemsWithSubmenu.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('open')) {
                            otherItem.classList.remove('open');
                        }
                    });
                });
            });
            
            // Adicionar um efeito de "active" ao clicar nos itens do menu
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.addEventListener('click', (e) => {
                    // Se for um item do submenu (que tem um href real), tratamos o 'active'
                    if (link.closest('.submenu')) {
                        // Não remove 'active' dos pais por enquanto, apenas deixa o link do submenu funcionar
                        // Para links de submenu, podemos adicionar uma lógica de 'active' mais específica se necessário
                        // Mas para o seu objetivo principal (clicar e navegar), não é necessário mudar nada aqui.
                        return;
                    }
                    
                    // Permite a navegação se não for um link pai de submenu que use '#'
                    if (link.closest('.has-submenu') && link.getAttribute('href') === '#') {
                        // Não faz nada além do que foi feito no listener acima (toggle 'open')
                        e.preventDefault();
                        return;
                    }
                    
                    // Remove 'active' de todos os itens principais primeiro
                    document.querySelectorAll('.nav-item.active').forEach(activeItem => {
                        activeItem.classList.remove('active');
                    });
                    
                    // Adiciona 'active' ao pai (se for um item principal sem submenu, ou se for o item pai do submenu mas com href real)
                    if(link.closest('.nav-item')) {
                        link.closest('.nav-item').classList.add('active');
                    }
                });
            });
            
            // Efeito de digitação no campo de pesquisa
            const searchInput = document.querySelector('.search-box input');
            
            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    this.parentElement.style.borderColor = 'var(--accent-color)';
                    this.parentElement.style.boxShadow = '0 0 10px var(--glow-color)';
                });
                
                searchInput.addEventListener('blur', function() {
                    this.parentElement.style.borderColor = '';
                    this.parentElement.style.boxShadow = '';
                });
            }
            
            // Efeito de animação nos cards ao carregar a página
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100); // Adiciona um atraso escalonado para um efeito em cascata
            });

            // Adicionar evento de clique para os cards (backup)
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    // Encontra o link dentro do card para redirecionar
                    const link = this.querySelector('a');
                    if (link && link.href) {
                        // Usa location.assign() para melhor controle de histórico (se necessário) ou window.location.href (simples)
                        window.location.href = link.href;
                    }
                });
            });
        });
    </script>
</body>
</html>