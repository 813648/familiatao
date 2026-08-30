<?php
// admin/visitas.php
// Protecção básica por password POST ou HTTP Basic (ajuste conforme preferir)

// CONFIGURAÇÃO: definir uma password forte
$ADMIN_PASSWORD = '1960';

// Autenticação simples via HTTP Basic se disponível
$authorized = false;
if (isset($_SERVER['PHP_AUTH_USER'])) {
  // neste caso só checamos password
  if (isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_PW'] === $ADMIN_PASSWORD) $authorized = true;
}

// Fallback via form POST (por comodidade)
if (!$authorized && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['password']) && $_POST['password'] === $ADMIN_PASSWORD) $authorized = true;
}

// Se não autorizado, mostrar form de login
if (!$authorized) {
  // Enviar cabeçalho basic auth para browser pedir credenciais (opcional)
  header('WWW-Authenticate: Basic realm="Área Administrativa"');
  http_response_code(401);
  // Simples página de login (form)
  echo '<!doctype html><html><head><meta charset="utf-8"><title>Login</title></head><body>';
  echo '<h2>Autenticação necessária</h2>';
  echo '<form method="post"><input type="password" name="password" placeholder="Password"/><button type="submit">Entrar</button></form>';
  echo '</body></html>';
  exit;
}

// Se chegámos aqui, estamos autorizados
$logFile = __DIR__ . '/../docs/visitas.txt';
$lines = [];
if (file_exists($logFile)) {
  // ler todo o ficheiro (para ficheiros muito grandes, use leitura por stream)
  $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  // inverter para mostrar mais recentes primeiro
  $lines = array_reverse($lines);
}

echo '<!doctype html><html><head><meta charset="utf-8"><title>Registo de Visitas</title>';
echo '<style>body{font-family:system-ui,Arial;} table{border-collapse:collapse;width:100%;} td,th{padding:6px;border-bottom:1px solid #eee;font-family:monospace;}</style>';
echo '</head><body>';
echo '<h2>Registo de Visitas</h2>';
echo '<p>Total de linhas: ' . count($lines) . '</p>';
echo '<table><thead><tr><th>#</th><th>Entrada</th></tr></thead><tbody>';
$idx = count($lines);
foreach ($lines as $line) {
  echo '<tr><td>' . $idx . '</td><td>' . htmlspecialchars($line) . '</td></tr>';
  $idx--;
}
echo '</tbody></table>';
echo '</body></html>';
