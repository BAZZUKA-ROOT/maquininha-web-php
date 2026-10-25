<?php
require_once __DIR__ . '/../includes/mp.php';
$id = preg_replace('/\D+/', '', $_GET['payment_id'] ?? '');
if (!$id) json_out(['ok' => false, 'message' => 'payment_id ausente'], 422);
$res = mp_request('GET', '/v1/payments/' . $id);
if (!$res['ok']) json_out(['ok' => false, 'message' => 'Não consegui consultar o pagamento', 'detalhes' => $res['data']], $res['status'] ?: 500);
$d = $res['data'];
$status = status_normalizado($d['status'] ?? 'pendente');
$stmt = db()->prepare('UPDATE vendas SET status=?, atualizado_em=NOW() WHERE mp_payment_id=?');
$stmt->execute([$status, $id]);
json_out(['ok' => true, 'payment_id' => $id, 'status' => $status, 'mp_status' => $d['status'] ?? null]);
