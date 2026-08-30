<?php
// /api/verify-admin.php
// Recebe JSON { "password": "<pw>" }
// Resposta JSON: { "status":"ok", "token":null } ou { "status":"error", "error":"mensagem" }
// Nota: Por simplicidade devolvemos token nulo. Se quiseres, substitui por JWT/nonce.
header('Content-Type: application/json; charset=UTF-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'invalid_json']);
    exit;
}

if (!isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'missing_password']);
    exit;
}

$pw = (string)$data['password'];

// Lê password admin do ambiente (recomendado) ou usa fallback (muda em produção)
$admin = getenv('ADMIN_PASS');
if ($admin === false) $admin = '1960';

if (!hash_equals($admin, $pw)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'invalid_password']);
    exit;
}

// Se quiseres gerar um token, faz aqui e devolve { token: "..." }
echo json_encode(['status' => 'ok', 'token' => null]);
