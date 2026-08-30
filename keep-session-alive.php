<?php
// keep-session-alive.php
// Responde com 204 No Content para pings keep-alive
http_response_code(204);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
    echo "<div class='error'>Acesso inválido. Por favor, use o formulário para enviar a mensagem.</div>";
exit;