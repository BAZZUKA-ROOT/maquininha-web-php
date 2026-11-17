<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
session_start();
require_once __DIR__ . '/../includes/db.php';

if (isset($_GET['sair'])) {
    session_destroy();
    header('Location: ./');
    exit;
}

if (isset($_POST['senha'])) {
    if (password_verify((string)$_POST['senha'], ADMIN_SENHA_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        header('Location: ./');
        exit;
    }
    $erro = 'Credenciais inválidas.';
}

function money($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['admin_ok'])): ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Admin - <?= h(NOME_LOJA) ?></title>
  <link rel="stylesheet" href="../assets/style.css?v=4">
</head>
<body>
<div class="bg-glow bg-one"></div><div class="bg-glow bg-two"></div>
<main class="shell admin-shell">
  <section class="login-card card glass">
    <div class="brand-row login-brand">
      <div class="brand-mark">MW</div>
      <div><span class="eyebrow">Painel seguro</span><h1>Histórico de vendas</h1></div>
    </div>
    <p class="note">O acesso exige senha configurada no ambiente do servidor. Não há senha padrão.</p>
    <?php if(!empty($erro)): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>
    <form method="post" class="form-card">
      <label>Senha do administrador</label>
      <input type="password" name="senha" placeholder="Digite a senha" autofocus required>
      <button class="primary full">Entrar no painel</button>
    </form>
    <a class="fullcenter muted-link" href="../">Voltar para nova venda</a>
  </section>
</main>
</body>
</html><?php exit; endif;

$pdo = db();
$q = trim($_GET['q'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(id = :id_exact OR cliente_nome LIKE :q OR cliente_email LIKE :q OR descricao LIKE :q OR mp_payment_id LIKE :q)';
    $params[':id_exact'] = ctype_digit($q) ? (int)$q : 0;
    $params[':q'] = '%' . $q . '%';
}
if ($statusFiltro !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $statusFiltro;
}
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT * FROM vendas $sqlWhere ORDER BY id DESC LIMIT 150");
$stmt->execute($params);
$vendas = $stmt->fetchAll();

$totalHoje = $pdo->query("SELECT COALESCE(SUM(valor),0) t FROM vendas WHERE status='aprovado' AND DATE(criado_em)=CURDATE()")->fetch()['t'];
$totalMes = $pdo->query("SELECT COALESCE(SUM(valor),0) t FROM vendas WHERE status='aprovado' AND YEAR(criado_em)=YEAR(CURDATE()) AND MONTH(criado_em)=MONTH(CURDATE())")->fetch()['t'];
$qtdHoje = $pdo->query("SELECT COUNT(*) t FROM vendas WHERE DATE(criado_em)=CURDATE()")->fetch()['t'];
$qtdAprovadas = $pdo->query("SELECT COUNT(*) t FROM vendas WHERE status='aprovado'")->fetch()['t'];
$qtdPendentes = $pdo->query("SELECT COUNT(*) t FROM vendas WHERE status IN ('pendente','iniciado')")->fetch()['t'];
$qtdRecusadas = $pdo->query("SELECT COUNT(*) t FROM vendas WHERE status IN ('recusado','cancelado','rejected')")->fetch()['t'];

function badgeClass($status) {
    $s = strtolower((string)$status);
    if (str_contains($s, 'apro')) return 'approved';
    if (str_contains($s, 'recus') || str_contains($s, 'cancel') || str_contains($s, 'reject')) return 'refused';
    return 'pending';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Admin - <?= h(NOME_LOJA) ?></title>
  <link rel="stylesheet" href="../assets/style.css?v=4">
</head>
<body>
<div class="bg-glow bg-one"></div><div class="bg-glow bg-two"></div>
<main class="shell admin-shell-wide">
  <section class="card glass hero admin-hero">
    <div class="brand-row">
      <div class="brand-mark">MW</div>
      <div><span class="eyebrow">Admin</span><h1><?= h(NOME_LOJA) ?></h1><p class="hero-sub">Controle de vendas, Pix, cartão e status.</p></div>
    </div>
    <div class="admin-actions">
      <a class="ghost-btn" href="../">Nova venda</a>
      <a class="ghost-btn danger-link" href="?sair=1">Sair</a>
    </div>
  </section>

  <section class="admin-stats admin-stats-wide">
    <div class="stat stat-primary"><span>Aprovado hoje</span><strong><?= money($totalHoje) ?></strong></div>
    <div class="stat"><span>Aprovado no mês</span><strong><?= money($totalMes) ?></strong></div>
    <div class="stat"><span>Vendas hoje</span><strong><?= (int)$qtdHoje ?></strong></div>
    <div class="stat"><span>Aprovadas</span><strong><?= (int)$qtdAprovadas ?></strong></div>
    <div class="stat"><span>Pendentes</span><strong><?= (int)$qtdPendentes ?></strong></div>
    <div class="stat"><span>Recusadas/canceladas</span><strong><?= (int)$qtdRecusadas ?></strong></div>
  </section>

  <section class="card panel filter-panel">
    <form method="get" class="filter-form">
      <div>
        <label>Buscar venda</label>
        <input name="q" value="<?= h($q) ?>" placeholder="ID, cliente, e-mail, descrição ou ID Mercado Pago">
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <option value="">Todos</option>
          <?php foreach(['aprovado','pendente','iniciado','recusado','cancelado'] as $st): ?>
            <option value="<?= h($st) ?>" <?= $statusFiltro===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="primary filter-btn">Filtrar</button>
    </form>
  </section>

  <section class="card panel table-panel">
    <div class="section-head">
      <div><span class="eyebrow">Últimas vendas</span><h2><?= count($vendas) ?> registro(s)</h2></div>
    </div>
    <?php if (!$vendas): ?>
      <p class="info">Nenhuma venda encontrada.</p>
    <?php else: ?>
      <div class="sales-table">
        <div class="sales-head"><span>ID</span><span>Venda</span><span>Cliente</span><span>Pagamento</span><span>Status</span></div>
        <?php foreach($vendas as $v): ?>
          <article class="sale-row">
            <div class="sale-id">#<?= (int)$v['id'] ?></div>
            <div>
              <strong><?= money($v['valor']) ?></strong>
              <small><?= h($v['criado_em']) ?></small>
              <?php if(!empty($v['descricao'])): ?><small><?= h($v['descricao']) ?></small><?php endif; ?>
            </div>
            <div>
              <strong><?= h($v['cliente_nome'] ?: 'Cliente não informado') ?></strong>
              <small><?= h($v['cliente_email'] ?: '-') ?></small>
            </div>
            <div>
              <strong><?= strtoupper(h($v['metodo'])) ?></strong>
              <small>MP: <?= h($v['mp_payment_id'] ?: '-') ?></small>
              <?php if(!empty($v['recibo_url'])): ?><a class="mini-link" target="_blank" href="<?= h($v['recibo_url']) ?>">Abrir recibo</a><?php endif; ?>
            </div>
            <div><span class="badge <?= badgeClass($v['status']) ?>"><?= h($v['status']) ?></span><?php if(!empty($v['erro'])): ?><small class="error-small"><?= h($v['erro']) ?></small><?php endif; ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
