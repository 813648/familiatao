<?php
// api/log-visit.php
// Regista linhas do tipo:
// Visita 1234 - 2025-10-29 10:51:56 - IP: 188.251.49.244 - Local: Porto, Portugal

// Configuração
$logDir  = __DIR__ . '/../docs';
$logFile = $logDir . '/visitas.txt';
$ctrFile = $logDir . '/visitas_counter.txt';
$geoCacheFile = $logDir . '/geo_cache.json'; // cache simples por IP
$geoCacheTtl = 60 * 60 * 24 * 14; // 14 dias

// Garantir pasta de logs
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

// Timestamp UTC (ajuste timezone se preferir local)
$ts = new DateTime('now', new DateTimeZone('UTC'));
$tsStr = $ts->format('Y-m-d H:i:s');

// Obter IP do cliente (simples)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// --- Contador atómico ---
$visitNumber = 0;
$fp = @fopen($ctrFile, 'c+');
if ($fp) {
  if (flock($fp, LOCK_EX)) {
    $contents = stream_get_contents($fp);
    $current = intval(trim($contents));
    $visitNumber = $current + 1;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, (string)$visitNumber);
    fflush($fp);
    flock($fp, LOCK_UN);
  }
  fclose($fp);
} else {
  $visitNumber = time();
}

// --- Funções utilitárias para geo lookup com cache ---
function load_geo_cache($file) {
  if (!file_exists($file)) return [];
  $json = @file_get_contents($file);
  $data = json_decode($json, true);
  return is_array($data) ? $data : [];
}
function save_geo_cache($file, $data) {
  @file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
}
function geo_lookup_ip($ip, &$cache, $cacheFile, $ttl) {
  if (!$ip || $ip === 'unknown') return null;
  // usar cache
  if (isset($cache[$ip])) {
    $entry = $cache[$ip];
    if (isset($entry['ts']) && (time() - $entry['ts'] < $ttl)) {
      return $entry['data'];
    }
  }
  // consulta externa (ip-api.com) - HTTP simples, sem chave
  $url = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,country,regionName,city,zip,lat,lon,query";
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($ch, CURLOPT_TIMEOUT, 3);
  curl_setopt($ch, CURLOPT_FAILONERROR, false);
  $resp = @curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if (!$resp || $httpCode !== 200) {
    // gravar cache fraco para evitar chamadas repetidas
    $cache[$ip] = ['ts' => time(), 'data' => null];
    save_geo_cache($cacheFile, $cache);
    return null;
  }
  $j = json_decode($resp, true);
  if (!is_array($j) || ($j['status'] ?? '') !== 'success') {
    $cache[$ip] = ['ts' => time(), 'data' => null];
    save_geo_cache($cacheFile, $cache);
    return null;
  }
  $data = [
    'country' => $j['country'] ?? '',
    'region'  => $j['regionName'] ?? '',
    'city'    => $j['city'] ?? '',
    'lat'     => $j['lat'] ?? null,
    'lon'     => $j['lon'] ?? null,
  ];
  $cache[$ip] = ['ts' => time(), 'data' => $data];
  save_geo_cache($cacheFile, $cache);
  return $data;
}

// Carregar cache e obter geo
$geoCache = load_geo_cache($geoCacheFile);
$geo = geo_lookup_ip($ip, $geoCache, $geoCacheFile, $geoCacheTtl);

// Formatar local humano
$localLabel = 'unknown';
if (is_array($geo) && ($geo['city'] || $geo['country'])) {
  $parts = [];
  if (!empty($geo['city'])) $parts[] = $geo['city'];
  if (!empty($geo['country'])) $parts[] = $geo['country'];
  $localLabel = implode(', ', $parts);
}

// Linha a gravar
$line = sprintf("Visita %d - %s - IP: %s - Local: %s\n", $visitNumber, $tsStr, $ip, $localLabel);

// Gravar no ficheiro de log com LOCK_EX
file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

// Responder sem corpo
http_response_code(204);
exit;
