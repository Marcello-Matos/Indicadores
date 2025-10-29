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
// ATENÇÃO: LINHAS DE EXIBIÇÃO DE ERROS - ESSENCIAIS PARA DEPURAR
// Se a página ficar em branco, essas linhas forçarão a mensagem de erro.
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores | Embaquim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="img/favicon.png" type="image/x-icon">
    <style>
        :root {
            --primary-gradient-start: #6a11cb;
            --primary-gradient-end: #2575fc;
            --secondary-gradient-start: #ff6b6b;
            --secondary-gradient-end: #ffa726;
            --success-color: #00d9a6;
            --warning-color: #ffcc00;
            --danger-color: #ff4757;
            --accent-color: #00f2fe;
            --dark-bg: #0f0f23;
            --sidebar-bg: rgba(34, 0, 83, 0.95);
            --active-item-bg: rgba(64, 0, 176, 0.8);
            --hover-bg: rgba(50, 0, 126, 0.7);
            --text-light: #f0f0f0;
            --text-muted: rgba(255, 255, 255, 0.7);
            --header-border: rgba(255, 255, 255, 0.1);
            --content-bg: rgba(15, 15, 35, 0.4);
            --search-bg: rgba(255, 255, 255, 0.1);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.2);
            --shadow-medium: 0 5px 15px rgba(0, 0, 0, 0.3);
            --shadow-heavy: 0 10px 25px rgba(0, 0, 0, 0.4);
            --card-bg: rgba(255, 255, 255, 0.08);
            --card-hover-bg: rgba(255, 255, 255, 0.15);
            --glow-color: rgba(0, 242, 254, 0.5);
            --border-radius: 16px;
            --transition-normal: 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --transition-slow: 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
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
            font-weight: 500;
        }

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

        /* Floating Particles */
        .floating-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 50%;
            animation: floatParticle 20s infinite linear;
            opacity: 0;
        }

        @keyframes floatParticle {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.7; }
            90% { opacity: 0.7; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }

        .app-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            grid-template-rows: 100vh;
            backdrop-filter: blur(10px);
        }

        /* Enhanced Sidebar */
        .sidebar {
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            padding: 0;
            overflow-y: auto;
            border-right: 1px solid var(--header-border);
            backdrop-filter: blur(15px);
            box-shadow: 8px 0 25px rgba(0, 0, 0, 0.4);
            z-index: 10;
            position: relative;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, transparent 0%, rgba(106, 17, 203, 0.1) 100%);
            pointer-events: none;
        }

        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            height: 90px;
            border-bottom: 1px solid var(--header-border);
            background: linear-gradient(90deg, rgba(106, 17, 203, 0.3), rgba(37, 117, 252, 0.3));
            position: relative;
            overflow: hidden;
        }

        .logo-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent-color), transparent);
        }

        .logo-image {
            width: auto;
            height: 50px;
            object-fit: contain;
            filter: brightness(0) invert(1) drop-shadow(0 0 15px rgba(255, 255, 255, 0.4));
            transition: all var(--transition-normal);
        }

        .logo-section:hover .logo-image {
            transform: scale(1.08) rotate(2deg);
            filter: brightness(0) invert(1) drop-shadow(0 0 20px rgba(255, 255, 255, 0.6));
        }

        .nav-menu {
            padding: 25px 0;
            flex-grow: 1;
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
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: var(--text-light);
            text-decoration: none;
            padding: 18px 25px;
            transition: all var(--transition-normal);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background-color: var(--hover-bg);
            padding-left: 30px;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 26px;
            text-align: center;
            margin-right: 15px;
            font-size: 20px;
            transition: all var(--transition-normal);
            position: relative;
            z-index: 2;
        }

        .nav-link:hover i {
            transform: scale(1.3) rotate(5deg);
            color: var(--accent-color);
            text-shadow: 0 0 10px var(--glow-color);
        }

        .nav-item.active .nav-link {
            background-color: var(--active-item-bg);
            font-weight: 700;
            box-shadow: 6px 0 0 0 var(--accent-color) inset;
            transform: translateX(5px);
        }

        .nav-item.active .nav-link i {
            color: var(--accent-color);
            transform: scale(1.2);
            text-shadow: 0 0 15px var(--glow-color);
        }

        /* Enhanced Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            background-color: var(--content-bg);
            overflow: hidden;
            position: relative;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(106, 17, 203, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(37, 117, 252, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 35px;
            height: 90px;
            background: rgba(15, 15, 35, 0.6);
            border-bottom: 1px solid var(--header-border);
            backdrop-filter: blur(15px);
            box-shadow: var(--shadow-light);
            position: relative;
            z-index: 5;
        }

        .search-box {
            display: flex;
            align-items: center;
            background-color: var(--search-bg);
            border: 1px solid var(--header-border);
            border-radius: 12px;
            padding: 12px 18px;
            width: 380px;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .search-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 242, 254, 0.1), transparent);
            transition: left 0.6s;
        }

        .search-box:focus-within {
            border-color: var(--accent-color);
            box-shadow: 0 0 20px var(--glow-color);
            transform: translateY(-2px);
        }

        .search-box:focus-within::before {
            left: 100%;
        }

        .search-box i {
            color: var(--text-light);
            margin-right: 12px;
            font-size: 18px;
            transition: all var(--transition-normal);
        }

        .search-box:focus-within i {
            color: var(--accent-color);
            transform: scale(1.2);
        }

        .search-box input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 16px;
            flex-grow: 1;
            padding: 2px 0;
            color: var(--text-light);
            font-weight: 500;
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding: 10px 18px;
            border-radius: 12px;
            transition: all var(--transition-normal);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .user-profile::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.6s;
        }

        .user-profile:hover::before {
            left: 100%;
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .avatar {
            position: relative;
            margin-right: 15px;
        }

        .avatar i {
            font-size: 40px;
            color: var(--accent-color);
            transition: all var(--transition-normal);
            filter: drop-shadow(0 0 8px var(--glow-color));
        }

        .user-profile:hover .avatar i {
            transform: scale(1.1) rotate(5deg);
            filter: drop-shadow(0 0 12px var(--glow-color));
        }

        /* BOLINHA DE STATUS ONLINE - NOVA ADIÇÃO */
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
            font-size: 17px;
            font-weight: 600;
            background: linear-gradient(to right, #ffffff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all var(--transition-normal);
        }

        .user-profile:hover .user-name {
            background: linear-gradient(to right, var(--accent-color), #ffffff);
            -webkit-background-clip: text;
        }

        /* Premium Cards */
        .content-area {
            flex-grow: 1;
            padding: 35px;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
            align-content: start;
            position: relative;
            z-index: 2;
        }

        .dashboard-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: var(--shadow-medium);
            transition: all var(--transition-normal);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.03), transparent);
            transition: left 0.8s;
        }

        .dashboard-card:hover::before {
            left: 100%;
        }

        .dashboard-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, transparent 0%, rgba(0, 242, 254, 0.03) 100%);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .dashboard-card:hover::after {
            opacity: 1;
        }

        .dashboard-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-heavy), 0 0 30px rgba(0, 242, 254, 0.2);
            border-color: rgba(0, 242, 254, 0.4);
            background: var(--card-hover-bg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-light);
            line-height: 1.3;
            transition: all var(--transition-normal);
        }

        .dashboard-card:hover .card-title {
            background: linear-gradient(to right, #ffffff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-icon-container {
            position: relative;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon {
            font-size: 28px;
            color: var(--accent-color);
            transition: all var(--transition-normal);
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 0 8px var(--glow-color));
        }

        .card-icon-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            background: rgba(0, 242, 254, 0.1);
            border-radius: 50%;
            opacity: 0;
            transition: all var(--transition-normal);
        }

        .dashboard-card:hover .card-icon {
            transform: scale(1.3) rotate(10deg);
            filter: drop-shadow(0 0 15px var(--glow-color));
        }

        .dashboard-card:hover .card-icon-bg {
            opacity: 1;
            width: 60px;
            height: 60px;
        }

        .card-content {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
            flex-grow: 1;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            transition: all var(--transition-normal);
        }

        .dashboard-card:hover .card-content {
            color: rgba(255, 255, 255, 0.9);
        }

        .card-stats {
            display: flex;
            align-items: center;
            margin-top: 15px;
            font-weight: 800;
            font-size: 24px;
            color: var(--text-light);
            position: relative;
            z-index: 2;
            transition: all var(--transition-normal);
        }

        .dashboard-card:hover .card-stats {
            transform: translateX(5px);
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 12px;
            position: relative;
            transition: all var(--transition-normal);
        }

        .status-indicator::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .dashboard-card:hover .status-indicator::after {
            opacity: 0.6;
        }

        .status-success {
            background-color: var(--success-color);
            box-shadow: 0 0 8px var(--success-color);
        }

        .status-success::after {
            background: var(--success-color);
        }

        .status-warning {
            background-color: var(--warning-color);
            box-shadow: 0 0 8px var(--warning-color);
        }

        .status-warning::after {
            background: var(--warning-color);
        }

        .status-danger {
            background-color: var(--danger-color);
            box-shadow: 0 0 8px var(--danger-color);
        }

        .status-danger::after {
            background: var(--danger-color);
        }

        .copyright {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            margin-top: 50px;
            grid-column: 1 / -1;
            position: relative;
            z-index: 2;
        }

        /* Enhanced Animations */
        @keyframes cardPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: cardPulse 2s infinite;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 0 10px var(--danger-color);
            animation: pulse 1.5s infinite;
            z-index: 3;
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .app-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: -280px;
                width: 280px;
                z-index: 1000;
                transition: left var(--transition-normal);
            }

            .sidebar.active {
                left: 0;
            }

            .top-header {
                padding: 15px 20px;
            }

            .search-box {
                width: 250px;
            }

            .content-area {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            /* Ajustes responsivos para a bolinha */
            .online-status {
                width: 10px;
                height: 10px;
                bottom: 1px;
                right: 1px;
            }
        }

        /* ESTILOS PARA SUBMENU */
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            background: rgba(0, 0, 0, 0.2);
        }

        .submenu.active {
            max-height: 800px;
        }

        .submenu .nav-link {
            padding-left: 60px !important;
            font-size: 14px;
            padding-top: 14px;
            padding-bottom: 14px;
        }

        .submenu .nav-link:hover {
            padding-left: 65px !important;
        }

        .nav-item.has-submenu > .nav-link {
            position: relative;
        }

        .nav-item.has-submenu > .nav-link::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 25px;
            transition: transform 0.3s ease;
        }

        .nav-item.has-submenu.active > .nav-link::after {
            transform: rotate(180deg);
        }

        .submenu .nav-link i {
            font-size: 16px;
            width: 20px;
        }
    </style>
</head>
<body>
    <div class="background-animation">
        <div class="gradient-bg"></div>
    </div>

    <div class="floating-particles" id="floatingParticles"></div>

    <div class="app-layout">
        
        <aside class="sidebar">
            <div class="logo-section">
                <img src="img/logo2025.png" alt="Logo Embaquim 2025" class="logo-image">
            </div>

            <nav class="nav-menu">
                <ul>
                    <li class="nav-item active">
                        <a href="dashboard.php" class="nav-link">
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

                   <!-- MENU INDICADORES COM 14 SUBMENUS -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span>Indicadores</span>
                        </a>
                        <ul class="submenu">
                            <!-- SUBMENU 1 -->
                            <li class="nav-item">
                                <a href="visaogeral.php" class="nav-link">
                                    <i class="fas fa-eye"></i>
                                    <span>Visão Geral</span>
                                </a>
                            </li>

                            <!-- SUBMENU 2 -->
                            <li class="nav-item">
                                <a href="indicadores45.php" class="nav-link">
                                    <i class="fas fa-chart-pie"></i>
                                    <span>Indicadores Ativos 45</span>
                                </a>
                            </li>

                            <!-- SUBMENU 3 -->
                            <li class="nav-item">
                                <a href="indicadores46.php" class="nav-link">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Indicadores Ativos 46</span>
                                </a>
                            </li>

                            <!-- SUBMENU 4 -->
                            <li class="nav-item">
                                <a href="indicadores47.php" class="nav-link">
                                    <i class="fas fa-chart-area"></i>
                                    <span>Indicadores Ativos 47</span>
                                </a>
                            </li>

                            <!-- SUBMENU 5 -->
                            <li class="nav-item">
                                <a href="compras.php" class="nav-link">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Compras</span>
                                </a>
                            </li>

                            <!-- SUBMENU 6 -->
                            <li class="nav-item">
                                <a href="pcp.php" class="nav-link">
                                    <i class="fas fa-tasks"></i>
                                    <span>P.C.P</span>
                                </a>
                            </li>

                            <!-- SUBMENU 7 -->
                            <li class="nav-item">
                                <a href="financeiro.php" class="nav-link">
                                    <i class="fas fa-dollar-sign"></i>
                                    <span>Financeiro</span>
                                </a>
                            </li>

                            <!-- SUBMENU 8 -->
                            <li class="nav-item">
                                <a href="qualidade.php" class="nav-link">
                                    <i class="fas fa-award"></i>
                                    <span>Qualidade</span>
                                </a>
                            </li>

                            <!-- SUBMENU 9 -->
                            <li class="nav-item">
                                <a href="relatoriosmensais.php" class="nav-link">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Relatórios Mensais</span>
                                </a>
                            </li>

                            <!-- SUBMENU 10 -->
                            <li class="nav-item">
                                <a href="materiais.php" class="nav-link">
                                    <i class="fas fa-truck"></i>
                                    <span>Materiais e Logística</span>
                                </a>
                            </li>

                            <!-- SUBMENU 11 -->
                            <li class="nav-item">
                                <a href="tecnologiainformacao.php" class="nav-link">
                                    <i class="fas fa-laptop-code"></i>
                                    <span>Tecnologia da Informação</span>
                                </a>
                            </li>

                            <!-- SUBMENU 12 -->
                            <li class="nav-item">
                                <a href="desenvolvimento.php" class="nav-link">
                                    <i class="fas fa-code-branch"></i>
                                    <span>Desenvolvimento</span>
                                </a>
                            </li>

                            <!-- SUBMENU 13 -->
                            <li class="nav-item">
                                <a href="operacoes.php" class="nav-link">
                                    <i class="fas fa-cogs"></i>
                                    <span>Operações</span>
                                </a>
                            </li>

                            <!-- SUBMENU 14 -->
                            <li class="nav-item">
                                <a href="vendas.php" class="nav-link">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Vendas</span>
                                </a>
                            </li>
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
                    <input type="text" placeholder="Pesquisar Indicador...">
                </div>

                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i> 
                        <!-- BOLINHA DE STATUS ONLINE ADICIONADA AQUI -->
                        <div class="online-status" title="Usuário Online"></div>
                    </div>
                    <!-- CORREÇÃO AQUI: AGORA MOSTRA O NOME REAL DO USUÁRIO LOGADO -->
                    <span class="user-name" id="userDisplayName"><?php echo htmlspecialchars($usuario_nome); ?></span>
                </div>
            </header>

            <section class="content-area">
                
                <div class="dashboard-card" onclick="navigateTo('visaogeral.php')">
                    <div class="card-header">
                        <h3 class="card-title">Visão Geral</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-chart-line card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('indicadores45.php')">
                    <div class="card-header">
                        <h3 class="card-title">Indicadores Ativos 45</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-chart-pie card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('indicadores46.php')">
                    <div class="card-header">
                        <h3 class="card-title">Indicadores Ativos 46</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-chart-pie card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('indicadores47.php')">
                    <div class="card-header">
                        <h3 class="card-title">Indicadores Ativos 47</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-chart-pie card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <!-- CARD INDICADORES RESTAURADO -->
                <div class="dashboard-card" onclick="navigateTo('indicadores.php')">
                    <div class="card-header">
                        <h3 class="card-title">Indicadores</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-chart-pie card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                 <div class="dashboard-card" onclick="navigateTo('compras.php')">
                    <div class="card-header">
                        <h3 class="card-title">Compras</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-tags card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                 <div class="dashboard-card" onclick="navigateTo('pcp.php')">
                    <div class="card-header">
                        <h3 class="card-title">P.C.P</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-tasks card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('financeiro.php')">
                    <div class="card-header">
                        <h3 class="card-title">Financeiro</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-dollar-sign card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>                

                <div class="dashboard-card" onclick="navigateTo('qualidade.php')">
                    <div class="card-header">
                        <h3 class="card-title">Qualidade</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-ruler-combined card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('relatoriosmensais.php')">
                    <div class="card-header">
                        <h3 class="card-title">Relatórios Mensais</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-file-alt card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('materiais.php')">
                    <div class="card-header">
                        <h3 class="card-title">Materiais e Logística</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-shipping-fast card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('tecnologiainformacao.php')">
                    <div class="card-header">
                        <h3 class="card-title">Tecnologia da Informação</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-desktop card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('desenvolvimento.php')">
                    <div class="card-header">
                        <h3 class="card-title">Desenvolvimento</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-building card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('operacoes.php')">
                    <div class="card-header">
                        <h3 class="card-title">Operações</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-user-gear card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('vendas.php')">
                    <div class="card-header">
                        <h3 class="card-title">Vendas</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-arrow-trend-up card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card pulse" onclick="navigateTo('alertasrecentes.php')">
                    <div class="card-header">
                        <h3 class="card-title">Alertas Recentes</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-exclamation-triangle card-icon"></i>
                            <div class="card-icon-bg"></div>
                            <div class="notification-badge">3</div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('manutencao_industrial.php')">
                    <div class="card-header">
                        <h3 class="card-title">Manutenção Industrial</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-wrench card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                 <div class="dashboard-card" onclick="navigateTo('recursoshumanos.php')">
                    <div class="card-header">
                        <h3 class="card-title">Recursos Humanos</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-address-card card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                 <div class="dashboard-card" onclick="navigateTo('recebimento.php')">
                    <div class="card-header">
                        <h3 class="card-title">Recebimento</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-receipt card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="navigateTo('esg.php')">
                    <div class="card-header">
                        <h3 class="card-title">ESG</h3>
                        <div class="card-icon-container">
                            <i class="fas fa-leaf card-icon"></i>
                            <div class="card-icon-bg"></div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-stats">
                        </div>
                    </div>
                </div>
                
                <p class="copyright">© 2025 Embaquim - Tecnologia & Inovação</p>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Create floating particles
            createFloatingParticles();
            
            // Enhanced search functionality
            const searchInput = document.querySelector('.search-box input');
            
            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    this.parentElement.style.borderColor = 'var(--accent-color)';
                    this.parentElement.style.boxShadow = '0 0 25px var(--glow-color)';
                    this.parentElement.style.transform = 'translateY(-3px)';
                });
                
                searchInput.addEventListener('blur', function() {
                    this.parentElement.style.borderColor = '';
                    this.parentElement.style.boxShadow = '';
                    this.parentElement.style.transform = '';
                });

                // Real-time search with debounce
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        performSearch(this.value);
                    }, 300);
                });
            }

            // SUBMENU FUNCTIONALITY - ADICIONADA
            const submenuItems = document.querySelectorAll('.nav-item.has-submenu');
            
            submenuItems.forEach(item => {
                const link = item.querySelector('.nav-link');
                const submenu = item.querySelector('.submenu');
                
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Fecha outros submenus
                    submenuItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.submenu').classList.remove('active');
                        }
                    });
                    
                    // Alterna o submenu atual
                    item.classList.toggle('active');
                    submenu.classList.toggle('active');
                });
            });

            // Fecha submenus ao clicar fora
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-item.has-submenu')) {
                    submenuItems.forEach(item => {
                        item.classList.remove('active');
                        item.querySelector('.submenu').classList.remove('active');
                    });
                }
            });

            // Enhanced card animations
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px) scale(0.95)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0) scale(1)';
                }, index * 100);

                // Add click effect
                card.addEventListener('click', function(e) {
                    if (!this.classList.contains('clicking')) {
                        this.classList.add('clicking');
                        
                        // Ripple effect
                        const ripple = document.createElement('div');
                        ripple.style.position = 'absolute';
                        ripple.style.borderRadius = '50%';
                        ripple.style.backgroundColor = 'rgba(0, 242, 254, 0.3)';
                        ripple.style.transform = 'scale(0)';
                        ripple.style.animation = 'ripple 0.6s linear';
                        ripple.style.pointerEvents = 'none';
                        
                        const rect = this.getBoundingClientRect();
                        const size = Math.max(rect.width, rect.height);
                        const x = e.clientX - rect.left - size / 2;
                        const y = e.clientY - rect.top - size / 2;
                        
                        ripple.style.width = ripple.style.height = size + 'px';
                        ripple.style.left = x + 'px';
                        ripple.style.top = y + 'px';
                        
                        this.appendChild(ripple);
                        
                        setTimeout(() => {
                            this.classList.remove('clicking');
                            if (ripple.parentNode) {
                                ripple.parentNode.removeChild(ripple);
                            }
                        }, 600);
                    }
                });
            });

            // User profile interaction - AGORA COM INFORMAÇÕES REAIS DO PHP
            const userProfile = document.querySelector('.user-profile');
            if (userProfile) {
                userProfile.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                    
                    // Agora mostra informações reais do usuário
                    const userName = document.getElementById('userDisplayName').textContent;
                    showNotification('Perfil de ' + userName, 'Clique em "Meu Perfil" para ver detalhes completos.');
                });
            }

            // Navigation functionality
            window.navigateTo = function(url) {
                // Add page transition effect
                document.body.style.opacity = '0.7';
                document.body.style.transition = 'opacity 0.3s ease';
                
                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            };

            // Active navigation item handling
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (!this.classList.contains('has-submenu')) {
                        navItems.forEach(nav => nav.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });

            // Mobile menu toggle (for responsive design)
            function initMobileMenu() {
                const menuToggle = document.createElement('div');
                menuToggle.className = 'mobile-menu-toggle';
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                menuToggle.style.cssText = `
                    position: fixed;
                    top: 20px;
                    left: 20px;
                    z-index: 1001;
                    background: var(--sidebar-bg);
                    color: white;
                    width: 40px;
                    height: 40px;
                    border-radius: 8px;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    font-size: 20px;
                    cursor: pointer;
                    box-shadow: var(--shadow-medium);
                    transition: all 0.3s ease;
                `;
                
                document.body.appendChild(menuToggle);
                
                menuToggle.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    sidebar.classList.toggle('active');
                    this.style.transform = sidebar.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0)';
                });

                // Check screen size and show/hide toggle
                function checkScreenSize() {
                    if (window.innerWidth <= 900) {
                        menuToggle.style.display = 'flex';
                    } else {
                        menuToggle.style.display = 'none';
                        document.querySelector('.sidebar').classList.remove('active');
                    }
                }

                checkScreenSize();
                window.addEventListener('resize', checkScreenSize);
            }

            initMobileMenu();
        });

        // Floating particles creation
        function createFloatingParticles() {
            const container = document.getElementById('floatingParticles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random properties
                const size = Math.random() * 3 + 2;
                const left = Math.random() * 100;
                const delay = Math.random() * 20;
                const duration = Math.random() * 10 + 15;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}%`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                
                // Random color variation
                const colors = [
                    'var(--accent-color)',
                    'var(--primary-gradient-start)',
                    'var(--success-color)',
                    '#ffffff'
                ];
                const randomColor = colors[Math.floor(Math.random() * colors.length)];
                particle.style.background = randomColor;
                particle.style.boxShadow = `0 0 ${size * 2}px ${randomColor}`;
                
                container.appendChild(particle);
            }
        }

        // Search functionality
        function performSearch(query) {
            if (query.trim() === '') {
                resetSearch();
                return;
            }
            
            const cards = document.querySelectorAll('.dashboard-card');
            let found = false;
            
            cards.forEach(card => {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const content = card.querySelector('.card-content').textContent.toLowerCase();
                const searchTerm = query.toLowerCase();
                
                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    card.style.display = 'flex';
                    card.style.animation = 'cardPulse 1s ease';
                    found = true;
                } else {
                    card.style.display = 'none';
                }
            });
            
            if (!found) {
                showNotification('Busca', 'Nenhum resultado encontrado para: ' + query);
            }
        }

        function resetSearch() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                card.style.display = 'flex';
            });
        }

        // Notification system
        function showNotification(title, message) {
            // Remove existing notification
            const existingNotification = document.querySelector('.custom-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = 'custom-notification';
            notification.innerHTML = `
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--sidebar-bg);
                color: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: var(--shadow-heavy);
                border-left: 4px solid var(--accent-color);
                z-index: 10000;
                transform: translateX(400px);
                transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                max-width: 300px;
                backdrop-filter: blur(20px);
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 400);
            }, 4000);
            
            // Add click to close
            notification.addEventListener('click', () => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 400);
            });
        }

        // Add ripple animation to CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .dashboard-card.clicking {
                transform: scale(0.98) !important;
            }
            
            .mobile-menu-toggle:hover {
                background: var(--hover-bg) !important;
                transform: scale(1.1) !important;
            }
            
            @media (max-width: 900px) {
                .mobile-menu-toggle {
                    display: flex !important;
                }
            }
        `;
        document.head.appendChild(style);

        // Performance monitoring
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const st = window.pageYOffset || document.documentElement.scrollTop;
            if (st > lastScrollTop) {
                // Scrolling down
                document.querySelector('.top-header').style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up
                document.querySelector('.top-header').style.transform = 'translateY(0)';
            }
            lastScrollTop = st <= 0 ? 0 : st;
        }, { passive: true });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + K or / for search focus
            if ((e.ctrlKey && e.key === 'k') || e.key === '/') {
                e.preventDefault();
                const searchInput = document.querySelector('.search-box input');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape to clear search
            if (e.key === 'Escape') {
                const searchInput = document.querySelector('.search-box input');
                if (searchInput && document.activeElement === searchInput) {
                    searchInput.blur();
                    resetSearch();
                }
            }
        });

        // Preload functionality for smoother navigation
        function preloadPages() {
            const links = document.querySelectorAll('a[href]');
            links.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    const url = this.getAttribute('href');
                    if (url && !url.startsWith('#')) {
                        // You could implement actual preloading here
                        console.log('Preloading:', url);
                    }
                });
            });
        }

        preloadPages();

        console.log('🚀 Dashboard Embaquim 2025 carregado com sucesso!');
    </script>
</body>
</html>