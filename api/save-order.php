<?php
// /api/save-order.php
// POST JSON: { "parentId": "<parentId>", "filhos": ["id1","id2",...], "token": null, "password": "<pw>" }
// Response JSON: { "status":"ok" } or { "status":"error","error":"mensagem" }
// This updates only the order of filhos for a single parent in the JSON tree.

header('Content-Type: application/json; charset=UTF-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'invalid_json']);
    exit;
}

if (empty($data['parentId']) || !isset($data['filhos']) || !is_array($data['filhos'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'missing_parameters']);
    exit;
}

$parentId = (string)$data['parentId'];
$filhos = array_values($data['filhos']);

// Auth (token or password)
$allowed = false;
if (!empty($data['token'])) {
    // token validation placeholder
    $allowed = true;
} elseif (!empty($data['password'])) {
    $admin = getenv('ADMIN_PASS');
    if ($admin === false) $admin = '1960';
    if (hash_equals($admin, (string)$data['password'])) $allowed = true;
}

if (!$allowed) {
    http_response_code(401);
    echo json_encode(['status'=>'error','error'=>'unauthorized']);
    exit;
}

// Data file
$dataFile = __DIR__ . '/../dados-arvore/oficial/arvore-validada.json';
if (!is_readable($dataFile) || !is_writable(dirname($dataFile))) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>'data_file_not_writable']);
    exit;
}

$rawTree = file_get_contents($dataFile);
$tree = json_decode($rawTree, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($tree)) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>'invalid_tree_data']);
    exit;
}

if (!isset($tree[$parentId])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'parent_not_found']);
    exit;
}

// Validate filhos contain only existing children of parent (to avoid accidental injection)
$currentChildren = isset($tree[$parentId]['filhos']) && is_array($tree[$parentId]['filhos']) ? $tree[$parentId]['filhos'] : [];
$allowedSet = array_flip($currentChildren);
$cleanOrdered = [];
foreach ($filhos as $f) {
    if (isset($allowedSet[$f])) $cleanOrdered[] = $f;
}
// If none valid, error
if (count($cleanOrdered) === 0) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'no_valid_children']);
    exit;
}

// Assign new order (keep only valid children, any missing children append to end preserving previous order)
$remaining = array_values(array_filter($currentChildren, function($x) use ($cleanOrdered) { return !in_array($x, $cleanOrdered, true); }));
$tree[$parentId]['filhos'] = array_merge($cleanOrdered, $remaining);

// Write atomically
$tempFile = $dataFile . '.tmp';
if (file_put_contents($tempFile, json_encode($tree, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>'write_failed']);
    exit;
}
if (!rename($tempFile, $dataFile)) {
    @unlink($tempFile);
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>'atomic_rename_failed']);
    exit;
}

echo json_encode(['status'=>'ok']);
