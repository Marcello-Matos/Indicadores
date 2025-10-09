<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Futurístico</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #00c6ff;
            --dark: #121212;
            --light: #f0f8ff;
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.1);
            --text-primary: #333333;
            --text-secondary: #666666;
            --success: #00c851;
            --error: #ff3860;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(-45deg, #121212, #1a1a2e, #16213e, #0f3460);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animações de fundo */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Partículas flutuantes */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
        }

        /* Container principal */
        .container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--card-border);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        /* Efeito de brilho no container */
        .container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--primary), var(--secondary), var(--accent), var(--primary));
            z-index: -1;
            border-radius: 22px;
            animation: borderGlow 3s linear infinite;
            background-size: 400% 400%;
            opacity: 0.7;
        }

        @keyframes borderGlow {
            0% { background-position: 0% 50%; filter: hue-rotate(0deg); }
            50% { background-position: 100% 50%; filter: hue-rotate(90deg); }
            100% { background-position: 0% 50%; filter: hue-rotate(0deg); }
        }

        /* Títulos */
        h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-align: center;
            letter-spacing: 1px;
            position: relative;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 3px;
        }

        h2 {
            font-size: 20px;
            font-weight: 500;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
        }

        h2::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            margin-right: 10px;
            box-shadow: 0 0 10px rgba(106, 17, 203, 0.5);
        }

        /* Estrutura de Seção do Formulário */
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-section:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        /* Layout para os campos de entrada */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        /* Ajuste para campos menores */
        .form-group.half-width {
            flex: 0.5;
        }
        .form-group.third-width {
            flex: 0.33;
        }

        label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            transition: var(--transition);
        }

        .form-group:focus-within label {
            color: var(--primary);
        }

        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="password"] {
            padding: 15px 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-primary);
            height: 50px;
            transition: var(--transition);
            outline: none;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(0, 198, 255, 0.2);
            background: rgba(255, 255, 255, 1);
        }

        /* Campos desabilitados */
        input:disabled {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        /* Layout da foto de perfil */
        .profile-info-row {
            display: flex;
            gap: 40px;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        
        .profile-photo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 150px;
            flex-shrink: 0;
        }
        
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            overflow: hidden;
            border: 3px solid rgba(106, 17, 203, 0.2);
            position: relative;
            transition: var(--transition);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-photo:hover {
            transform: scale(1.05);
            border-color: var(--accent);
        }
        
        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo-placeholder {
            font-size: 40px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .change-photo-btn {
            font-size: 14px;
            color: var(--primary);
            cursor: pointer;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(106, 17, 203, 0.1);
            transition: var(--transition);
            border: 1px solid rgba(106, 17, 203, 0.2);
        }

        .change-photo-btn:hover {
            background: rgba(106, 17, 203, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.2);
        }

        /* Campos de Dados Básicos */
        .basic-data-fields {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .data-pair {
            display: flex;
            gap: 20px;
        }

        .data-pair .form-group {
            flex: 1;
        }

        /* Estilos para a seção de Alterar Senha */
        .password-fields .form-row {
            flex-direction: column;
            gap: 20px;
        }
        
        .password-fields .form-group {
            width: 100%;
        }

        /* Indicador de força da senha */
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            background: rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 5px;
            transition: var(--transition);
        }

        /* Rodapé com botões de ação */
        .actions-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: var(--transition);
            z-index: -1;
        }

        .btn:hover::before {
            width: 100%;
        }

        .btn-cancel {
            background: rgba(0, 0, 0, 0.05);
            color: var(--text-primary);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .btn-cancel:hover {
            background: rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-save {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(106, 17, 203, 0.4);
        }

        /* Efeito de brilho nos botões */
        .btn-save::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-save:hover::after {
            left: 100%;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .form-row, .profile-info-row, .data-pair {
                flex-direction: column;
                gap: 15px;
            }

            .profile-info-row {
                align-items: center;
                text-align: center;
            }

            .profile-photo-container {
                width: auto;
            }
            
            .actions-footer {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* Efeitos de entrada suave */
        .form-section {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Atraso para animação sequencial */
        .basic-info { animation-delay: 0.1s; }
        .address-info { animation-delay: 0.2s; }
        .password-fields { animation-delay: 0.3s; }
        .actions-footer { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Partículas de fundo -->
    <div class="particles" id="particles"></div>

    <div class="container">
        <h1>DADOS DO PERFIL</h1>

        <div class="form-section basic-info">
            <h2>Informações Básicas</h2>
            <div class="profile-info-row">
                <div class="profile-photo-container">
                    <div class="profile-photo">
                        <span class="profile-photo-placeholder">👤</span> 
                    </div>
                    <a href="#" class="change-photo-btn" id="alterarFotoBtn">Alterar Foto</a>
                    <input type="file" id="photoUpload" accept="image/*" style="display: none;">
                </div>

                <div class="basic-data-fields">
                    <div class="data-pair">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" value="TESTE GERENTE">
                        </div>
                        <div class="form-group">
                            <label>E-mail</label>
                            <input type="email" value="igor" disabled>
                        </div>
                    </div>

                    <div class="data-pair">
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="tel" placeholder="(dd) ddddd-dddd">
                        </div>
                        <div class="form-group">
                            <label>Data Cadastro</label>
                            <input type="text" value="dd/mm/aaaa" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section address-info">
            <h2>Endereço</h2>
            <div class="form-row">
                <div class="form-group third-width">
                    <label>CEP</label>
                    <input type="text" value="00000-000">
                </div>
                <div class="form-group">
                    <label>Endereço</label>
                    <input type="text" value="Rua Exemplo">
                </div>
                <div class="form-group third-width">
                    <label>Número</label>
                    <input type="text" value="123">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Bairro</label>
                    <input type="text" value="Bairro Exemplo">
                </div>
                <div class="form-group">
                    <label>Cidade</label>
                    <input type="text" value="Cidade">
                </div>
                <div class="form-group half-width">
                    <label>Estado</label>
                    <input type="text" value="UF">
                </div>
            </div>
        </div>

        <div class="form-section password-fields">
            <h2>Alterar Senha</h2>
            <div class="form-row">
                <div class="form-group">
                    <label>Senha Atual</label>
                    <input type="password" value="...." placeholder="••••••••" id="currentPassword">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="currentStrength"></div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nova Senha</label>
                    <input type="password" placeholder="Nova senha" id="newPassword">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="newStrength"></div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" placeholder="Confirme a nova senha" id="confirmPassword">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="confirmStrength"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions-footer">
            <a href="dashboard.php" class="btn btn-cancel">Cancelar</a>
            <button type="submit" class="btn btn-save">Salvar Alterações</button>
        </div>
    </div>

    <script>
        // Criar partículas de fundo
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Tamanho aleatório
                const size = Math.random() * 5 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posição aleatória
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Atraso aleatório na animação
                particle.style.animationDelay = `${Math.random() * 15}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // JavaScript para simular a abertura do seletor de arquivo ao clicar em "Alterar Foto"
        document.getElementById('alterarFotoBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('photoUpload').click();
        });

        // Pré-visualizar a imagem selecionada
        document.getElementById('photoUpload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let imgElement = document.getElementById('profileImage');
                    let photoContainer = document.querySelector('.profile-photo');
                    let placeholder = document.querySelector('.profile-photo-placeholder');

                    if (!imgElement) {
                        imgElement = document.createElement('img');
                        imgElement.id = 'profileImage';
                        imgElement.alt = 'Foto de Perfil';
                        photoContainer.appendChild(imgElement);
                        if (placeholder) {
                            placeholder.style.display = 'none';
                        }
                    }
                    imgElement.src = e.target.result;
                    imgElement.style.display = 'block';
                    
                    // Efeito de brilho ao adicionar imagem
                    photoContainer.style.boxShadow = '0 0 20px rgba(0, 198, 255, 0.7)';
                    setTimeout(() => {
                        photoContainer.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
                    }, 1000);
                }
                reader.readAsDataURL(file);
            }
        });

        // Simulação de validação de força da senha
        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('newStrength');
            let strength = 0;
            
            if (password.length > 0) strength += 20;
            if (password.length >= 8) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            strengthBar.style.width = `${strength}%`;
            
            // Cor baseada na força
            if (strength < 40) {
                strengthBar.style.background = 'var(--error)';
            } else if (strength < 80) {
                strengthBar.style.background = '#ffdd59';
            } else {
                strengthBar.style.background = 'var(--success)';
            }
        });

        // Efeito de confirmação de senha
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            const strengthBar = document.getElementById('confirmStrength');
            
            if (confirmPassword.length === 0) {
                strengthBar.style.width = '0%';
                return;
            }
            
            if (newPassword === confirmPassword) {
                strengthBar.style.width = '100%';
                strengthBar.style.background = 'var(--success)';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.background = 'var(--error)';
            }
        });

        // Efeito de hover nos botões
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Inicializar partículas
        createParticles();
    </script>
</body>
</html>