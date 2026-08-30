<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// Autenticação simples via POST password
$pass = $_POST['password'] ?? '';
if ($pass !== ADMIN_PASSWORD) {
    http_response_code(403);
    echo json_encode(['status'=>'error','msg'=>'Autenticação falhou']);
    exit;
}

$file = $_POST['file'] ?? '';
if (!$file) {
    http_response_code(400);
    echo json_encode(['status'=>'error','msg'=>'Ficheiro não especificado']);
    exit;
}

$src = PENDENTES_DIR . basename($file);
if (!is_file($src)) {
    http_response_code(404);
    echo json_encode(['status'=>'error','msg'=>'Ficheiro não encontrado']);
    exit;
}

// mover para oficial ou misturar com existente conforme regra de negócio
if (!is_dir(OFICIAL_DIR)) mkdir(OFICIAL_DIR, 0750, true);
$dst = OFICIAL_DIR . basename($file);
if (!rename($src, $dst)) {
    http_response_code(500);
    echo json_encode(['status'=>'error','msg'=>'Falha ao mover para oficial']);
    exit;
}

// log
if (is_dir(LOGS_DIR) || mkdir(LOGS_DIR, 0750, true)) {
    $log = date('c') . " - APROVADO - $file\n";
    file_put_contents(LOGS_DIR . 'actions.log', $log, FILE_APPEND);
}

echo json_encode(['status'=>'ok','moved_to'=>$dst]);
