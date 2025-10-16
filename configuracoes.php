<?php
// configuracoes.php
// Arquivo único: PHP + HTML + CSS + JS
// Lê/escreve config.json na mesma pasta

session_start();

// Caminho do arquivo de configuração (mesma pasta)
$config_file = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';

// Valores padrão
$defaults = [
    'nome_usuario' => 'Gerente Administrativo',
    'titulo_pagina' => 'Configurações do Sistema',
    'config_notificacoes' => true,
    'config_tema' => 'light',
    'config_linguagem' => 'pt-BR',
    'config_limite_dados' => 500,
    'alert_email' => 'alerta@embaquim.com',
    'weekly_report' => true,
    'two_factor' => false
];

// Carrega configurações do arquivo (se existir) com fallback para defaults
$config = $defaults;
if (is_readable($config_file)) {
    $json = file_get_contents($config_file);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $config = array_replace($config, $decoded);
    }
}

// Para evitar notices no HTML
$nome_usuario = $config['nome_usuario'];
$titulo_pagina = $config['titulo_pagina'];
$config_notificacoes = (bool)$config['config_notificacoes'];
$config_tema = ($config['config_tema'] === 'dark') ? 'dark' : 'light';
$config_linguagem = $config['config_linguagem'];
$config_limite_dados = (int)$config['config_limite_dados'];
$alert_email = $config['alert_email'];
$weekly_report = (bool)$config['weekly_report'];
$two_factor = (bool)$config['two_factor'];

// Função para salvar arquivo com lock e tratamento de erros
function save_config_file($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    // grava com LOCK_EX
    $result = file_put_contents($path, $json, LOCK_EX);
    return ($result !== false);
}

// Processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saved = false;
    // Preferências gerais
    if (isset($_POST['salvar_preferencias'])) {
        // Tema
        $tema = (isset($_POST['tema']) && $_POST['tema'] === 'dark') ? 'dark' : 'light';
        // Linguagem
        $linguagem = filter_input(INPUT_POST, 'linguagem', FILTER_SANITIZE_STRING) ?: 'pt-BR';
        // Limite de dados (inteiro entre 100 e 1000)
        $limite_raw = filter_input(INPUT_POST, 'limite_dados', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 100, 'max_range' => 1000]
        ]);
        $limite = ($limite_raw !== false && $limite_raw !== null) ? (int)$limite_raw : 500;

        // Atualiza config
        $config['config_tema'] = $tema;
        $config['config_linguagem'] = $linguagem;
        $config['config_limite_dados'] = $limite;

        $saved = save_config_file($config_file, $config);
    }

    // Notificações
    if (isset($_POST['salvar_notificacoes'])) {
        $notificacoes = isset($_POST['notificacoes']) && $_POST['notificacoes'] === '1';
        $alert_email_in = filter_input(INPUT_POST, 'alert_email', FILTER_VALIDATE_EMAIL);
        $weekly_report_in = isset($_POST['weekly_report']) && $_POST['weekly_report'] === '1';

        $config['config_notificacoes'] = $notificacoes;
        $config['alert_email'] = $alert_email_in ? $alert_email_in : $config['alert_email'];
        $config['weekly_report'] = (bool)$weekly_report_in;

        $saved = save_config_file($config_file, $config);
    }

    // Segurança / Ações (exemplo: forçar logout) - apenas simulado aqui
    if (isset($_POST['forcar_logout'])) {
        // Aqui você colocaria a lógica real de logout forçado
        // Por enquanto, só adicionamos um log de exemplo (não persistido)
        $_SESSION['last_action'] = 'forcar_logout';
        $saved = true;
    }

    // Atualiza variáveis após salvar
    if ($saved) {
        // recarrega valores do config
        if (is_readable($config_file)) {
            $json = file_get_contents($config_file);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $config = array_replace($defaults, $decoded);
            }
        }
        // Redireciona para evitar reenvio
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    } else {
        // Se falhar a gravação, redireciona com erro
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=1");
        exit();
    }
}

// Recarrega estados (para renderização)
$nome_usuario = $config['nome_usuario'];
$titulo_pagina = $config['titulo_pagina'];
$config_notificacoes = (bool)$config['config_notificacoes'];
$config_tema = ($config['config_tema'] === 'dark') ? 'dark' : 'light';
$config_linguagem = $config['config_linguagem'];
$config_limite_dados = (int)$config['config_limite_dados'];
$alert_email = $config['alert_email'];
$weekly_report = (bool)$config['weekly_report'];
$two_factor = (bool)$config['two_factor'];

$show_success = isset($_GET['success']);
$show_error = isset($_GET['error']);

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo_pagina); ?> | Embaquim</title>
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
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --error-color: #e74c3c;
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
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            align-content: start;
        }

        /* Cards de Configurações */
        .settings-card {
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

        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.7s;
        }

        .settings-card:hover::before {
            left: 100%;
        }

        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: rgba(0, 242, 254, 0.3);
        }

        .settings-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--accent-color);
            filter: drop-shadow(0 0 5px var(--glow-color));
        }

        /* Itens de Configuração */
        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-label {
            flex: 1;
            font-weight: 500;
            color: var(--text-light);
        }

        .setting-description {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 5px;
            display: block;
        }

        .setting-control {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.2);
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--accent-color);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Inputs e Selects */
        .form-input, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 10px 12px;
            color: var(--text-light);
            font-size: 14px;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 10px var(--glow-color);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Range Slider */
        .range-container {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .range-slider {
            flex: 1;
            -webkit-appearance: none;
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            outline: none;
        }

        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            background: var(--accent-color);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 0 10px var(--glow-color);
        }

        .range-value {
            min-width: 60px;
            text-align: right;
            font-weight: 600;
            color: var(--accent-color);
        }

        /* Botões */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-gradient-start), var(--primary-gradient-end));
            color: white;
            box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(106, 17, 203, 0.4);
        }

        .btn-accent {
            background: linear-gradient(135deg, var(--secondary-gradient-start), var(--secondary-gradient-end));
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .btn-small {
            padding: 10px 16px;
            font-size: 13px;
        }

        /* Links de Configuração */
        .setting-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .setting-link:last-child {
            border-bottom: none;
        }

        .setting-link:hover {
            color: var(--accent-color);
            padding-left: 10px;
        }

        .setting-link i {
            width: 20px;
            text-align: center;
        }

        /* Tabela de Logs */
        .logs-container {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logs-table th,
        .logs-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logs-table th {
            font-weight: 600;
            color: var(--accent-color);
            background: rgba(0, 242, 254, 0.1);
        }

        .logs-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .status-success {
            color: var(--success-color);
            font-weight: 600;
        }

        .status-error {
            color: var(--error-color);
            font-weight: 600;
        }

        /* Toast */
        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success {
            background: linear-gradient(135deg, var(--success-color), #2ecc71);
        }

        .toast.error {
            background: linear-gradient(135deg, var(--error-color), #e67e22);
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .copyright {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            margin-top: 40px;
            grid-column: 1 / -1;
        }

        /* Coluna Direita */
        .right-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
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

            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .setting-control {
                width: 100%;
                justify-content: space-between;
            }

            .right-column {
                gap: 20px;
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
                    
                    <li class="nav-item active">
                        <a href="configuracoes.php" class="nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="login.php" class="nav-link">
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
                    <input type="text" placeholder="Pesquisar Configuração...">
                </div>

                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i> 
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($nome_usuario); ?></span>
                </div>
            </header>

            <section class="content-area">
                
                <!-- Coluna Esquerda - Cards Principais -->
                <div class="left-column">
                    <!-- Preferências do Sistema -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-cogs"></i> Preferências do Sistema</h2>
                        </div>

                        <form id="general-settings-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <input type="hidden" name="salvar_preferencias" value="1">

                            <div class="setting-item">
                                <div>
                                    <label class="setting-label">Modo Escuro</label>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" id="theme-toggle" name="tema" value="dark" <?php echo ($config_tema === 'dark' ? 'checked' : ''); ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div>
                                    <label class="setting-label" for="language">Idioma da Interface</label>
                                </div>
                                <div class="setting-control">
                                    <select id="language" name="linguagem" class="form-select">
                                        <option value="pt-BR" <?php echo ($config_linguagem === 'pt-BR' ? 'selected' : ''); ?>>Português (Brasil)</option>
                                        <option value="en-US" <?php echo ($config_linguagem === 'en-US' ? 'selected' : ''); ?>>English (US)</option>
                                        <option value="es-ES" <?php echo ($config_linguagem === 'es-ES' ? 'selected' : ''); ?>>Español (ES)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div>
                                    <label class="setting-label">Limite de Exibição de Dados</label>
                                    <span class="setting-description">Define o limite máximo de dados processados em relatórios</span>
                                </div>
                                <div class="setting-control">
                                    <div class="range-container">
                                        <input type="range" id="data-range" class="range-slider" min="100" max="1000" value="<?php echo htmlspecialchars($config_limite_dados); ?>">
                                        <input type="number" id="data-limit" name="limite_dados" class="form-input" value="<?php echo htmlspecialchars($config_limite_dados); ?>" min="100" max="1000" style="width: 100px;">
                                        <span class="range-value"><span id="current-data-limit"><?php echo htmlspecialchars($config_limite_dados); ?></span> MB</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Salvar Preferências
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Logs de Atividade -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-list-alt"></i> Logs de Atividade Recente</h2>
                        </div>
                        
                        <div class="logs-container">
                            <table class="logs-table">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Ação</th>
                                        <th>IP</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2025-10-10 17:01</td>
                                        <td>Alterou configuração de Tema</td>
                                        <td>192.168.1.1</td>
                                        <td><span class="status-success">Sucesso</span></td>
                                    </tr>
                                    <tr>
                                        <td>2025-10-10 16:45</td>
                                        <td>Tentativa de Login</td>
                                        <td>10.0.0.5</td>
                                        <td><span class="status-error">Falha</span></td>
                                    </tr>
                                    <tr>
                                        <td>2025-10-09 09:30</td>
                                        <td>Gerou Relatório Mensal</td>
                                        <td>192.168.1.1</td>
                                        <td><span class="status-success">Sucesso</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Coluna Direita - Cards Secundários -->
                <div class="right-column">
                    <!-- Notificações -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-bell"></i> Notificações</h2>
                        </div>

                        <form id="notifications-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <input type="hidden" name="salvar_notificacoes" value="1">
                            
                            <div class="setting-item">
                                <div>
                                    <label class="setting-label">Receber Notificações por E-mail</label>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" id="notifications-toggle" name="notificacoes" value="1" <?php echo ($config_notificacoes ? 'checked' : ''); ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div>
                                    <label class="setting-label" for="alert-email">E-mail para Alertas Críticos</label>
                                </div>
                                <div class="setting-control">
                                    <input type="email" id="alert-email" name="alert_email" class="form-input" value="<?php echo htmlspecialchars($alert_email); ?>" placeholder="alerta@empresa.com">
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Atualizar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Segurança -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-shield-alt"></i> Segurança e Acesso</h2>
                        </div>

                        <div class="setting-item">
                            <div>
                                <label class="setting-label">Autenticação de Dois Fatores (2FA)</label>
                            </div>
                            <div class="setting-control">
                                <label class="switch">
                                    <input type="checkbox" id="two-factor" name="two_factor" value="1" <?php echo ($two_factor ? 'checked' : ''); ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <a href="#" class="setting-link">
                            <i class="fas fa-key"></i> Mudar Senha
                        </a>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="margin-top: 20px;">
                            <input type="hidden" name="forcar_logout" value="1">
                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="btn btn-accent btn-small">
                                    <i class="fas fa-sync-alt"></i> Forçar Logout
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="copyright">© <?php echo date('Y'); ?> Embaquim - Tecnologia & Inovação</p>
            </section>
        </main>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast <?php echo $show_success ? 'show success' : ($show_error ? 'show error' : ''); ?>" style="<?php echo ($show_success||$show_error) ? '' : 'display:none;'; ?>">
        <i class="fas <?php echo $show_success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <div><?php echo $show_success ? 'Configurações salvas com sucesso!' : ($show_error ? 'Erro ao salvar configurações.' : ''); ?></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
            
            // Navegação do menu
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.addEventListener('click', (e) => {
                    if (link.closest('.submenu')) {
                        return;
                    }
                    
                    if (link.closest('.has-submenu') && link.getAttribute('href') === '#') {
                        e.preventDefault();
                        return;
                    }
                    
                    document.querySelectorAll('.nav-item.active').forEach(activeItem => {
                        activeItem.classList.remove('active');
                    });
                    
                    if(link.closest('.nav-item')) {
                        link.closest('.nav-item').classList.add('active');
                    }
                });
            });
            
            // Efeito no campo de pesquisa
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
            
            // Animação dos cards
            const cards = document.querySelectorAll('.settings-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Sincronização do range slider
            const range = document.getElementById('data-range');
            const number = document.getElementById('data-limit');
            const display = document.getElementById('current-data-limit');

            function syncFromRange() {
                number.value = range.value;
                display.textContent = range.value;
            }
            
            function syncFromNumber() {
                let v = parseInt(number.value) || parseInt(range.min);
                if (v < parseInt(range.min)) v = parseInt(range.min);
                if (v > parseInt(range.max)) v = parseInt(range.max);
                range.value = v;
                number.value = v;
                display.textContent = v;
            }

            range.addEventListener('input', syncFromRange);
            number.addEventListener('input', syncFromNumber);

            // Feedback de envio dos formulários
            const generalForm = document.getElementById('general-settings-form');
            const notifForm = document.getElementById('notifications-form');

            function onSubmitWithSpinner(form, btn) {
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner"></span> Salvando...';
                
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }, 2000);
            }

            if (generalForm) {
                generalForm.addEventListener('submit', function(e){
                    const btn = generalForm.querySelector('button[type="submit"]');
                    onSubmitWithSpinner(generalForm, btn);
                });
            }
            
            if (notifForm) {
                notifForm.addEventListener('submit', function(e){
                    const btn = notifForm.querySelector('button[type="submit"]');
                    onSubmitWithSpinner(notifForm, btn);
                });
            }

            // Auto-hide toast
            const toast = document.getElementById('toast');
            if (toast && toast.classList.contains('show')) {
                setTimeout(() => {
                    try { 
                        toast.classList.remove('show'); 
                        toast.style.display = 'none'; 
                    } catch(e) {}
                }, 3000);
            }
        });
    </script>
</body>
</html>