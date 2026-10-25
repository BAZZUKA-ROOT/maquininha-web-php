<?php
require_once __DIR__ . '/db.php';
function mp_request(string $method, string $endpoint, array $payload = null): array {
    if (!MP_ACCESS_TOKEN || MP_ACCESS_TOKEN === 'COLE_SEU_ACCESS_TOKEN_AQUI') {
        return ['ok' => false, 'status' => 500, 'data' => ['message' => 'Configure o MP_ACCESS_TOKEN no config.php']];
    }
    $url = 'https://api.mercadopago.com' . $endpoint;
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
        'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false) return ['ok' => false, 'status' => 500, 'data' => ['message' => $err]];
    $data = json_decode($raw, true);
    return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'data' => $data ?: ['raw' => $raw]];
}
function status_normalizado(string $mpStatus): string {
    return match ($mpStatus) {
        'approved' => 'aprovado',
        'pending', 'in_process' => 'pendente',
        'rejected' => 'recusado',
        'cancelled' => 'cancelado',
        'refunded', 'charged_back' => 'estornado',
        default => $mpStatus,
    };
}
