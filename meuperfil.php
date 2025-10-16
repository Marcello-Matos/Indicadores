<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Nike</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --nike-black: #111111;
            --nike-gray: #757575;
            --nike-light-gray: #f5f5f5;
            --nike-white: #ffffff;
            --nike-error: #e4002b;
            --nike-success: #007c49;
            --nike-info: #0066cc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--nike-white);
            color: var(--nike-black);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo svg {
            width: 60px;
            height: 22px;
        }

        .card {
            background: var(--nike-white);
            border-radius: 4px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e5e5;
            position: relative;
        }

        h1 {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            color: var(--nike-gray);
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d5d5d5;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.2s;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--nike-black);
        }

        .error-message {
            color: var(--nike-error);
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .birth-date {
            display: flex;
            gap: 10px;
        }

        .birth-date select {
            flex: 1;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .checkbox-group input {
            margin-right: 10px;
            margin-top: 3px;
        }

        .checkbox-group label {
            font-size: 12px;
            line-height: 1.4;
            margin-bottom: 0;
        }

        .checkbox-group a {
            color: var(--nike-black);
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background-color: var(--nike-black);
            color: var(--nike-white);
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }

        .btn:hover:not(:disabled) {
            background-color: #333;
        }

        .btn:disabled {
            background-color: #e5e5e5;
            color: #a5a5a5;
            cursor: not-allowed;
        }

        .code-inputs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .code-inputs input {
            flex: 1;
            text-align: center;
            font-size: 18px;
            font-weight: 500;
            padding: 12px;
        }

        .resend-code {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .resend-code a {
            color: var(--nike-black);
            text-decoration: underline;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .resend-code a:hover:not(.disabled) {
            opacity: 0.7;
        }
        
        .resend-code a.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            text-decoration: none;
        }

        .debug-code {
            display: none;
            background: #f0f8ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: var(--nike-info);
            border: 2px dashed var(--nike-info);
        }
        
        .feedback-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease-in-out;
            border-radius: 4px;
        }
        
        .feedback-overlay.visible {
            opacity: 1;
            pointer-events: all;
        }
        
        .feedback-box {
            background: var(--nike-white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 300px;
        }
        
        .feedback-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .feedback-box p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .feedback-box .btn {
            width: 100%;
        }
        
        .feedback-icon.success-icon { color: var(--nike-success); }
        .feedback-icon.error-icon { color: var(--nike-error); }
        .feedback-icon.info-icon { color: var(--nike-info); }
        
        .profile-image-section { text-align: center; margin-bottom: 20px; }
        .profile-image-container { width: 120px; height: 120px; border-radius: 50%; background-color: var(--nike-light-gray); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px dashed #d5d5d5; cursor: pointer; position: relative; }
        .profile-image-container img { width: 100%; height: 100%; object-fit: cover; }
        .profile-image-placeholder { font-size: 40px; color: var(--nike-gray); }
        .profile-image-text { font-size: 14px; color: var(--nike-gray); }
        .hidden { display: none; }
        .password-requirements { font-size: 12px; color: var(--nike-gray); margin-top: 5px; }
        .requirement { margin-bottom: 3px; }
        .requirement.met { color: var(--nike-success); }
        .progress-bar { height: 4px; background-color: #e5e5e5; border-radius: 2px; margin-top: 5px; overflow: hidden; }
        .progress { height: 100%; background-color: var(--nike-success); width: 0%; transition: width 0.3s; }
        
        .username-note {
            font-size: 12px;
            color: var(--nike-gray);
            margin-top: 5px;
            font-style: italic;
        }

        .simple-password-note {
            font-size: 12px;
            color: var(--nike-gray);
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="feedback-overlay" class="feedback-overlay">
            <div class="feedback-box">
                <div id="feedback-icon" class="feedback-icon"></div>
                <p id="feedback-message"></p>
                <button class="btn" onclick="hideFeedback()">OK</button>
            </div>
        </div>
        
        <div class="logo">
            <svg viewBox="0 0 60 22" fill="currentColor">
                <path d="M12.5 0L25 22H0L12.5 0zM37.5 0L50 22H25L37.5 0z"></path>
            </svg>
        </div>

        <div class="card">
            <div class="step active" id="step1">
                <h1>Cadastre-se ao nossos indicadores</h1>
                <p class="subtitle">Insira seu email para começar</p>

                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" placeholder="seu@email.com">
                    <div class="error-message" id="email-error">Por favor, insira um email válido</div>
                </div>

                <button class="btn" id="send-code-btn">Enviar Código</button>
            </div>

            <div class="step" id="step2">
                <h1>Verifique seu Email</h1>
                <p class="subtitle">Enviamos um código para <span id="email-display">seu@email.com</span></p>
                
                <div id="debug-code" class="debug-code" style="display: none;">
                    CÓDIGO DE VERIFICAÇÃO (DEBUG): <span id="debug-code-number"></span>
                </div>

                <div class="code-inputs">
                    <input type="text" maxlength="1" class="code-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-input" inputmode="numeric">
                </div>

                <button class="btn" id="verify-code-btn" disabled>Verificar Código</button>

                <div class="resend-code">
                    Não recebeu o código? <a id="resend-code">Reenviar</a>
                </div>
            </div>

            <div class="step" id="step3">
                <h1>Criar sua Conta</h1>
                <p class="subtitle">Complete suas informações</p>

                <div class="profile-image-section">
                    <div class="profile-image-container" id="profile-image-container">
                        <div class="profile-image-placeholder">👤</div>
                        <img id="profile-image" class="hidden">
                    </div>
                    <p class="profile-image-text">Clique para adicionar uma foto</p>
                    <input type="file" id="image-upload" accept="image/*" class="hidden">
                </div>

                <div class="form-group">
                    <label for="fullName">Nome Completo*</label>
                    <input type="text" id="fullName" placeholder="Seu nome completo">
                    <div class="error-message" id="fullName-error">Por favor, insira seu nome completo</div>
                </div>

                <div class="form-group">
                    <label for="username">Usuário*</label>
                    <input type="text" id="username" placeholder="Nome de usuário">
                    <div class="error-message" id="username-error">Por favor, insira um nome de usuário</div>
                    <div class="username-note">Este será seu login no sistema</div>
                </div>

                <div class="form-group">
                    <label for="password">Senha*</label>
                    <input type="password" id="password" placeholder="Senha (mínimo 4 caracteres)">
                    <div class="error-message" id="password-error">A senha deve ter pelo menos 4 caracteres</div>
                    
                    <div class="password-requirements">
                        <div class="requirement" id="req-length">Mínimo de 4 caracteres</div>
                    </div>
                    
                    <div class="simple-password-note">Pode usar números, letras ou qualquer combinação</div>
                    
                    <div class="progress-bar">
                        <div class="progress" id="password-strength"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Data de Nascimento</label>
                    <div class="birth-date">
                        <select id="birth-day">
                            <option value="">Dia</option>
                        </select>
                        <select id="birth-month">
                            <option value="">Mês</option>
                        </select>
                        <select id="birth-year">
                            <option value="">Ano</option>
                        </select>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="terms">
                    <label for="terms">Concordo com a Política de Privacidade e os Termos de Uso da Nike.</label>
                </div>

                <button class="btn" id="create-account-btn" disabled>Criar Conta</button>
            </div>

            <div class="step" id="step4">
                <h1>Conta Criada!</h1>
                <p class="subtitle">Sua conta foi criada com sucesso.</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <div style="font-size: 48px; color: var(--nike-success);">✓</div>
                </div>
                
                <p style="text-align: center; margin-bottom: 30px;">Agora você pode ter acesso aos indicadores.</p>
                
                <button class="btn" id="continue-btn">Continuar</button>
            </div>
        </div>
    </div>

    <script>
        const BACKEND_URL = 'send_verification.php';

        // Elementos DOM
        const steps = document.querySelectorAll('.step');
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('email-error');
        const sendCodeBtn = document.getElementById('send-code-btn');
        const emailDisplay = document.getElementById('email-display');
        const codeInputs = document.querySelectorAll('.code-input');
        const verifyCodeBtn = document.getElementById('verify-code-btn');
        const resendCodeLink = document.getElementById('resend-code');
        const debugCode = document.getElementById('debug-code');
        const debugCodeNumber = document.getElementById('debug-code-number');
        const profileImageContainer = document.getElementById('profile-image-container');
        const profileImage = document.getElementById('profile-image');
        const imageUpload = document.getElementById('image-upload');
        
        // Novos campos
        const fullNameInput = document.getElementById('fullName');
        const fullNameError = document.getElementById('fullName-error');
        const usernameInput = document.getElementById('username');
        const usernameError = document.getElementById('username-error');
        const passwordInput = document.getElementById('password');
        const passwordError = document.getElementById('password-error');
        const createAccountBtn = document.getElementById('create-account-btn');
        const termsCheckbox = document.getElementById('terms');
        const continueBtn = document.getElementById('continue-btn');

        // Variáveis de estado
        let userEmail = '';
        let userImage = null;
        let resendTimer = 0;
        let resendInterval = null;

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            populateBirthDate();
            setupEventListeners();
            
            // ESCONDER OS REQUISITOS COMPLEXOS DA SENHA
            // Essas linhas estavam dando erro porque os elementos não existiam. Removidas.
        });

        // FUNÇÕES DE UTILIDADE
        function showFeedback(message, type) {
            const overlay = document.getElementById('feedback-overlay');
            const iconDiv = document.getElementById('feedback-icon');
            const messageP = document.getElementById('feedback-message');
            
            iconDiv.className = `feedback-icon ${type}-icon`;
            
            if (type === 'success') iconDiv.innerHTML = '✓';
            else if (type === 'error') iconDiv.innerHTML = '✕';
            else iconDiv.innerHTML = 'i';
            
            messageP.textContent = message;
            overlay.classList.add('visible');
        }

        function hideFeedback() {
            document.getElementById('feedback-overlay').classList.remove('visible');
        }

        function showError(errorElement, message) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        function hideError(errorElement) {
            errorElement.style.display = 'none';
        }
        
        function goToStep(stepNumber) {
            steps.forEach(step => {
                step.classList.remove('active');
            });
            document.getElementById(`step${stepNumber}`).classList.add('active');
        }

        // API FETCH
        async function apiFetch(action, data = {}) {
            try {
                const response = await fetch(BACKEND_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ...data, action: action })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Erro HTTP/PHP Fatal:', errorText);
                    throw new Error(`Erro de servidor. Status: ${response.status}. Veja o console para detalhes.`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('Erro de API/Rede:', error);
                return { success: false, message: 'Erro de conexão ou servidor. Verifique a URL do backend e se o PHP está rodando.' };
            }
        }

        // PASSO 1: ENVIAR CÓDIGO
        async function sendVerificationCode() {
            const email = emailInput.value.trim();
            
            if (!isValidEmail(email)) {
                showError(emailError, 'Por favor, insira um email válido.');
                return;
            }
            
            hideError(emailError);
            sendCodeBtn.disabled = true;
            sendCodeBtn.textContent = 'ENVIANDO...';
            
            const result = await apiFetch('send_code', { email: email });
            
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = 'ENVIAR CÓDIGO';
            
            if (result.success) {
                userEmail = email;
                emailDisplay.textContent = email;
                goToStep(2);
                startResendTimer();
                showFeedback(result.message, 'success');
            } else {
                showFeedback(result.message || 'Erro ao enviar código. Tente novamente.', 'error');
                showError(emailError, result.message || 'Erro ao enviar código. Tente novamente.');
            }
        }
        
        function startResendTimer() {
            if (resendInterval) clearInterval(resendInterval);
            resendTimer = 60;
            resendCodeLink.textContent = `Reenviar em ${resendTimer}s`;
            resendCodeLink.classList.add('disabled');
            resendCodeLink.removeEventListener('click', resendCode);
            
            resendInterval = setInterval(() => {
                resendTimer--;
                resendCodeLink.textContent = `Reenviar em ${resendTimer}s`;
                
                if (resendTimer <= 0) {
                    clearInterval(resendInterval);
                    resendCodeLink.textContent = 'Reenviar';
                    resendCodeLink.classList.remove('disabled');
                    resendCodeLink.addEventListener('click', resendCode);
                }
            }, 1000);
        }

        function resendCode(e) {
             if (e.target.classList.contains('disabled')) return;
             if (resendInterval) clearInterval(resendInterval);
             sendVerificationCode();
        }

        // PASSO 2: VERIFICAR CÓDIGO
        function checkCodeInputs() {
            const code = Array.from(codeInputs).map(input => input.value).join('');
            verifyCodeBtn.disabled = code.length !== 6;
        }

        async function verifyCode() {
            const enteredCode = Array.from(codeInputs).map(input => input.value).join('');
            
            verifyCodeBtn.disabled = true;
            verifyCodeBtn.textContent = 'VERIFICANDO...';
            
            const result = await apiFetch('verify_code', {
                email: userEmail,
                code: enteredCode
            });

            verifyCodeBtn.disabled = false;
            verifyCodeBtn.textContent = 'VERIFICAR CÓDIGO';
            
            if (result.success) {
                showFeedback(result.message, 'success');
                goToStep(3);
            } else {
                showFeedback(result.message || 'Erro desconhecido na verificação.', 'error');
                
                codeInputs.forEach(input => {
                    input.value = '';
                    input.style.borderColor = 'var(--nike-error)';
                });
                codeInputs[0].focus();
                
                setTimeout(() => {
                    codeInputs.forEach(input => {
                        input.style.borderColor = '#d5d5d5';
                    });
                }, 2000);
            }
        }
        
        // PASSO 3: CRIAR CONTA - VALIDAÇÃO SIMPLIFICADA
        function validatePassword() {
            const password = passwordInput.value;
            
            // APENAS VERIFICA SE TEM PELO MENOS 4 CARACTERES
            const isValid = password.length >= 4;
            
            document.getElementById('req-length').classList.toggle('met', isValid);
            
            // Barra de progresso baseada apenas no comprimento
            let strength = 0;
            if (password.length >= 4) strength = 100; // 4+ caracteres = 100%
            
            document.getElementById('password-strength').style.width = `${strength}%`;
            
            // Mostrar/ocultar erro
            if (password.length < 4 && password.length > 0) {
                showError(passwordError, 'A senha deve ter pelo menos 4 caracteres');
            } else {
                hideError(passwordError);
            }
            
            validateForm();
        }

        function validateForm() {
            // VALIDAÇÃO SIMPLIFICADA - APENAS 4 CARACTERES MÍNIMO
            const passwordValid = passwordInput.value.length >= 4;
            const fullNameValid = fullNameInput.value.trim() !== '';
            const usernameValid = usernameInput.value.trim() !== '';
            const termsAccepted = termsCheckbox.checked;
            
            createAccountBtn.disabled = !(fullNameValid && usernameValid && passwordValid && termsAccepted);
        }

        async function createAccount() {
            if (createAccountBtn.disabled) {
                showFeedback('Por favor, preencha todos os campos obrigatórios e aceite os termos.', 'error');
                return;
            }

            const day = document.getElementById('birth-day').value;
            const month = document.getElementById('birth-month').value;
            const year = document.getElementById('birth-year').value;
            
            if (day && month && year && !isValidDate(year, month, day)) {
                 showFeedback('Data de Nascimento inválida.', 'error');
                 return;
            }

            createAccountBtn.disabled = true;
            createAccountBtn.textContent = 'CRIANDO CONTA...';
            
            const userData = {
                email: userEmail,
                // CORRIGIDO: Envia o nome completo e o usuário
                fullName: fullNameInput.value.trim(),
                username: usernameInput.value.trim(), 
                password: passwordInput.value,
                birthDate: { day, month, year }
            };
            
            const result = await apiFetch('register_user', userData);

            createAccountBtn.disabled = false;
            createAccountBtn.textContent = 'CRIAR CONTA';
            
            if (result.success) {
                showFeedback(result.message, 'success');
                goToStep(4);
            } else {
                showFeedback(result.message || 'Erro desconhecido ao tentar criar a conta.', 'error');
            }
        }
        
        // CONFIGURAÇÃO DOS EVENT LISTENERS
        function setupEventListeners() {
            sendCodeBtn.addEventListener('click', sendVerificationCode);
            
            codeInputs.forEach((input, index) => {
                input.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 1);
                    if (e.target.value.length === 1 && index < codeInputs.length - 1) {
                        codeInputs[index + 1].focus();
                    }
                    checkCodeInputs();
                });
                
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                        codeInputs[index - 1].focus();
                    }
                });
            });
            
            verifyCodeBtn.addEventListener('click', verifyCode);
            
            profileImageContainer.addEventListener('click', () => { imageUpload.click(); });
            imageUpload.addEventListener('change', handleImageUpload);
            
            // EVENT LISTENERS
            fullNameInput.addEventListener('input', validateForm);
            usernameInput.addEventListener('input', validateForm);
            passwordInput.addEventListener('input', validatePassword);
            termsCheckbox.addEventListener('change', validateForm);
            
            createAccountBtn.addEventListener('click', createAccount);
            
            continueBtn.addEventListener('click', () => {
                showFeedback('Redirecionando para tela inicial...', 'info');
                window.location.href = 'login.php';
            });
            
            if (resendTimer <= 0) {
                resendCodeLink.addEventListener('click', resendCode);
            }
        }
        
        // FUNÇÕES AUXILIARES
        function populateBirthDate() {
            const daySelect = document.getElementById('birth-day');
            for (let i = 1; i <= 31; i++) {
                const option = document.createElement('option');
                option.value = String(i).padStart(2, '0');
                option.textContent = i;
                daySelect.appendChild(option);
            }
            
            const monthSelect = document.getElementById('birth-month');
            const months = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];
            months.forEach((month, index) => {
                const option = document.createElement('option');
                option.value = String(index + 1).padStart(2, '0');
                option.textContent = month;
                monthSelect.appendChild(option);
            });
            
            const yearSelect = document.getElementById('birth-year');
            const currentYear = new Date().getFullYear();
            for (let i = currentYear; i >= currentYear - 100; i--) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                yearSelect.appendChild(option);
            }
        }

        function handleImageUpload(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    profileImage.src = event.target.result;
                    profileImage.classList.remove('hidden');
                    document.querySelector('.profile-image-placeholder').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        function isValidDate(year, month, day) {
            const date = new Date(year, month - 1, day);
            return date.getFullYear() == year && date.getMonth() + 1 == month && date.getDate() == day;
        }
    </script>
</body>
</html>