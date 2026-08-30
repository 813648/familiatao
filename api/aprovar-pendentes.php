<?php
// /api/aprovar-pendentes.php (versão simples, password em texto em config/admin_pass.txt)
header('Content-Type: application/json; charset=utf-8');
session_start();

// verificação de sessão mínima
if (!isset($_SESSION['user']) || !in_array($_SESSION['role'] ?? '', ['admin','validator'])) {
  http_response_code(403);
  echo json_encode(['error'=>'forbidden']);
  exit;
}

// ler payload
$raw = file_get_contents('php://input');
$j = json_decode($raw, true);
$ids = $j['ids'] ?? null;
$pw = $j['password'] ?? null;

// paths relativos ao webroot (pai de /api)
$webroot = realpath(__DIR__ . '/../');
if ($webroot === false) $webroot = __DIR__ . '/..';
$configDir = $webroot . '/config';
$pwFile = $configDir . '/admin_pass.txt';
$base = $webroot . '/dados-arvore';
$officialFile = $base . '/oficial/arvore-validada.json';
$backupDir = $base . '/backups';
$logDir = $base . '/logs';

// verificar password presente
if ($pw === null) {
  http_response_code(400);
  echo json_encode(['error'=>'missing_password']);
  exit;
}

// ler ficheiro de password em texto
if (!is_file($pwFile) || !is_readable($pwFile)) {
  http_response_code(500);
  echo json_encode(['error'=>'password_store_missing']);
  exit;
}
$stored = trim(@file_get_contents($pwFile));
if ($stored === '') {
  http_response_code(500);
  echo json_encode(['error'=>'password_store_empty']);
  exit;
}

// comparar (texto simples)
if (!hash_equals($stored, $pw)) {
  http_response_code(401);
  echo json_encode(['error'=>'invalid_password']);
  exit;
}

// garantir dirs e ficheiro oficial
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
if (!file_exists($officialFile)) {
  http_response_code(404);
  echo json_encode(['error'=>'no_official']);
  exit;
}

// carregar e aprovar
$dataRaw = @file_get_contents($officialFile);
if ($dataRaw === false) { http_response_code(500); echo json_encode(['error'=>'read_failed']); exit; }
$data = json_decode($dataRaw, true) ?? [];

$approved = [];
if ($ids === null) {
  foreach ($data as $k => &$n) {
    if (($n['status'] ?? '') === 'pending') { $n['status'] = 'official'; $approved[] = $k; }
  }
  unset($n);
} else {
  foreach ($ids as $id) {
    if (isset($data[$id]) && ($data[$id]['status'] ?? '') === 'pending') {
      $data[$id]['status'] = 'official';
      $approved[] = $id;
    }
  }
}

// backup e gravação
$stamp = date('YmdHis');
@copy($officialFile, $backupDir . "/arvore-validada-$stamp.json");
$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) { http_response_code(500); echo json_encode(['error'=>'encode_failed']); exit; }
$written = @file_put_contents($officialFile, $json, LOCK_EX);
if ($written === false) { http_response_code(500); echo json_encode(['error'=>'write_failed']); exit; }

// registo simples
$log = ['action'=>'approve','user'=>$_SESSION['user'],'time'=>gmdate('c'),'approved'=>$approved,'ip'=>$_SERVER['REMOTE_ADDR']];
@file_put_contents($logDir . '/actions.jsonl', json_encode($log, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

echo json_encode(['status'=>'ok','approved_count'=>count($approved),'approved'=>$approved]);
