<?php
// =========================================================================
// ATENÇÃO: É NECESSÁRIO QUE VOCÊ TENHA INICIADO A SESSÃO ANTES DESTE CÓDIGO
// Ex: session_start();
// E que a variável de sessão 'usuario_logado' contenha pelo menos o 'id' e o 'nome'.
// =========================================================================

// Inicia a sessão se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------------------
// 1. VERIFICAÇÃO DE LOGIN E CARREGAMENTO DAS INFORMAÇÕES DO USUÁRIO
// -------------------------------------------------------------------------

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit();
}

// Carregar informações do usuário da sessão - CORREÇÃO AQUI
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_login = $_SESSION['usuario_login'] ?? '';
$usuario_departamento = $_SESSION['usuario_departamento'] ?? '';

// -------------------------------------------------------------------------
// 2. CONFIGURAÇÕES DA CONEXÃO COM O BANCO DE DADOS (USANDO PDO_SQLSRV)
// -------------------------------------------------------------------------

// CONFIGURAÇÕES DO SEU SERVIDOR SQL SERVER
$host = 'srv-03';              // Nome ou IP do seu servidor SQL Server
$username = 'sa';              // Usuário (login SQL Server)
$password = 'aplak2904&';      // Senha
$database = 'dbkalpa';         // Nome do banco

/**
 * Tenta estabelecer uma conexão com o SQL Server usando PDO.
 * Retorna APENAS o status 'ONLINE' ou 'OFFLINE'.
 * @return string 'ONLINE' ou 'OFFLINE'
 */
function debugConexao($host, $database, $username, $password) {
    // Excluímos a opção PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION do array de opções 
    // no debug para evitar que a exceção seja lançada e o script quebre, retornando
    // apenas o status de falha.
    $dsn = "sqlsrv:Server=$host;Database=$database";
    
    try {
        // Tenta criar a conexão
        $debug_conn = new PDO($dsn, $username, $password);
        $debug_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT); // Apenas para garantir que não lance exceção aqui
        return 'ONLINE';
    } catch (PDOException $e) {
        return 'OFFLINE';
    }
}

// Verifica o status da conexão
$status_conexao = debugConexao($host, $database, $username, $password); 
$modo_demo = ($status_conexao === 'OFFLINE');

// -------------------------------------------------------------------------
// 3. CONEXÃO PRINCIPAL PARA USO NO SISTEMA
// -------------------------------------------------------------------------
$conn = null;

if (!$modo_demo) {
    // Tentar conexão principal (o mesmo código do debug, mas armazena em $conn)
    $dsn = "sqlsrv:Server=$host;Database=$database";
    try {
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Aqui queremos exceções para tratar erros de query
    } catch (PDOException $e) {
        // Falha na conexão após o debug (algo deu errado, talvez permissão ou timeout)
        $modo_demo = true;
        $conn = null;
        error_log("Falha crítica na conexão principal: " . $e->getMessage());
    }
}

// -------------------------------------------------------------------------
// 4. SISTEMA DE PERMISSÕES - CLASSE REFATORADA PARA PDO
// -------------------------------------------------------------------------

class SistemaPermissoes {
    private $conn;
    private $usuario_id;
    private $modo_demo;
    
    public function __construct($db_connection, $usuario_id, $modo_demo = false) {
        $this->conn = $db_connection;
        $this->usuario_id = $usuario_id;
        $this->modo_demo = $modo_demo;
    }

    /**
     * Retorna a lista de todos os usuários (PDO).
     * CORREÇÃO FINAL: Usa aliases (AS) para colunas 'CodUsuario', 'Nome', 'Usuario' 
     * e o nome da tabela 'tbl_Usuarios'.
     * @return array
     */
    public function getUsuarios() {
        if ($this->modo_demo || $this->conn === null) {
            // Retorna dados dummy no modo demonstração
            return [
                ['id' => 1, 'nome' => 'Admin do Sistema (Demo)', 'login' => 'admin', 'departamento' => 'TI'],
                ['id' => 2, 'nome' => 'João Silva (Demo)', 'login' => 'joao.silva', 'departamento' => 'Vendas'],
                ['id' => 3, 'nome' => 'Maria Oliveira (Demo)', 'login' => 'maria.o', 'departamento' => 'Financeiro'],
                ['id' => 4, 'nome' => 'Pedro Rocha (Demo)', 'login' => 'pedro.r', 'departamento' => 'Produção'],
                ['id' => 5, 'nome' => 'Ana Souza (Demo)', 'login' => 'ana.s', 'departamento' => 'Logística'],
            ];
        }

        try {
            // CORREÇÃO: Uso de Aliases para padronizar o retorno e WHERE para desligados
            $sql = "SELECT 
                        CodUsuario AS id, 
                        Nome AS nome, 
                        Usuario AS login, 
                        'Sem Depto' AS departamento 
                    FROM 
                        tbl_Usuarios 
                    WHERE 
                        Desligado = 0 
                    ORDER BY Nome"; 
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erro PDO em getUsuarios(): SQL Tabela/Colunas incorretas. Detalhes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Retorna todos os módulos de permissão disponíveis.
     * @return array
     */
    public function getTodosModulos() {
        // Módulos demo (usados se o modo_demo estiver ativo OU se a consulta falhar)
        $modulos_demo = [
            ['id' => 101, 'nome' => 'Dashboard Geral', 'descricao' => 'Acesso total ao dashboard principal.', 'icone' => 'fa-chart-line', 'categoria' => 'Visualização'],
            ['id' => 102, 'nome' => 'Indicadores de Vendas', 'descricao' => 'Visualizar e editar indicadores de vendas.', 'icone' => 'fa-handshake', 'categoria' => 'Visualização'],
            ['id' => 103, 'nome' => 'Relatórios Gerenciais', 'descricao' => 'Download e visualização de relatórios em PDF/Excel.', 'icone' => 'fa-file-alt', 'categoria' => 'Visualização'],
            ['id' => 201, 'nome' => 'Gerenciar Usuários', 'descricao' => 'Criar, editar e excluir contas de usuários.', 'icone' => 'fa-user-plus', 'categoria' => 'Administração'],
            ['id' => 202, 'nome' => 'Acesso ao SRV-03', 'descricao' => 'Acesso direto e administrativo ao servidor de banco de dados.', 'icone' => 'fa-server', 'categoria' => 'Administração'],
            ['id' => 301, 'nome' => 'Indicadores de Produção', 'descricao' => 'Acesso e edição de dados de produção.', 'icone' => 'fa-industry', 'categoria' => 'Operacional'],
        ];

        if ($this->modo_demo || $this->conn === null) {
            return $modulos_demo;
        }

        // Lógica PDO real para buscar módulos no banco (Ex: SELECT * FROM modulos)
        try {
             // ATENÇÃO: Verifique se sua tabela de módulos é realmente 'modulos'
             $sql = "SELECT id, nome, descricao, icone, categoria FROM modulos ORDER BY categoria, nome";
             $stmt = $this->conn->prepare($sql);
             $stmt->execute();
             $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
             
             // Se a consulta retornar algo, usa os dados do banco. Senão, usa os dados demo como fallback silencioso
             return $modulos ?: $modulos_demo;
             
        } catch (PDOException $e) {
             error_log("Erro PDO em getTodosModulos(): Verifique a tabela 'modulos'. Detalhes: " . $e->getMessage());
             return $modulos_demo; // Retorna demo em caso de erro no banco
        }
    }
    
    /**
     * NOVO: Retorna as permissões de TODOS os usuários em um array mapeado por ID de usuário.
     * @return array [ user_id => [modulo_id1, modulo_id2, ...], ... ]
     */
    public function getAllUsersPermissoes() {
        $users = $this->getUsuarios(); // Usado para garantir que todos os usuários tenham uma entrada
        $all_permissions = [];

        // No modo demo, usa a lógica de demo por ID
        if ($this->modo_demo || $this->conn === null) {
            $demo_perms = [
                1 => [101, 102, 103, 201, 202, 301],
                2 => [101, 102, 103],
                3 => [101, 103],
                4 => [101, 301],
                5 => [101],
            ];
            foreach ($users as $user) {
                $all_permissions[$user['id']] = $demo_perms[$user['id']] ?? [101]; // Padrão 101
            }
            return $all_permissions;
        }

        // Modo real: busca todas as permissões de uma vez
        try {
            // ATENÇÃO: Verifique se sua tabela de permissões é realmente 'user_permissoes'
            $sql = "SELECT user_id, modulo_id FROM user_permissoes";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $row) {
                $user_id = $row['user_id'];
                $modulo_id = $row['modulo_id'];

                if (!isset($all_permissions[$user_id])) {
                    $all_permissions[$user_id] = [];
                }
                $all_permissions[$user_id][] = (int)$modulo_id;
            }
            
            // Garante que todo usuário da lista tem um array de permissões, mesmo que vazio
            foreach ($users as $user) {
                if (!isset($all_permissions[$user['id']])) {
                    $all_permissions[$user['id']] = [];
                }
            }

            return $all_permissions;

        } catch (PDOException $e) {
            error_log("Erro PDO em getAllUsersPermissoes(): Verifique a tabela 'user_permissoes'. Detalhes: " . $e->getMessage());
            // Em caso de erro, retorna um array que simula permissões vazias para todos
            foreach ($users as $user) {
                 $all_permissions[$user['id']] = [];
            }
            return $all_permissions;
        }
    }
}


// -------------------------------------------------------------------------
// 5. INSTANCIAÇÃO DA CLASSE E GERAÇÃO DE DADOS PARA O FRONTEND
// -------------------------------------------------------------------------

// CORREÇÃO: Usando as variáveis de sessão corretas que foram carregadas no início
$usuario_id_logado = $usuario_id;
$usuario_nome_logado = $usuario_nome;

/**
 * Função auxiliar para gerar iniciais para o avatar.
 */
function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    
    if (isset($parts[0])) {
        $initials .= strtoupper($parts[0][0]);
    }
    // Pega a inicial da última palavra se houver mais de uma
    if (count($parts) > 1 && isset($parts[count($parts) - 1])) {
        $initials .= strtoupper($parts[count($parts) - 1][0]);
    }
    return $initials;
}

// CORREÇÃO: Instanciação da classe que resolve o Fatal Error.
$permissoes = new SistemaPermissoes($conn, $usuario_id_logado, $modo_demo);

// Busca dados a serem injetados no JavaScript
$usuarios = $permissoes->getUsuarios(); 
$modulos = $permissoes->getTodosModulos();
$all_users_permissions = $permissoes->getAllUsersPermissoes(); // Nova função para carregar todas as permissões

// -------------------------------------------------------------------------
// 6. CÓDIGO HTML/FRONTEND
// -------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Permissões | Embaquim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ESTILOS COMPLETOS DO SEU SISTEMA (mantidos inalterados) */
        :root {
            --primary-gradient-start: #6a11cb;
            --primary-gradient-end: #2575fc;
            --success-color: #00d9a6;
            --accent-color: #00f2fe;
            --dark-bg: #0f0f23;
            --card-bg: rgba(255, 255, 255, 0.08);
            --text-light: #f0f0f0;
            --text-muted: rgba(255, 255, 255, 0.7);
            --header-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-light);
            overflow-x: hidden;
        }

        .debug-status {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #1e1e1e;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 10000;
            font-size: 12px;
            border-left: 4px solid #00d9a6;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .debug-status.offline {
            border-left-color: #ff6b6b;
        }

        .demo-banner {
            background: linear-gradient(45deg, #ff6b6b, #ffa726);
            color: white;
            padding: 10px 20px;
            text-align: center;
            font-weight: 600;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10000;
        }

        .app-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            height: 100vh;
            margin-top: <?php echo $modo_demo ? '45px' : '0'; ?>;
        }

        /* Sidebar */
        .sidebar {
            background: rgba(34, 0, 83, 0.95);
            padding: 0;
            border-right: 1px solid var(--header-border);
        }

        .logo-section {
            padding: 25px 15px;
            border-bottom: 1px solid var(--header-border);
            text-align: center;
        }

        .nav-menu ul {
            list-style: none;
            padding: 25px 0;
        }

        .nav-item {
            padding: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: var(--text-light);
            text-decoration: none;
            padding: 18px 25px;
            transition: all 0.4s ease;
        }

        .nav-link:hover, .nav-item.active .nav-link {
            background: rgba(64, 0, 176, 0.8);
            padding-left: 30px;
        }

        .nav-link i {
            width: 26px;
            margin-right: 15px;
            font-size: 20px;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            background: rgba(15, 15, 35, 0.4);
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 35px;
            background: rgba(15, 15, 35, 0.6);
            border-bottom: 1px solid var(--header-border);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            position: relative;
        }

        .avatar i {
            font-size: 40px;
            color: var(--accent-color);
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
        }

        .user-name {
            font-weight: 600;
        }

        /* Sistema de Permissões */
        .admin-container {
            flex: 1;
            margin: 20px;
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .admin-header {
            background: linear-gradient(90deg, var(--primary-gradient-start), var(--primary-gradient-end));
            color: white;
            padding: 25px 30px;
            position: relative;
        }

        .demo-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #ff4757;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .admin-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-title i {
            font-size: 2rem;
        }

        .admin-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            min-height: 600px;
            flex: 1;
        }

        .users-panel {
            background: rgba(0, 0, 0, 0.3);
            border-right: 1px solid var(--header-border);
            padding: 20px;
        }

        .permissions-panel {
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            display: flex;
            flex-direction: column;
        }

        .panel-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--header-border);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 1rem;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .users-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .user-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.4s ease;
            border: 1px solid transparent;
        }

        .user-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .user-item.active {
            background: rgba(64, 0, 176, 0.8);
            border-color: var(--accent-color);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, var(--primary-gradient-start), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-department {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .permissions-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .permission-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--header-border);
            transition: all 0.4s ease;
        }

        .permission-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .permission-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .permission-info {
            flex: 1;
        }

        .permission-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .permission-name i {
            color: var(--accent-color);
        }

        .permission-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            flex-shrink: 0;
        }

        .toggle-switch input {
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
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success-color);
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .no-user-selected {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .no-user-selected i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--accent-color);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid var(--header-border);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-gradient-start), var(--primary-gradient-end));
            color: white;
        }

        .btn-success {
            background: linear-gradient(45deg, var(--success-color), #00b894);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .permission-category {
            margin-bottom: 25px;
        }

        .category-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--header-border);
        }
        
        /* Estilo para a mensagem de erro da lista de usuários */
        .no-users-warning {
             padding: 10px;
             text-align: center;
             color: #ff4757;
             background-color: rgba(255, 71, 87, 0.1);
             border-radius: 8px;
             border: 1px solid #ff4757;
        }
    </style>
</head>
<body>
    <!-- Debug Status -->
    
    
    <?php if ($modo_demo): ?>
    <div class="demo-banner">
        🚀 MODO DEMONSTRAÇÃO - Não foi possível conectar ao SRV-03. Exibindo dados fictícios.
    </div>
    <?php endif; ?>

    <div class="app-layout">
        
        <aside class="sidebar">
            <div class="logo-section">
                <img src="img/logo2025.png" alt="Logo Embaquim 2025" class="logo-image" style="height: 50px; filter: brightness(0) invert(1);">
            </div>

            <nav class="nav-menu">
                <ul>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item active">
                        <a href="gerenciar_permissoes.php" class="nav-link">
                            <i class="fas fa-users-cog"></i>
                            <span>Gerenciar Permissões</span>
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
                    <input type="text" placeholder="Pesquisar...">
                </div>

                <div class="user-profile">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i>
                        <div class="online-status"></div>
                    </div>
                    <!-- CORREÇÃO: Exibindo o nome do usuário logado -->
                    <span class="user-name"><?php echo htmlspecialchars($usuario_nome_logado); ?></span>
                </div>
            </header>

            <section class="content-area">
                <div class="admin-container">
                    <div class="admin-header">
                        <div class="admin-title">
                            <i class="fas fa-shield-alt"></i>
                            <h1>Gerenciamento de Permissões</h1>
                        </div>
                        <?php if ($status_conexao === 'ONLINE'): ?>
                        <div style="position: absolute; top: 20px; right: 20px; background: var(--success-color); color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                            Conectado ao SRV-03
                        </div>
                        <?php endif; ?>
                        <?php if ($modo_demo): ?>
                        <div class="demo-badge">MODO DEMO</div>
                        <?php endif; ?>
                    </div>

                    <div class="permissions-grid">
                        <div class="users-panel">
                            <div class="panel-title">
                                <i class="fas fa-users"></i>
                                Lista de Usuários
                            </div>
                            <div class="search-box">
                                <input type="text" id="searchUsers" placeholder="Buscar usuário...">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="users-list" id="usersList">
                                <?php
                                if (empty($usuarios) && $status_conexao === 'ONLINE'):
                                ?>
                                    <div class="no-users-warning">
                                        <i class="fas fa-exclamation-triangle" style="color: #ff4757;"></i>
                                        <h4>ATENÇÃO</h4>
                                        <p>A lista de usuários está vazia. Verifique se há usuários não 'Desligado' na tabela **`tbl_Usuarios`** ou se o SQL precisa de ajuste.</p>
                                    </div>
                                <?php
                                endif;
                                // A lista de usuários será gerada pelo JavaScript agora para facilitar a busca/filtro
                                ?>
                            </div>
                        </div>

                        <div class="permissions-panel">
                            <div class="panel-title">
                                <i class="fas fa-key"></i>
                                Permissões do Usuário
                            </div>
                            
                            <div id="selectedUserInfo" style="display: none; margin-bottom: 20px;" class="user-item active">
                                <div class="user-avatar" id="selectedUserAvatar">U</div>
                                <div class="user-info">
                                    <div class="user-name" id="selectedUserName">Nome do Usuário</div>
                                    <div class="user-department" id="selectedUserLogin">login@usuario</div>
                                </div>
                            </div>
                            
                            <div id="permissionsContainer" style="flex: 1; display: flex; flex-direction: column;">
                                <div class="no-user-selected" id="noUserSelected" style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%;">
                                    <i class="fas fa-user-plus"></i>
                                    <h3>Selecione um usuário</h3>
                                    <p>Clique em um usuário da lista ao lado para gerenciar suas permissões</p>
                                </div>
                                <div id="userPermissions" style="display: none; flex: 1;">
                                    </div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-primary" id="btnSelectAll" style="display: none;">
                            <i class="fas fa-check-double"></i>
                            Selecionar Todos
                        </button>
                        <button class="btn btn-success" id="btnSavePermissions" style="display: none;" disabled>
                            <i class="fas fa-save"></i>
                            Salvar Permissões
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // 1. DADOS INJETADOS PELO PHP
        const USERS_DATA = <?php echo json_encode($usuarios, JSON_NUMERIC_CHECK); ?>;
        const MODULES_DATA = <?php echo json_encode($modulos, JSON_NUMERIC_CHECK); ?>;
        const ALL_PERMISSIONS = <?php echo json_encode($all_users_permissions, JSON_NUMERIC_CHECK); ?>; // Permissões de todos
        const IS_DEMO_MODE = <?php echo json_encode($modo_demo); ?>;

        // 2. ELEMENTOS DOM
        const usersListElement = document.getElementById('usersList');
        const searchUsersInput = document.getElementById('searchUsers');
        const selectedUserInfoElement = document.getElementById('selectedUserInfo');
        const selectedUserNameElement = document.getElementById('selectedUserName');
        const selectedUserLoginElement = document.getElementById('selectedUserLogin');
        const selectedUserAvatarElement = document.getElementById('selectedUserAvatar');
        const noUserSelectedElement = document.getElementById('noUserSelected');
        const userPermissionsElement = document.getElementById('userPermissions');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const btnSavePermissions = document.getElementById('btnSavePermissions');

        // 3. ESTADO GLOBAL
        let selectedUserId = null;
        let originalPermissions = [];
        let currentPermissions = [];

        /**
         * Gera as iniciais do nome.
         * @param {string} name
         * @returns {string}
         */
        function getInitials(name) {
            const parts = name.trim().split(' ');
            let initials = '';
            if (parts.length > 0) {
                initials += parts[0][0].toUpperCase();
            }
            if (parts.length > 1) {
                initials += parts[parts.length - 1][0].toUpperCase();
            }
            return initials;
        }

        /**
         * Renderiza a lista de usuários baseada no filtro.
         * @param {string} filter
         */
        function renderUsersList(filter = '') {
            usersListElement.innerHTML = '';
            const normalizedFilter = filter.toLowerCase().trim();

            const filteredUsers = USERS_DATA.filter(user => 
                user.nome.toLowerCase().includes(normalizedFilter) || 
                (user.login && user.login.toLowerCase().includes(normalizedFilter))
            );

            if (filteredUsers.length === 0 && !normalizedFilter) {
                 // Exibe a mensagem de aviso se não houver usuários e não estiver filtrando
                 if (usersListElement.querySelector('.no-users-warning')) {
                    usersListElement.innerHTML = usersListElement.querySelector('.no-users-warning').outerHTML;
                 }
                 return;
            } else if (filteredUsers.length === 0) {
                usersListElement.innerHTML = `
                    <div class="no-user-selected" style="padding: 10px;">
                        <i class="fas fa-search" style="color: var(--accent-color);"></i>
                        <p>Nenhum usuário encontrado com o termo "${filter}".</p>
                    </div>
                `;
                return;
            }


            filteredUsers.forEach(user => {
                const initials = getInitials(user.nome);
                const isActive = user.id === selectedUserId ? 'active' : '';
                const loginDisplay = user.login || 'N/A';
                
                const userItem = document.createElement('div');
                userItem.className = `user-item ${isActive}`;
                userItem.setAttribute('data-user-id', user.id);
                userItem.setAttribute('data-user-name', user.nome);
                userItem.setAttribute('data-user-login', loginDisplay);
                userItem.setAttribute('data-user-initials', initials);
                userItem.innerHTML = `
                    <div class="user-avatar">${initials}</div>
                    <div class="user-info">
                        <div class="user-name">${user.nome}</div>
                        <div class="user-department">${user.departamento || 'Sem Depto'}</div>
                    </div>
                `;
                userItem.addEventListener('click', () => selectUser(user.id));
                usersListElement.appendChild(userItem);
            });
        }
        
        /**
         * Renderiza os checkboxes de permissão.
         */
        function renderPermissions() {
            if (!selectedUserId) return;

            userPermissionsElement.style.display = 'block';
            noUserSelectedElement.style.display = 'none';
            btnSelectAll.style.display = 'inline-flex';
            btnSavePermissions.style.display = 'inline-flex';
            
            userPermissionsElement.innerHTML = '';
            
            // Agrupa módulos por categoria
            const categorizedModules = MODULES_DATA.reduce((acc, module) => {
                const category = module.categoria || 'Outros';
                if (!acc[category]) {
                    acc[category] = [];
                }
                acc[category].push(module);
                return acc;
            }, {});

            for (const category in categorizedModules) {
                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'permission-category';
                categoryDiv.innerHTML = `<div class="category-title">${category}</div>`;
                
                const modulesList = document.createElement('div');
                modulesList.className = 'permissions-list';

                categorizedModules[category].forEach(module => {
                    const isChecked = currentPermissions.includes(module.id);
                    
                    const moduleItem = document.createElement('div');
                    moduleItem.className = 'permission-item';
                    moduleItem.innerHTML = `
                        <div class="permission-header">
                            <div class="permission-info">
                                <div class="permission-name">
                                    <i class="fas ${module.icone || 'fa-cog'}"></i>
                                    ${module.nome}
                                </div>
                                <div class="permission-desc">${module.descricao}</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" data-module-id="${module.id}" ${isChecked ? 'checked' : ''}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    `;
                    
                    modulesList.appendChild(moduleItem);
                });
                
                categoryDiv.appendChild(modulesList);
                userPermissionsElement.appendChild(categoryDiv);
            }
            
            // Adiciona listener para os toggles
            userPermissionsElement.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', handlePermissionToggle);
            });

            checkChanges(); // Verifica o estado inicial dos botões
        }
        
        /**
         * Seleciona um usuário e carrega suas permissões.
         * @param {number} userId
         */
        function selectUser(userId) {
            // Remove a classe 'active' do usuário anterior
            const prevActive = usersListElement.querySelector('.user-item.active');
            if (prevActive) {
                prevActive.classList.remove('active');
            }

            // Define o novo ID selecionado
            selectedUserId = userId;
            
            // Adiciona a classe 'active' ao novo usuário
            const newActive = usersListElement.querySelector(`.user-item[data-user-id="${userId}"]`);
            if (newActive) {
                newActive.classList.add('active');
            }
            
            const selectedUser = USERS_DATA.find(u => u.id === userId);
            
            // Atualiza o painel de informações do usuário
            if (selectedUser) {
                selectedUserInfoElement.style.display = 'flex';
                selectedUserNameElement.textContent = selectedUser.nome;
                selectedUserLoginElement.textContent = selectedUser.login;
                selectedUserAvatarElement.textContent = getInitials(selectedUser.nome);
            }

            // Carrega as permissões do usuário
            // Usamos a lista pré-carregada (ALL_PERMISSIONS)
            originalPermissions = ALL_PERMISSIONS[userId] || [];
            // Cria uma cópia para o estado atual que pode ser modificado
            currentPermissions = [...originalPermissions]; 
            
            renderPermissions();
        }
        
        /**
         * Trata o toggle de permissão e atualiza o estado.
         * @param {Event} event
         */
        function handlePermissionToggle(event) {
            const moduleId = parseInt(event.target.getAttribute('data-module-id'));
            const isChecked = event.target.checked;

            if (isChecked) {
                if (!currentPermissions.includes(moduleId)) {
                    currentPermissions.push(moduleId);
                }
            } else {
                const index = currentPermissions.indexOf(moduleId);
                if (index > -1) {
                    currentPermissions.splice(index, 1);
                }
            }
            
            checkChanges();
        }
        
        /**
         * Verifica se as permissões atuais são diferentes das originais.
         */
        function checkChanges() {
            // Ordena os arrays para comparação fácil
            const sortedOriginal = [...originalPermissions].sort((a, b) => a - b).join(',');
            const sortedCurrent = [...currentPermissions].sort((a, b) => a - b).join(',');
            
            const hasChanges = sortedOriginal !== sortedCurrent;
            
            // O botão de salvar só fica ativo se houver mudanças e não estiver no modo demo
            btnSavePermissions.disabled = IS_DEMO_MODE || !hasChanges;
            
            // O botão de selecionar todos só aparece se houver um usuário selecionado
            btnSelectAll.style.display = selectedUserId ? 'inline-flex' : 'none';
        }
        
        /**
         * Seleciona/Desseleciona todas as permissões.
         */
        function toggleAllPermissions() {
            const allModuleIds = MODULES_DATA.map(m => m.id);
            const isAllChecked = allModuleIds.every(id => currentPermissions.includes(id));
            
            if (isAllChecked) {
                // Desseleciona todos
                currentPermissions = [];
            } else {
                // Seleciona todos
                currentPermissions = allModuleIds;
            }
            
            // Atualiza os checkboxes e o status de salvar
            renderPermissions(); // Força a re-renderização para atualizar os toggles
            checkChanges();
        }
        
        /**
         * Lógica de salvamento das permissões (MOCK).
         */
        function savePermissions() {
            if (IS_DEMO_MODE) {
                alert('Modo Demonstração: O salvamento está desabilitado.');
                return;
            }
            
            if (!selectedUserId) {
                alert('Nenhum usuário selecionado.');
                return;
            }

            // Obtém as permissões que foram adicionadas e removidas
            const permissionsToSave = {
                user_id: selectedUserId,
                permissions: currentPermissions
            };

            // --------------------------------------------------------------------------------------------------
            // !!! PONTO CRÍTICO: VOCÊ PRECISA DE UM ARQUIVO PHP (EX: save_permissions.php) PARA RECEBER ESTES DADOS
            // E EXECUTAR AS QUERIES DE UPDATE/INSERT/DELETE NO BANCO DE DADOS (PDO)
            // --------------------------------------------------------------------------------------------------
            
            // MOCK DE CHAMADA AJAX/FETCH
            alert(`Salvando permissões para o usuário ID: ${selectedUserId}.\nNovas permissões: ${currentPermissions.join(', ')}\n\n(Implementação real de AJAX/Fetch necessária!)`);

            // Após o sucesso do salvamento:
            originalPermissions = [...currentPermissions];
            ALL_PERMISSIONS[selectedUserId] = originalPermissions; // Atualiza a lista global
            checkChanges();
        }

        // 4. INICIALIZAÇÃO DE EVENT LISTENERS
        document.addEventListener('DOMContentLoaded', () => {
            renderUsersList();
            searchUsersInput.addEventListener('input', (e) => renderUsersList(e.target.value));
            btnSelectAll.addEventListener('click', toggleAllPermissions);
            btnSavePermissions.addEventListener('click', savePermissions);

            // Se houver usuários na lista, seleciona o primeiro por padrão
            if (USERS_DATA.length > 0) {
                 selectUser(USERS_DATA[0].id);
            }
        });
    </script>
</body>
</html>