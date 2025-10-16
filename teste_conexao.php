<?php
header('Content-Type: text/plain');

$serverName = "192.168.0.8,1433";
$connectionOptions = [
    "Database" => "Indicadores",
    "Uid" => "sa", 
    "PWD" => "aplak2904&",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => false,
    "LoginTimeout" => 5
];

echo "Testando conexão com SQL Server...\n";
echo "Servidor: " . $serverName . "\n";
echo "Database: Indicadores\n";

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo "❌ ERRO NA CONEXÃO:\n";
    $errors = sqlsrv_errors();
    foreach($errors as $error) {
        echo "Código: " . $error['code'] . "\n";
        echo "Mensagem: " . $error['message'] . "\n";
        echo "SQLSTATE: " . $error['SQLSTATE'] . "\n";
    }
} else {
    echo "✅ CONEXÃO BEM SUCEDIDA!\n";
    sqlsrv_close($conn);
}
?>