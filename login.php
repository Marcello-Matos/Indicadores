<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessar Dashboard | Embaquim</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ------------------------------------- */
        /* 1. RESET E CONFIGURAÇÕES GLOBAIS      */
        /* ------------------------------------- */
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
        /* 3. LAYOUT PRINCIPAL                   */
        /* ------------------------------------- */
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

        /* ------------------------------------- */
        /* 4. ÁREA DE LOGIN                      */
        /* ------------------------------------- */
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

        /* ------------------------------------- */
        /* 5. FORMULÁRIO ESTILIZADO              */
        /* ------------------------------------- */
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

        /* ------------------------------------- */
        /* 6. BOTÃO DE VISUALIZAR SENHA          */
        /* ------------------------------------- */
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

        /* ------------------------------------- */
        /* 7. BOTÃO ESTILIZADO                   */
        /* ------------------------------------- */
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

        /* ------------------------------------- */
        /* 8. ÁREA DO LOGO                       */
        /* ------------------------------------- */
        .logo-area {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(37, 117, 252, 0.2), rgba(106, 17, 203, 0.2));
        }

        .logo-area::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 242, 254, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .logo-icon {
            font-size: 100px;
            font-weight: bold;
            line-height: 1;
            background: linear-gradient(to bottom, var(--accent-color), #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            filter: drop-shadow(0 0 10px rgba(0, 242, 254, 0.5));
            animation: pulse 3s infinite alternate;
        }

        @keyframes pulse {
            0% { transform: scale(1); filter: drop-shadow(0 0 10px rgba(0, 242, 254, 0.5)); }
            100% { transform: scale(1.05); filter: drop-shadow(0 0 20px rgba(0, 242, 254, 0.8)); }
        }

        .logo-text {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 1px;
            background: linear-gradient(to right, #ffffff, var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-subtitle {
            margin-top: 10px;
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
        }

        /* ------------------------------------- */
        /* 9. EFEITOS EXTRAS                     */
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
        /* 10. RESPONSIVIDADE                    */
        /* ------------------------------------- */
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

            .logo-icon {
                font-size: 70px;
            }

            .logo-text {
                font-size: 28px;
            }
        }

        /* ------------------------------------- */
        /* 11. ANIMAÇÃO DE ENTRADA               */
        /* ------------------------------------- */
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
    </style>
</head>
<body>
    <!-- Fundo animado com partículas -->
    <div class="background-animation">
        <div class="gradient-bg"></div>
        <div class="particles" id="particles"></div>
    </div>
    
    <!-- Efeitos de brilho -->
    <div class="glow-effect">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
    </div>

    <!-- Container principal -->
    <div class="main-container">
        <!-- Área de login -->
        <div class="login-area">
            <div class="login-box">
                <h1 class="title">Acessar Indicadores</h1>

                <form action="dashboard.php" method="POST" id="loginForm">
                    <div class="input-group">
                        <label for="usuario">Seu Usuário</label>
                        <div class="input-field">
                            <i class="fas fa-user"></i>
                            <input type="text" id="usuario" name="usuario" placeholder="Digite seu usuário" required>
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

        <!-- Área do logo -->
        <div class="logo-area">
            <div class="logo-content">
                <i class="logo-icon">E</i> 
                <span class="logo-text">embaquim</span>
                <p class="logo-subtitle">Tecnologia & Inovação</p>
            </div>
        </div>
    </div>

    <script>
        // Criar partículas dinâmicas
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
                        this.style.color = 'var(--accent-color)'; // Destaque quando a senha está visível
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        this.style.color = ''; // Voltar à cor padrão
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
            const usuarioInput = document.getElementById('usuario');
            
            if (usuarioInput) {
                usuarioInput.addEventListener('focus', function() {
                    this.placeholder = '';
                });
                
                usuarioInput.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.placeholder = 'Digite seu usuário';
                    }
                });
            }
            
            if (passwordInput) {
                passwordInput.addEventListener('focus', function() {
                    this.placeholder = '';
                });
                
                passwordInput.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.placeholder = 'Digite sua senha';
                    }
                });
            }
        });
    </script>
</body>
</html>