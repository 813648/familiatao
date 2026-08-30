<?php
// config.php - ajustado ao seu alojamento
// Caminho absoluto dentro de DOCUMENT_ROOT
date_default_timezone_set('Europe/Lisbon');
define('DADOS_ARVORE_BASE', '/home/vol18_2/epizy.com/epiz_34085186/familiatao.ct.ws/htdocs/dados-arvore/');

define('PENDENTES_DIR', DADOS_ARVORE_BASE . 'pendentes/');
define('OFICIAL_DIR', DADOS_ARVORE_BASE . 'oficial/');
define('REJEITADAS_DIR', DADOS_ARVORE_BASE . 'rejeitadas/');
define('LOGS_DIR', DADOS_ARVORE_BASE . 'logs/');
define('BACKUPS_DIR', DADOS_ARVORE_BASE . 'backups/');

// Senha de admin temporária — substitua por hash seguro em produção
define('ADMIN_PASSWORD', '1960');


