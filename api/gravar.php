<?php
require_once __DIR__ . '/auth_retry.php';

// Dados recebidos do frontend
$input = file_get_contents("php://input");
$dados = json_decode($input, true);

// Endpoint real de gravação
$apiUrl = "https://familiatao.ct.ws/api/gravar.php";

// Chamada com retry automático
$resultado = api_post_with_retry($apiUrl, $dados);

http_response_code($resultado['code']);
echo $resultado['body'];
