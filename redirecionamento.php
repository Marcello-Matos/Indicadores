<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: index.html');
    exit();
}

// Agora você tem acesso aos dados do usuário:
// $_SESSION['usuario_id']
// $_SESSION['usuario_nome'] 
// $_SESSION['usuario_login']
// $_SESSION['usuario_departamento']
// $_SESSION['cod_departamento']
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Embaquim</title>
</head>
<body>
    <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h1>
    <p>Departamento: <?php echo htmlspecialchars($_SESSION['usuario_departamento']); ?></p>
    <a href="logout.php">Sair</a>
</body>
</html>