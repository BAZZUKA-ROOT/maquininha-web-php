<?php
require_once __DIR__ . '/../config.php';
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
function json_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function money_to_float($value): float {
    $value = trim((string)$value);
    $value = str_replace(['R$', ' '], '', $value);
    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);
    return round((float)$value, 2);
}
function criar_venda(float $valor, string $metodo, ?string $descricao = null, ?string $cliente_nome = null, ?string $cliente_email = null): int {
    $stmt = db()->prepare('INSERT INTO vendas (valor, metodo, descricao, cliente_nome, cliente_email, status, criado_em) VALUES (?, ?, ?, ?, ?, "iniciado", NOW())');
    $stmt->execute([$valor, $metodo, $descricao, $cliente_nome, $cliente_email]);
    return (int)db()->lastInsertId();
}
function atualizar_venda(int $id, array $dados): void {
    $sets = [];
    $vals = [];
    foreach ($dados as $k => $v) {
        $sets[] = "$k = ?";
        $vals[] = $v;
    }
    if (!$sets) return;
    $vals[] = $id;
    db()->prepare('UPDATE vendas SET ' . implode(', ', $sets) . ', atualizado_em = NOW() WHERE id = ?')->execute($vals);
}
