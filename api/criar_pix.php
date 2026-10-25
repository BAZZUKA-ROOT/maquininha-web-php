<?php
require_once __DIR__ . '/../includes/mp.php';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$valor = money_to_float($input['valor'] ?? 0);
$descricao = trim($input['descricao'] ?? 'Venda maquininha');
$email = trim($input['email'] ?? 'cliente@email.com');
if ($valor <= 0) json_out(['ok' => false, 'message' => 'Informe um valor válido.'], 422);

$vendaId = criar_venda($valor, 'pix', $descricao, null, $email);
$payload = [
    'transaction_amount' => $valor,
    'description' => $descricao ?: 'Venda maquininha',
    'payment_method_id' => 'pix',
    'payer' => ['email' => $email ?: 'cliente@email.com'],
    'external_reference' => (string)$vendaId,
];
if (WEBHOOK_URL) $payload['notification_url'] = WEBHOOK_URL;
$res = mp_request('POST', '/v1/payments', $payload);
if (!$res['ok']) {
    atualizar_venda($vendaId, ['status' => 'erro', 'erro' => json_encode($res['data'], JSON_UNESCAPED_UNICODE)]);
    json_out(['ok' => false, 'message' => 'Erro ao gerar Pix.', 'detalhes' => $res['data']], $res['status'] ?: 500);
}
$d = $res['data'];
$status = status_normalizado($d['status'] ?? 'pendente');
$qrBase64 = $d['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;
$qrText = $d['point_of_interaction']['transaction_data']['qr_code'] ?? null;
$ticket = $d['point_of_interaction']['transaction_data']['ticket_url'] ?? null;
atualizar_venda($vendaId, [
    'status' => $status,
    'mp_payment_id' => $d['id'] ?? null,
    'qr_code_base64' => $qrBase64,
    'qr_code_text' => $qrText,
    'recibo_url' => $ticket,
]);
json_out(['ok' => true, 'venda_id' => $vendaId, 'payment_id' => $d['id'] ?? null, 'status' => $status, 'qr_code_base64' => $qrBase64, 'qr_code' => $qrText, 'ticket_url' => $ticket]);
