<?php
// Carregue credenciais pelo ambiente do PHP, jamais pelo Git.
// Arquivos .env nao sao interpretados automaticamente.
function maquininha_required_env($name) {
    $value = getenv($name);
    if ($value === false || trim((string)$value) === '') {
        throw new RuntimeException('Configuracao obrigatoria ausente: ' . $name);
    }
    return (string)$value;
}
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'maquininha_web');
define('DB_USER', maquininha_required_env('DB_USER'));
define('DB_PASS', maquininha_required_env('DB_PASS'));
define('DB_CHARSET', 'utf8mb4');
define('MP_ACCESS_TOKEN', maquininha_required_env('MP_ACCESS_TOKEN'));
define('MP_PUBLIC_KEY', maquininha_required_env('MP_PUBLIC_KEY'));
define('NOME_LOJA', getenv('NOME_LOJA') ?: 'Minha Maquininha');
define('MOEDA', 'BRL');
define('WEBHOOK_URL', getenv('WEBHOOK_URL') ?: '');
// Gere com a funcao password_hash do PHP e salve somente o hash no ambiente.
define('ADMIN_SENHA_HASH', maquininha_required_env('ADMIN_SENHA_HASH'));
