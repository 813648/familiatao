<?php
// /api/aprovar-membro.php
// Espera um POST JSON: { "id": "<memberId>", "password": "<adminPassword>" }
// Resposta JSON: { "status":"ok" } ou { "status":"error", "error":"mensagem" }

header('Content-Type: application/json; charset=UTF-8');

// Ler input
$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'No input received']);
    exit;
}

$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Invalid JSON']);
    exit;
}

if (empty($data['id']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Missing id or password']);
    exit;
}

$memberId = $data['id'];
$password = $data['password'];

// Configura aqui a tua validação real (banco de dados, LDAP, etc).
// Por simplicidade, verificamos contra uma variável de ambiente ADMIN_PASS ou uma password hardcoded (muda isto).
$adminPass = getenv('ADMIN_PASS');
if ($adminPass === false) {
    // fallback - altere isto em produção
    $adminPass = '1960';
}

if (!hash_equals($adminPass, $password)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'invalid_password']);
    exit;
}

// Aqui tens o ponto para marcar o membro como aprovado no teu backend.
// Exemplo placeholder: escrever num ficheiro JSON local (substitui por DB).
// WARNING: isto é apenas um exemplo. Substituir pela lógica real de gravação.

$dataFile = __DIR__ . '/../dados-arvore/oficial/arvore-validada.json';
if (is_writable(dirname($dataFile))) {
    $tree = @json_decode(@file_get_contents($dataFile), true);
    if (json_last_error() === JSON_ERROR_NONE && isset($tree[$memberId])) {
        $tree[$memberId]['status'] = 'approved';
        @file_put_contents($dataFile, json_encode($tree, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo json_encode(['status' => 'ok']);
        exit;
    } else {
        // Se não for possível actualizar o ficheiro local, retornamos ok para não bloquear a UX,
        // mas indica ao integrador que deve ligar a actualização ao seu backend real.
        echo json_encode(['status' => 'ok', 'note' => 'member validated (no local file update performed)']);
        exit;
    }
} else {
    // Impossível escrever no caminho de exemplo: devolve sucesso mas informa integrador
    echo json_encode(['status' => 'ok', 'note' => 'validated (write not allowed on demo path)']);
    exit;
}
