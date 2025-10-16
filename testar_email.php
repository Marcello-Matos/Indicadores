<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-6.10.0/src/Exception.php';
require 'PHPMailer-6.10.0/src/PHPMailer.php';
require 'PHPMailer-6.10.0/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Configurações do servidor SMTP (Gmail)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'marcelloxmarcellojr@gmail.com';
    // MUDANÇA CRUCIAL: Substitua 'SUA_SENHA_DE_APP_AQUI' pela senha de 16 caracteres gerada pelo Google.
    $mail->Password   = 'aqzv ccdt rgid oxtr'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Remetente e destinatário
    $mail->setFrom('marcelloxmarcellojr@gmail.com', 'Marcello Matos');
    // MUDANÇA: Substitua 'email_valido@dominio.com' por um e-mail de teste real.
    $mail->addAddress('marcelloxmarcellojr@gmail.com', 'Destinatário'); 

    // Conteúdo do e-mail
    $mail->isHTML(true);
    $mail->Subject = 'Teste PHPMailer';
    $mail->Body    = '<h1>PHPMailer Funcionando!</h1><p>Envio de e-mail bem-sucedido.</p>';
    $mail->AltBody = 'PHPMailer Funcionando! Envio de e-mail bem-sucedido.';

    // Enviar
    $mail->send();
    echo '✅ E-mail enviado com sucesso!';
} catch (Exception $e) {
    echo "❌ Erro ao enviar e-mail: {$mail->ErrorInfo}";
}