<?php
// contact.php

// 1. Incluir a biblioteca PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// 2. Configuração
// Substitua o email abaixo pelo seu próprio email de destino
$to = "admin@livrosusados.com"; 

// 3. Verificação do Método de Requisição
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obter e Sanear os Dados
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = htmlspecialchars(trim($_POST["email"]));
    $subject = htmlspecialchars(trim($_POST["subject"]));
    $message = htmlspecialchars(trim($_POST["message"]));

    // 4. Validação dos Dados
    $errors = [];
    if (empty($name)) $errors[] = "O nome é obrigatório.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de email inválido.";
    if (empty($subject)) $errors[] = "O assunto é obrigatório.";
    if (empty($message)) $errors[] = "A mensagem é obrigatória.";

    // 5. Se não houver erros, envia o email com PHPMailer
    if (empty($errors)) {
        $mail = new PHPMailer(true);
        try {
            // Configurações do Servidor SMTP
            $mail->isSMTP();
            $mail->Host       = 'free.mboxhosting.com'; // Por exemplo: 'smtp.gmail.com' ou 'smtp.seuprovedor.pt'
            $mail->SMTPAuth   = true;
            $mail->Username   = 'admin@livrosusados.com'; // O seu email SMTP
            $mail->Password   = 'Lu56.123';           // A password do seu email SMTP
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use 'ssl' (PHPMailer::ENCRYPTION_SMTPS) ou 'tls' (PHPMailer::ENCRYPTION_STARTTLS)
            $mail->Port       = 465;                      // Porta SMTP (465 para SSL, 587 para TLS)

            // Destinatários e Remetente
            $mail->setFrom('admin@livrosusados.com', 'Familia Tão'); // Use um email real do seu domínio
            $mail->addAddress($to); // Email de destino
            $mail->addReplyTo($email, $name); // Responde diretamente ao utilizador

            // Conteúdo do email
            $mail->isHTML(false); // Envia email como texto simples
            $mail->Subject = $subject;
            $mail->Body    = "Nome: {$name}\nEmail: {$email}\n\nMensagem:\n{$message}";
            $mail->send();

			// O REDIRECIONAMENTO É AGORA FEITO COM JAVASCRIPT, COM UM ATRASO DE 3 SEGUNDOS
			echo "<!DOCTYPE html>
			<html lang='pt'>
			<head>
				<meta charset='UTF-8'>
				<meta name='viewport' content='width=device-width, initial-scale=1.0'>
				<title>Mensagem Enviada</title>
				<style>
					body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
					.container { max-width: 600px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
					.success { color: green; font-size: 1.2em; }
					.redirect-message { margin-top: 20px; font-style: italic; color: #555; }
				</style>
				<meta http-equiv='refresh' content='3;url=index.html'> </head>
			<body>
				<div class='container'>
					<p class='success'>Mensagem enviada com sucesso!</p>

					<p class='redirect-message'>A redirecionar para a página inicial em 3 segundos...</p>
					<a href='index.html'>Clique aqui para redirecionar agora.</a>

				</div>
				<script>
					setTimeout(function() {
						window.location.href = 'index.html';
					}, 3000); // 3000 milissegundos = 3 segundos
				</script>
			</body>
			</html>";
			exit();
			
			
			
        } catch (Exception $e) {
            // Se houver um erro, exibe-o para depuração
            echo "<div class='error'>Ocorreu um erro ao enviar a sua mensagem. Erro: {$mail->ErrorInfo}</div>";
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
    } else {
        // Exibe os erros de validação
        echo "<div class='container'>";
        echo "<div class='error'>Erro no formulário:</div>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . $error . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }

} else {
    echo "<div class='error'>Acesso inválido. Por favor, use o formulário para enviar a mensagem.</div>";
}
?>