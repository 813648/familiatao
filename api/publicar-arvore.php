<?php
// /api/publicar-arvore.php
header('Content-Type: application/json; charset=utf-8');
session_start();

// AUTENTICAÇÃO DE DESENVOLVIMENTO
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'] ?? '', ['admin','validator'])) {
  http_response_code(403);
  echo json_encode(['error'=>'forbidden']);
  exit;
}

// Ler payload
$raw = file_get_contents('php://input');
$newTree = json_decode($raw, true);
if ($newTree === null) {
  http_response_code(400);
  echo json_encode(['error'=>'invalid_json','raw'=>substr($raw,0,1000)]);
  exit;
}

// Determinar base apontando para o webroot pai de /api
$webroot = realpath(__DIR__ . '/../');
if ($webroot === false) $webroot = __DIR__ . '/..';
$base = $webroot . '/dados-arvore';
$officialFile = $base . '/oficial/arvore-validada.json';
$backupDir = $base . '/backups';
$logDir = $base . '/logs';
$debugFile = $logDir . '/debug.log';

// Garantir pastas
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
if (!is_dir($logDir)) mkdir($logDir, 0755, true);

// Função de debug
function dbg($msg) {
  global $debugFile;
  $line = gmdate('c') . ' ' . $msg . PHP_EOL;
  @file_put_contents($debugFile, $line, FILE_APPEND | LOCK_EX);
}

// Carregar oldTree se existir
$oldTree = [];
if (file_exists($officialFile)) {
  $oldRaw = @file_get_contents($officialFile);
  if ($oldRaw === false) {
    dbg("ERRO leitura oficial: $officialFile");
  } else {
    $oldTree = json_decode($oldRaw, true) ?? [];
  }
}

// comparar nós (ignora status)
function nodeChanged($oldNode, $newNode) {
  if (!$oldNode) return true;
  $o = $oldNode; $n = $newNode;
  if (is_array($o)) unset($o['status']);
  if (is_array($n)) unset($n['status']);
  return json_encode($o, JSON_UNESCAPED_UNICODE) !== json_encode($n, JSON_UNESCAPED_UNICODE);
}

// Marcar pendentes
foreach ($newTree as $id => &$node) {
  $oldNode = $oldTree[$id] ?? null;
  $changed = nodeChanged($oldNode, $node);
  if ($changed) $node['status'] = 'pending';
  else {
    if (isset($oldNode['status']) && $oldNode['status'] === 'pending') $node['status'] = 'pending';
    else $node['status'] = 'official';
  }
}
unset($node);

// Backup
$stamp = date('YmdHis');
if (file_exists($officialFile)) {
  $copied = @copy($officialFile, $backupDir . "/arvore-validada-$stamp.json");
  if (!$copied) dbg("ERRO backup falhou: $officialFile -> $backupDir/arvore-validada-$stamp.json");
}

// Gravar novo official
$json = json_encode($newTree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
  dbg("ERRO encode json: " . json_last_error_msg());
  http_response_code(500);
  echo json_encode(['error'=>'encode_failed','msg'=>json_last_error_msg()]);
  exit;
}

$written = @file_put_contents($officialFile, $json, LOCK_EX);
if ($written === false) {
  dbg("ERRO gravação: $officialFile (permissões?)");
  http_response_code(500);
  echo json_encode(['error'=>'write_failed','path'=>$officialFile]);
  exit;
}

// Log de ação
$log = ['action'=>'publish','user'=>$_SESSION['user'],'time'=>gmdate('c'),'changed_count'=>count(array_diff_key($newTree,$oldTree))];
@file_put_contents($logDir . '/actions.jsonl', json_encode($log, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

echo json_encode(['status'=>'ok','written_bytes'=>$written,'path'=>$officialFile]);
