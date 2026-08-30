<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$files = [];
if (is_dir(PENDENTES_DIR)) {
    $dh = opendir(PENDENTES_DIR);
    while (($file = readdir($dh)) !== false) {
        if (in_array($file, ['.', '..'])) continue;
        $path = PENDENTES_DIR . $file;
        if (is_file($path)) {
            $files[] = [
                'nome' => $file,
                'tamanho' => filesize($path),
                'modificado' => date('c', filemtime($path))
            ];
        }
    }
    closedir($dh);
}

echo json_encode(['status' => 'ok', 'pendentes' => $files]);
