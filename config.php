<?php
// config.php - Configurações do banco de dados

return [
    "serverName" => "192.168.0.8,1433", // NOTE: vírgula SEM espaço
    "connectionOptions" => [
        "Database" => "Indicadores",
        "Uid" => "sa",
        "PWD" => "aplak2904&",
        "CharacterSet" => "UTF-8",
        "LoginTimeout" => 5,
        "ReturnDatesAsStrings" => true,
        "TrustServerCertificate" => true, // Para SQL Server recente
        "Encrypt" => false // Se não usar SSL
    ]
];