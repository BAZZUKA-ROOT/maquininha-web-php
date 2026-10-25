<?php
// CONFIGURACAO PRINCIPAL - MAQUININHA WEB
// 1) Crie o banco e importe sql/database.sql
// 2) Coloque suas credenciais do Mercado Pago abaixo
// 3) Acesse /maquininha_web/ no navegador

define('DB_HOST', 'localhost');
define('DB_NAME', 'maquininha_web');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Mercado Pago - producao ou teste
// Access Token: painel Mercado Pago > Desenvolvedores > Credenciais
// Public Key: usada no cartão pelo navegador
define('MP_ACCESS_TOKEN', 'COLE_SEU_ACCESS_TOKEN_AQUI');
define('MP_PUBLIC_KEY', 'COLE_SUA_PUBLIC_KEY_AQUI');

// Dados que aparecem no pagamento/recibo
define('NOME_LOJA', 'Minha Maquininha');
define('MOEDA', 'BRL');
define('WEBHOOK_URL', ''); // opcional: https://seudominio.com/maquininha_web/webhook_mp.php

// Admin simples
define('ADMIN_SENHA', '123456');
