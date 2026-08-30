<?php
// submeter-rascunho.php (versão com OPTIONS/GET/POST e debug)
// Coloque este ficheiro em /api/submeter-rascunho.php (substituir o existente)

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
$debug_file = __DIR__ . '/submeter_debug.log';

// Funções utilitárias
function dbg($m) {
    global $debug_file;
    @file_put_contents($debug_file, date('c') . " - " . $m . PHP_EOL, FILE_APPEND);
}
function send_json($data, $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Cabeçalhos CORS/ comuns (ajuste se necessário)
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Tratar preflight
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
dbg("Incoming request method: $method; URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "; Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'N/A'));

// Responder a OPTIONS com sucesso
if ($method === 'OPTIONS') {
    dbg("OPTIONS preflight responded");
    http_response_code(204);
    exit;
}

// GET: mostrar formulário simples para teste manual
if ($method === 'GET') {
    dbg("GET received - serving debug HTML");
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Submeter rascunho - debug</title>
    <h2>Submeter rascunho - debug</h2>
    <form id="f">
    <textarea id="json" style="width:90%;height:200px;">{"meta":{"autor":"web"},"arvore":[]}</textarea><br>
    <button type="button" onclick="send()">Enviar via fetch</button>
    </form>
    <pre id="out"></pre>
    <script>
    async function send(){
      const out=document.getElementById("out");
      const data=document.getElementById("json").value;
      out.textContent = "Enviando...";
      try{
        const r = await fetch(location.pathname, {
          method: "POST",
          headers: {"Content-Type":"application/json"},
          body: data
        });
        const text = await r.text();
        out.textContent = "HTTP " + r.status + "\\n" + text;
      }catch(e){
        out.textContent = "Erro: "+e;
      }
    }
    </script>';
    exit;
}

// POST: aceite múltiplos formatos
$raw = file_get_contents('php://input');
dbg("Raw body length: " . strlen((string)$raw));

$jsonData = null;
if (!empty($raw)) {
    $rawTrim = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $decoded = json_decode($rawTrim, true);
    if ($decoded !== null) {
        $jsonData = $decoded;
        dbg("JSON decoded from raw body");
    } else {
        dbg("Raw body not valid JSON; raw start: " . substr($rawTrim,0,400));
    }
}

// Campo POST form 'json'
if ($jsonData === null && !empty($_POST['json'])) {
    $decoded = json_decode($_POST['json'], true);
    if ($decoded !== null) {
        $jsonData = $decoded;
        dbg("JSON decoded from POST field 'json'");
    } else {
        dbg("POST field 'json' not valid JSON: " . substr($_POST['json'],0,400));
    }
}

// Upload file field 'json'
if ($jsonData === null && !empty($_FILES['json']) && is_uploaded_file($_FILES['json']['tmp_name'])) {
    $fileContent = file_get_contents($_FILES['json']['tmp_name']);
    $decoded = json_decode($fileContent, true);
    if ($decoded !== null) {
        $jsonData = $decoded;
        dbg("JSON decoded from uploaded file 'json'");
    } else {
        dbg("Uploaded file 'json' not valid JSON");
    }
}

// Fallback campo 'data'
if ($jsonData === null && !empty($_POST['data'])) {
    $decoded = json_decode($_POST['data'], true);
    if ($decoded !== null) {
        $jsonData = $decoded;
        dbg("JSON decoded from POST field 'data'");
    } else {
        dbg("POST field 'data' not valid JSON");
    }
}

if ($jsonData === null) {
    dbg("No JSON obtained; returning 400");
    send_json(['status'=>'error','msg'=>'Corpo vazio ou JSON inválido. Envie raw JSON no corpo ou use campo form "json".','debug_log'=>basename($debug_file)], 400);
}

// Validar tipo
if (!is_array($jsonData)) {
    dbg("JSON is not array/object");
    send_json(['status'=>'error','msg'=>'JSON deve ser um objecto/array','debug_log'=>basename($debug_file)], 400);
}

// Gerar nome seguro
try {
    $filename = 'rascunho_' . time() . '_' . bin2hex(random_bytes(4)) . '.json';
} catch (Exception $e) {
    $filename = 'rascunho_' . time() . '_' . uniqid() . '.json';
}
$path = PENDENTES_DIR . $filename;

// Garantir diretoria
if (!is_dir(PENDENTES_DIR)) {
    if (!@mkdir(PENDENTES_DIR, 0755, true)) {
        dbg("Falha ao criar diretoria: " . PENDENTES_DIR);
        send_json(['status'=>'error','msg'=>'Não foi possível criar diretoria pendentes','debug_log'=>basename($debug_file)], 500);
    }
}

// Serializar e gravar
$content = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($content === false) {
    dbg("json_encode error: " . json_last_error_msg());
    send_json(['status'=>'error','msg'=>'Falha a serializar JSON','debug'=>json_last_error_msg(),'debug_log'=>basename($debug_file)], 500);
}

if (@file_put_contents($path, $content . PHP_EOL) === false) {
    dbg("Falha ao escrever ficheiro: $path");
    send_json(['status'=>'error','msg'=>'Falha ao gravar ficheiro','debug_log'=>basename($debug_file)], 500);
}

// Registar log
if (!is_dir(LOGS_DIR)) @mkdir(LOGS_DIR, 0755, true);
@file_put_contents(LOGS_DIR . 'actions.log', date('c') . " - SUBMETIDO - " . $filename . PHP_EOL, FILE_APPEND);
dbg("Saved file: $path");

// Resposta final
send_json(['status'=>'ok','file'=>$filename], 200);


