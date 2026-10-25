<?php
require_once __DIR__ . '/../includes/mp.php';

function somente_digitos($v) { return preg_replace('/\D+/', '', (string)$v); }
function email_valido($email) { return (bool)filter_var($email, FILTER_VALIDATE_EMAIL); }
function cpf_valido($cpf) {
    $cpf = somente_digitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) $soma += (int)$cpf[$i] * (($t + 1) - $i);
        $digito = ((10 * $soma) % 11) % 10;
        if ((int)$cpf[$t] !== $digito) return false;
    }
    return true;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$valor = round((float)($input['transaction_amount'] ?? 0), 2);
$descricao = trim($input['description'] ?? 'Venda no cartão');
$nome = trim($input['cardholderName'] ?? '');
$email = trim($input['payer']['email'] ?? '');
$docTipo = trim($input['payer']['identification']['type'] ?? 'CPF');
$docNumero = somente_digitos($input['payer']['identification']['number'] ?? '');
$telefone = somente_digitos($input['phone'] ?? '');
$parcelas = max(1, (int)($input['installments'] ?? 1));

if ($valor <= 0) json_out(['ok' => false, 'message' => 'Valor inválido.'], 422);
if (empty($input['token']) || empty($input['payment_method_id'])) json_out(['ok' => false, 'message' => 'Dados do cartão incompletos. Confira número, validade e CVV.'], 422);
if (!$nome || count(preg_split('/\s+/', $nome)) < 2) json_out(['ok' => false, 'message' => 'Informe o nome completo do titular.'], 422);
if (!email_valido($email)) json_out(['ok' => false, 'message' => 'Informe um e-mail válido do cliente.'], 422);
if (strtoupper($docTipo) === 'CPF' && !cpf_valido($docNumero)) json_out(['ok' => false, 'message' => 'CPF do titular inválido.'], 422);
if ($parcelas < 1 || $parcelas > 24) json_out(['ok' => false, 'message' => 'Quantidade de parcelas inválida.'], 422);

$vendaId = criar_venda($valor, 'cartao', $descricao, $nome, $email);

$payload = [
    'transaction_amount' => $valor,
    'token' => $input['token'],
    'description' => $descricao,
    'installments' => $parcelas,
    'payment_method_id' => $input['payment_method_id'],
    'issuer_id' => $input['issuer_id'] ?? null,
    'capture' => true,
    'binary_mode' => true,
    'payer' => [
        'email' => $email,
        'first_name' => explode(' ', $nome)[0] ?? $nome,
        'last_name' => trim(substr($nome, strlen(explode(' ', $nome)[0] ?? ''))),
        'identification' => [
            'type' => $docTipo ?: 'CPF',
            'number' => $docNumero,
        ],
    ],
    'additional_info' => [
        'items' => [[
            'id' => 'venda-' . $vendaId,
            'title' => $descricao,
            'description' => $descricao,
            'quantity' => 1,
            'unit_price' => $valor,
        ]],
        'payer' => [
            'first_name' => explode(' ', $nome)[0] ?? $nome,
            'last_name' => trim(substr($nome, strlen(explode(' ', $nome)[0] ?? ''))),
            'phone' => [
                'area_code' => strlen($telefone) >= 10 ? substr($telefone, 0, 2) : '',
                'number' => strlen($telefone) >= 10 ? substr($telefone, 2) : $telefone,
            ],
        ],
    ],
    'external_reference' => (string)$vendaId,
    'statement_descriptor' => substr(preg_replace('/[^A-Za-z0-9 ]/', '', NOME_LOJA), 0, 22),
];

if (WEBHOOK_URL) $payload['notification_url'] = WEBHOOK_URL;

$res = mp_request('POST', '/v1/payments', $payload);
if (!$res['ok']) {
    atualizar_venda($vendaId, ['status' => 'erro', 'erro' => json_encode($res['data'], JSON_UNESCAPED_UNICODE)]);
    $msg = $res['data']['message'] ?? 'Pagamento recusado ou erro na API.';
    json_out(['ok' => false, 'message' => $msg, 'detalhes' => $res['data']], $res['status'] ?: 500);
}

$d = $res['data'];
$status = status_normalizado($d['status'] ?? 'pendente');
atualizar_venda($vendaId, [
    'status' => $status,
    'mp_payment_id' => $d['id'] ?? null,
    'recibo_url' => $d['transaction_details']['external_resource_url'] ?? null,
    'erro' => isset($d['status_detail']) ? (string)$d['status_detail'] : null,
]);
json_out(['ok' => true, 'venda_id' => $vendaId, 'payment_id' => $d['id'] ?? null, 'status' => $status, 'status_detail' => $d['status_detail'] ?? null]);
