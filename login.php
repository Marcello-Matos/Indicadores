<?php
// =========================================================================
// CONFIGURAÇÃO INICIAL E CONEXÃO
// =========================================================================
session_start();

// ATENÇÃO DE SEGURANÇA: REMOVA OU COMENTE ESSAS LINHAS EM PRODUÇÃO
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// ATENÇÃO: SUBSTITUA PELAS SUAS CREDENCIAIS REAIS E USE VARIÁVEIS DE AMBIENTE
$serverName = "servidor"; 
$connectionOptions = [
    "Database" => "dbkalpa",
    "Uid" => "sa", 
    "PWD" => "aplak", 
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => false,
    "ReturnDatesAsStrings" => true
];

// =========================================================================
// FUNÇÕES DE CRIPTOGRAFIA LEGADAS (APENAS PARA VALIDAÇÃO)
// =========================================================================

function fGerarNovoHash($senha) {
    // Método moderno e seguro
    return password_hash($senha, PASSWORD_DEFAULT); 
}

function fSenhaAntiga($senha) {
    // Cifra de César Antiga (insegura, mas necessária para retrocompatibilidade)
    $Senhacrp = '';
    for ($i = 0; $i < strlen($senha); $i++) {
        $vAsc = ord($senha[$i]);
        if ($vAsc > 255) {
            $Senhacrp .= chr(255);
        } else {
            $Senhacrp .= chr($vAsc + 5);
        }
    }
    return $Senhacrp !== '' ? $Senhacrp : ' ';
}

function fSenhaNovoCadastroAntiga($senha) {
    // MD5 de 6 caracteres (insegura, mas necessária para retrocompatibilidade)
    $hash_completo = md5($senha); 
    return substr($hash_completo, 0, 6);
}

// =========================================================================
// LÓGICA DE LOGIN E AUTENTICAÇÃO
// =========================================================================

$erro_login = '';
$usuario_digitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['senha'])) {
    $usuario_digitado = trim($_POST['usuario']);
    $senha_digitada = trim($_POST['senha']);
    
    // Consulta no DB usa o usuário em maiúsculas (assumindo que o DB armazene assim)
    $usuario_para_consulta = strtoupper($usuario_digitado);
    
    if (empty($usuario_digitado) || empty($senha_digitada)) {
        $erro_login = "Por favor, preencha todos os campos!";
    } else {
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        
        if ($conn === false) {
            $erro_login = "Erro de conexão com o banco de dados.";
            // Removido o detalhe do erro do DB para o usuário por questões de segurança
        } else {
            
            // SELECT DE TODAS AS COLUNAS, INCLUINDO TODAS AS PERMISSÕES (CHK...)
            $sql = "SELECT 
                        CodUsuario, Usuario, Nome, senha, CodVendedor, CodCentroCusto, Email,
                        chkOperadorTMK, chkSupCustos, chkSupGeral, chkSupExpedicao,
                        chkSupRecebimento, chkGer, chkDir, chkDirAdm, chkRH,
                        CodFuncionario, CodEmpresaFuncionario, CodTipoFuncionario, AcessaKalpaRH,
                        Desligado, Afastado, Dt_Incl, User_Incl, Dt_Alter, User_Alt
                    FROM dbo.tbl_Usuarios 
                    WHERE Usuario = ?";
            
            $params = array($usuario_para_consulta);
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                $erro_login = "Erro na consulta ao banco de dados. Contate o suporte.";
            } else {
                $usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                
                $login_sucesso = false;
                $atualizar_senha = false;
                
                if ($usuario) {
                    $senha_salva = $usuario['senha'];
                    $cod_usuario = $usuario['CodUsuario'];
                    
                    // 1. Tentar validação com Hash Moderno (Prioridade)
                    if (password_verify($senha_digitada, $senha_salva)) {
                        $login_sucesso = true;
                        // Opcional: Re-hash se o hash estiver desatualizado
                        if (password_needs_rehash($senha_salva, PASSWORD_DEFAULT)) {
                            $atualizar_senha = true;
                        }
                    } 
                    // 2. Tentar validação com Cifra de César (Antiga - Marca para atualização)
                    else if (fSenhaAntiga($senha_digitada) === $senha_salva) {
                        $login_sucesso = true;
                        $atualizar_senha = true;
                    }
                    // 3. Tentar validação com MD5(6) (Nova Antiga - Marca para atualização)
                    else if (fSenhaNovoCadastroAntiga($senha_digitada) === $senha_salva) {
                       $login_sucesso = true;
                       $atualizar_senha = true;
                    }

                    if ($login_sucesso) {
                        
                        // ==========================================================
                        // LÓGICA DE MIGRAÇÃO DE SENHA (Aprimoramento de Segurança)
                        // ==========================================================
                        if ($atualizar_senha) {
                            $novo_hash = fGerarNovoHash($senha_digitada);
                            $sql_update = "UPDATE dbo.tbl_Usuarios SET senha = ?, Dt_Alter = GETDATE() WHERE CodUsuario = ?";
                            $params_update = array($novo_hash, $cod_usuario);
                            sqlsrv_query($conn, $sql_update, $params_update);
                            // Observação: Não é necessário verificar o sucesso do UPDATE aqui.
                        }
                        
                        // ==========================================================
                        // CARREGAMENTO DAS PERMISSÕES NA SESSÃO
                        // ==========================================================
                        
                        // Salvar dados de autenticação
                        $_SESSION['usuario_logado'] = true;
                        $_SESSION['usuario_id'] = $cod_usuario;
                        $_SESSION['usuario_nome'] = $usuario['Nome'];
                        $_SESSION['usuario_login'] = $usuario['Usuario'];
                        
                        // Salvar todas as permissões e dados relevantes
                        // Itera sobre o array $usuario para salvar todas as chaves 'chk...'
                        foreach ($usuario as $chave => $valor) {
                            // Se a chave começar com 'chk', salva na sessão com prefixo 'perm_'
                            if (strpos($chave, 'chk') === 0) {
                                $_SESSION['perm_' . substr($chave, 3)] = (bool)$valor; // Salva como booleano (true/false)
                            }
                            // Salva também outras colunas úteis
                            if (in_array($chave, ['CodVendedor', 'CodCentroCusto', 'Email'])) {
                                $_SESSION[strtolower($chave)] = $valor;
                            }
                            // Exemplo específico para AcessaKalpaRH, CodFuncionario, etc.
                            if ($chave === 'AcessaKalpaRH') {
                                $_SESSION['acessa_rh'] = (bool)$valor;
                            }
                            // Adicione aqui outros campos que você precisa globalmente na sessão (Ex: Desligado, Afastado)
                            if ($chave === 'Desligado') {
                                $_SESSION['desligado'] = (bool)$valor;
                            }
                            if ($chave === 'Afastado') {
                                $_SESSION['afastado'] = (bool)$valor;
                            }
                        }

                        // Redirecionar para dashboard
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $erro_login = "Usuário ou senha inválidos!";
                    }
                } else {
                    $erro_login = "Usuário ou senha inválidos!";
                }
                
                sqlsrv_free_stmt($stmt);
            }
            
            sqlsrv_close($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessar Dashboard | Embaquim</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="img/favicon.png" type="image/x-icon">
    <style>
        /* [ ... CÓDIGO CSS COMPLETO MANTIDO INTACTO ... ] */
        :root {
            --primary-gradient-start: #6a11cb;
            --primary-gradient-end: #2575fc;
            --secondary-gradient-start: #ff6b6b;
            --secondary-gradient-end: #ffa726;
            --text-color: #ffffff;
            --accent-color: #00f2fe;
            --dark-bg: #0f0f23;
            --input-bg: rgba(255, 255, 255, 0.1);
            --input-focus-bg: rgba(255, 255, 255, 0.15);
            --shadow-color: rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 0;
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

        .main-container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            height: 80vh;
            background: rgba(15, 15, 35, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px var(--shadow-color);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
        }

        .login-area {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            text-align: left;
            position: relative;
            z-index: 2;
        }

        .title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 40px;
            background: linear-gradient(to right, var(--accent-color), #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, var(--accent-color), #ffffff);
            border-radius: 2px;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.8);
        }

        .input-field {
            display: flex;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 12px 15px;
            background-color: var(--input-bg);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .input-field::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .input-field:focus-within {
            border-color: var(--accent-color);
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.3);
            background-color: var(--input-focus-bg);
        }

        .input-field:focus-within::before {
            left: 100%;
        }

        .input-field i {
            color: rgba(255, 255, 255, 0.7);
            margin-right: 12px;
            font-size: 18px;
            transition: color 0.3s;
        }

        .input-field:focus-within i {
            color: var(--accent-color);
        }

        .input-field input {
            flex-grow: 1;
            border: none;
            outline: none;
            background: transparent;
            font-size: 16px;
            color: #ffffff;
            padding: 2px 0;
        }

        .input-field input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .toggle-password {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-size: 16px;
            padding: 0 5px;
            transition: all 0.3s ease;
            margin-left: 5px;
        }

        .toggle-password:hover {
            color: var(--accent-color);
            transform: scale(1.1);
        }

        .toggle-password:active {
            transform: scale(0.95);
        }

        .btn-entrar {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, var(--primary-gradient-start), var(--primary-gradient-end));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-entrar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--secondary-gradient-start), var(--secondary-gradient-end));
            transition: left 0.5s;
            z-index: -1;
        }

        .btn-entrar:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-entrar:hover::before {
            left: 0;
        }

        .btn-entrar:active {
            transform: translateY(-1px);
        }

        .logo-area {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.08));
        }

        .logo-area::before {
            content: '';
            position: absolute;
            width: 300%;
            height: 300%;
            background: 
                radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 60%),
                radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: cosmicRotate 25s linear infinite;
            z-index: 1;
        }

        @keyframes cosmicRotate {
            0% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.05); }
            100% { transform: rotate(360deg) scale(1); }
        }

        .logo-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            z-index: 2;
            padding: 40px;
        }

        .logo-image-container {
            position: relative;
            margin-bottom: 25px;
            filter: drop-shadow(0 0 30px rgba(255, 255, 255, 0.4));
            transition: all 0.3s ease;
        }

        .logo-image {
            width: 280px;
            height: 280px;
            object-fit: contain;
            border-radius: 20px;
            transition: all 0.3s ease;
            filter: brightness(0) invert(1);
        }

        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 130%;
            height: 130%;
            background: radial-gradient(circle, 
                rgba(255, 255, 255, 0.4) 0%, 
                rgba(255, 255, 255, 0.3) 30%, 
                transparent 70%);
            filter: blur(25px);
            animation: glowPulse 2.5s ease-in-out infinite alternate;
            z-index: -1;
        }

        @keyframes glowPulse {
            0% { opacity: 0.5; transform: translate(-50%, -50%) scale(0.95); }
            100% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.05); }
        }

        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 46px;
            font-weight: 800;
            letter-spacing: 4px;
            background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            text-shadow: 0 0 25px rgba(255, 255, 255, 0.4);
            position: relative;
        }

        .logo-text::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #ffffff, transparent);
            animation: lineScan 3s ease-in-out infinite;
        }

        @keyframes lineScan {
            0%, 100% { opacity: 0; transform: scaleX(0); }
            50% { opacity: 1; transform: scaleX(1); }
        }

        .logo-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 12px;
            position: relative;
        }

        .logo-subtitle::before {
            content: '✦';
            margin-right: 12px;
            color: #ffffff;
            animation: twinkle 2s ease-in-out infinite;
        }

        .logo-subtitle::after {
            content: '✦';
            margin-left: 12px;
            color: #ffffff;
            animation: twinkle 2s ease-in-out infinite reverse;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.4; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }

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

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                height: 90vh;
                width: 95%;
            }

            .logo-area {
                padding: 30px;
                min-height: 200px;
            }

            .login-area {
                padding: 30px 20px;
            }

            .title {
                font-size: 28px;
            }

            .logo-image {
                width: 200px;
                height: 200px;
            }

            .logo-text {
                font-size: 32px;
                letter-spacing: 2px;
            }
        }

        @media (max-width: 480px) {
            .logo-image {
                width: 150px;
                height: 150px;
            }

            .logo-text {
                font-size: 28px;
                letter-spacing: 1.5px;
            }
        }

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

        .login-box, .logo-content {
            animation: fadeInUp 0.8s ease-out;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.2); 
            border: 1px solid rgba(239, 68, 68, 0.5); 
            border-radius: 10px; 
            padding: 15px; 
            margin-bottom: 25px; 
            color: #ff6b6b; 
            font-size: 14px; 
            text-align: center;
            animation: fadeInUp 0.5s ease-out;
        }

        .error-message i {
            margin-right: 8px;
        }
        
        .register-link-container {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }

        .register-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 700;
            margin-left: 5px;
            transition: color 0.3s, text-shadow 0.3s;
            position: relative;
            display: inline-block;
        }

        .register-link::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 100%;
            height: 1px;
            background-color: var(--accent-color);
            transform: scaleX(0);
            transition: transform 0.3s ease-in-out;
        }

        .register-link:hover {
            color: #ffffff;
            text-shadow: 0 0 5px var(--accent-color);
        }
        
        .register-link:hover::after {
            transform: scaleX(1);
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

    <div class="main-container">
        <div class="login-area">
            <div class="login-box">
                <h1 class="title">Acessar Indicadores</h1>

                <?php if (!empty($erro_login)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro_login); ?>
                </div>
                <?php endif; ?>

                <form action="" method="POST" id="loginForm">
                    <div class="input-group">
                        <label for="usuario">Seu Usuário</label>
                        <div class="input-field">
                            <i class="fas fa-user"></i>
                            <input type="text" id="usuario" name="usuario" placeholder="Digite seu usuário" 
                                    value="<?php echo htmlspecialchars($usuario_digitado); ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="senha">Senha</label>
                        <div class="input-field">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-entrar">
                        Entrar
                    </button>
                </form>
            </div>
        </div>

        <div class="logo-area">
            <div class="logo-content">
                <div class="logo-image-container">
                    <div class="logo-glow"></div>
                    <img src="img/logo2025.png" alt="Logo Embaquim 2025" class="logo-image">
                    <div class="logo-subtitle">Tecnologia & Inovação</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // [ ... CÓDIGO JAVASCRIPT COMPLETO MANTIDO INTACTO ... ]
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
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
            
            // Função para alternar visibilidade da senha
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('senha');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    // Alternar entre tipo password e text
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Alternar ícone do olho
                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        this.style.color = 'var(--accent-color)';
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        this.style.color = '';
                    }
                });
            }
            
            // Validação do formulário
            const loginForm = document.getElementById('loginForm');
            
            if (loginForm) {
                loginForm.addEventListener('submit', function(event) {
                    const usuarioInput = document.getElementById('usuario');
                    const senhaInput = document.getElementById('senha');
                    
                    // Remover espaços em branco
                    usuarioInput.value = usuarioInput.value.trim();
                    senhaInput.value = senhaInput.value.trim();
                    
                    // Validação básica
                    if (usuarioInput.value === '' || senhaInput.value === '') {
                        event.preventDefault();
                        
                        // Efeito visual de erro
                        if (usuarioInput.value === '') {
                            usuarioInput.parentElement.style.borderColor = '#ff6b6b';
                            usuarioInput.parentElement.style.boxShadow = '0 0 10px rgba(255, 107, 107, 0.5)';
                        }
                        
                        if (senhaInput.value === '') {
                            senhaInput.parentElement.style.borderColor = '#ff6b6b';
                            senhaInput.parentElement.style.boxShadow = '0 0 10px rgba(255, 107, 107, 0.5)';
                        }
                        
                        // Resetar estilos após 2 segundos
                        setTimeout(() => {
                            usuarioInput.parentElement.style.borderColor = '';
                            usuarioInput.parentElement.style.boxShadow = '';
                            senhaInput.parentElement.style.borderColor = '';
                            senhaInput.parentElement.style.boxShadow = '';
                        }, 2000);
                    }
                });
            }
            
            // Efeito de digitação no placeholder
            const usuarioInputRef = document.getElementById('usuario');
            const passwordInputRef = document.getElementById('senha');
            
            if (usuarioInputRef) {
                usuarioInputRef.addEventListener('focus', function() {
                    this.placeholder = '';
                });
                usuarioInputRef.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.placeholder = 'Digite seu usuário';
                    }
                });
            }
            
            if (passwordInputRef) {
                passwordInputRef.addEventListener('focus', function() {
                    this.placeholder = '';
                });
                passwordInputRef.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.placeholder = 'Digite sua senha';
                    }
                });
            }
        });
    </script>
</body>
</html>