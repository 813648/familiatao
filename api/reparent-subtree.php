<?php
// /api/reparent-subtree.php
// POST JSON: { "id": "<nodeId>", "newParent": "<parentId>", "token": null, "password": "<pw>" }
// Response JSON: { "status":"ok" } or { "status":"error","error":"mensagem" }
// WARNING: This script edits a JSON file on disk. Adapt to DB in production.

header('Content-Type: application/json; charset=UTF-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'invalid_json']);
    exit;
}

if (empty($data['id']) || !isset($data['newParent'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'missing_parameters']);
    exit;
}

$nodeId = (string)$data['id'];
$newParent = (string)$data['newParent'];

// Simple auth: accept token OR password. Token handling is placeholder.
$allowed = false;
if (!empty($data['token'])) {
    // If you implement tokens, validate here.
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

// Path to tree JSON (adjust to your layout)
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

if (!isset($tree[$nodeId])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'node_not_found']);
    exit;
}
if (!isset($tree[$newParent])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'new_parent_not_found']);
    exit;
}

// Prevent reparenting into own descendant (or itself)
function collect_descendants($id, $tree, &$out) {
    $children = isset($tree[$id]['filhos']) ? $tree[$id]['filhos'] : [];
    foreach ($children as $c) {
        if (!in_array($c, $out, true)) {
            $out[] = $c;
            collect_descendants($c, $tree, $out);
        }
    }
}
$desc = [];
collect_descendants($nodeId, $tree, $desc);
if (in_array($newParent, $desc, true) || $newParent === $nodeId) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'invalid_target_descendant']);
    exit;
}

// Update relations: remove from old parent and add to newParent
$oldParent = isset($tree[$nodeId]['parent']) ? $tree[$nodeId]['parent'] : null;
if ($oldParent && isset($tree[$oldParent]['filhos'])) {
    $tree[$oldParent]['filhos'] = array_values(array_filter($tree[$oldParent]['filhos'], function($x) use ($nodeId){ return $x !== $nodeId; }));
}

$tree[$nodeId]['parent'] = $newParent;
if (!isset($tree[$newParent]['filhos']) || !is_array($tree[$newParent]['filhos'])) $tree[$newParent]['filhos'] = [];
if (!in_array($nodeId, $tree[$newParent]['filhos'], true)) $tree[$newParent]['filhos'][] = $nodeId;

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
