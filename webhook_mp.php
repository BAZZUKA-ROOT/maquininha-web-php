<?php
require_once __DIR__ . '/includes/mp.php';
$paymentId = $_GET['id'] ?? $_GET['data_id'] ?? null;
$body = json_decode(file_get_contents('php://input'), true) ?: [];
if (!$paymentId && isset($body['data']['id'])) $paymentId = $body['data']['id'];
if ($paymentId) {
    $res = mp_request('GET', '/v1/payments/' . preg_replace('/\D+/', '', $paymentId));
    if ($res['ok']) {
        $d = $res['data'];
        $status = status_normalizado($d['status'] ?? 'pendente');
        $stmt = db()->prepare('UPDATE vendas SET status=?, atualizado_em=NOW() WHERE mp_payment_id=?');
        $stmt->execute([$status, $d['id'] ?? $paymentId]);
    }
}
http_response_code(200);
echo 'OK';
