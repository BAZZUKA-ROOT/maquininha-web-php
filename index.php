<?php require_once __DIR__ . '/config.php'; ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= htmlspecialchars(NOME_LOJA) ?> - Maquininha Web</title>
  <meta name="theme-color" content="#111827">
  <link rel="stylesheet" href="assets/style.css?v=3">
  <script src="https://sdk.mercadopago.com/js/v2"></script>
</head>
<body>
<div class="bg-glow bg-one"></div>
<div class="bg-glow bg-two"></div>
<main class="shell">
  <header class="hero card glass">
    <div class="brand-row">
      <div class="brand-mark">MW</div>
      <div>
        <span class="eyebrow">Maquininha Web</span>
        <h1><?= htmlspecialchars(NOME_LOJA) ?></h1>
      </div>
    </div>
    <a href="admin/" class="ghost-btn">Histórico</a>
  </header>

  <nav class="steps card">
    <span class="step active" data-step="1">1 Valor</span>
    <span class="step" data-step="2">2 Forma</span>
    <span class="step" data-step="3">3 Pagamento</span>
  </nav>

  <section id="stepValor" class="terminal card step-panel">
    <div class="terminal-top">
      <span>Nova venda</span>
      <strong id="valorPreview">R$ 0,00</strong>
    </div>

    <label for="valor">Valor da venda</label>
    <div class="money-box">
      <span>R$</span>
      <input id="valor" class="valor" inputmode="numeric" placeholder="0,00" autocomplete="off">
    </div>

    <label for="descricao">Descrição</label>
    <input id="descricao" placeholder="Ex: venda balcão, serviço, produto" autocomplete="off">

    <button class="primary full" onclick="irParaForma()">Continuar</button>
    <div id="valorErro"></div>
  </section>

  <section id="stepForma" class="terminal card step-panel hidden">
    <div class="terminal-top">
      <span>Escolha a forma de pagamento</span>
      <strong id="valorEscolhido">R$ 0,00</strong>
    </div>
    <p class="note compact">Escolha apenas uma opção. Ao selecionar Pix, o cartão fecha. Ao selecionar Cartão, o Pix fecha.</p>
    <div class="pay-grid">
      <button class="pay pix" onclick="selecionarPagamento('pix')">
        <span class="pay-icon">◆</span>
        <span>Pix</span>
        <small>QR Code + copia e cola</small>
      </button>
      <button class="pay cardpay" onclick="selecionarPagamento('cartao')">
        <span class="pay-icon">▣</span>
        <span>Cartão</span>
        <small>Crédito digitado</small>
      </button>
    </div>
    <button class="ghost-wide" onclick="voltarValor()">Alterar valor</button>
  </section>

  <section id="resultadoPix" class="card panel hidden step-panel">
    <div class="section-head">
      <div>
        <span class="eyebrow">Pagamento Pix</span>
        <h2>Mostre o QR Code ao cliente</h2>
      </div>
      <span class="badge pending" id="pixStatusBadge">Pendente</span>
    </div>
    <div id="pixArea"></div>
    <button class="ghost-wide" onclick="trocarForma()">Trocar forma de pagamento</button>
  </section>

  <section id="cartaoBox" class="card panel hidden step-panel">
    <div class="section-head">
      <div>
        <span class="eyebrow">Cartão</span>
        <h2>Pagamento por cartão digitado</h2>
      </div>
      <span class="badge secure">Seguro</span>
    </div>
    <p class="note">Preencha todos os dados do titular. Quanto mais completo, menor a chance de recusa por antifraude. Este site não salva número, validade ou CVV.</p>
    <form id="form-checkout" class="form-card">
      <div class="field-row">
        <div>
          <label>Nome impresso no cartão</label>
          <input id="form-checkout__cardholderName" placeholder="Nome completo" autocomplete="cc-name" />
        </div>
        <div>
          <label>E-mail do cliente</label>
          <input id="form-checkout__cardholderEmail" placeholder="cliente@email.com" autocomplete="email" />
        </div>
      </div>

      <label>Número do cartão</label>
      <div id="form-checkout__cardNumber" class="mp-field"></div>

      <div class="field-row three">
        <div>
          <label>Validade</label>
          <div id="form-checkout__expirationDate" class="mp-field"></div>
        </div>
        <div>
          <label>CVV</label>
          <div id="form-checkout__securityCode" class="mp-field"></div>
        </div>
        <div>
          <label>Parcelas</label>
          <select id="form-checkout__installments"></select>
        </div>
      </div>

      <div class="field-row">
        <div>
          <label>Banco emissor</label>
          <select id="form-checkout__issuer"></select>
        </div>
        <div>
          <label>Tipo de documento</label>
          <select id="form-checkout__identificationType"></select>
        </div>
      </div>

      <div class="field-row">
        <div>
          <label>CPF do titular</label>
          <input id="form-checkout__identificationNumber" placeholder="000.000.000-00" inputmode="numeric" autocomplete="off" />
        </div>
        <div>
          <label>Telefone do cliente</label>
          <input id="payerPhone" placeholder="DDD + número" inputmode="tel" autocomplete="tel" />
        </div>
      </div>

      <button id="form-checkout__submit" type="submit" class="pay cardpay full">Processar cartão</button>
      <progress value="0" class="progress-bar">Carregando...</progress>
    </form>
    <div id="cardResult"></div>
    <button class="ghost-wide" onclick="trocarForma()">Trocar forma de pagamento</button>
  </section>
</main>
<script>
window.MP_PUBLIC_KEY = <?= json_encode(MP_PUBLIC_KEY) ?>;
</script>
<script src="assets/app.js?v=3"></script>
</body>
</html>
