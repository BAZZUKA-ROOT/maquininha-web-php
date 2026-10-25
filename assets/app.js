let venda = { valor: 0, valorTexto: '0,00', descricao: '', metodo: null };
let cardMounted = false;
let cardFormInstance = null;
let botaoCartaoTravado = false;

function el(id){ return document.getElementById(id); }
function show(id) { el(id).classList.remove('hidden'); }
function hide(id) { el(id).classList.add('hidden'); }
function onlyDigits(v){ return String(v || '').replace(/\D/g, ''); }
function parseValor() {
  let v = (el('valor').value || '').replace(/R\$|\s/g, '').replace(/\./g, '').replace(',', '.');
  return Number(v || 0);
}
function moeda(n){ return Number(n || 0).toLocaleString('pt-BR', {style:'currency', currency:'BRL'}); }
function setStep(n){
  document.querySelectorAll('.step').forEach(s => s.classList.toggle('active', s.dataset.step == n));
}
function limparAlertas(){ ['valorErro','cardResult'].forEach(id => { if(el(id)) el(id).innerHTML = ''; }); }
function resetPagamentos(){
  hide('resultadoPix');
  hide('cartaoBox');
  if (el('pixArea')) el('pixArea').innerHTML = '';
  if (el('cardResult')) el('cardResult').innerHTML = '';
}
function validarEmail(email){ return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(email || '').trim()); }
function validarCPF(cpf){
  cpf = onlyDigits(cpf);
  if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
  let soma = 0;
  for (let i=0;i<9;i++) soma += Number(cpf[i]) * (10-i);
  let d1 = 11 - (soma % 11); d1 = d1 > 9 ? 0 : d1;
  if (d1 !== Number(cpf[9])) return false;
  soma = 0;
  for (let i=0;i<10;i++) soma += Number(cpf[i]) * (11-i);
  let d2 = 11 - (soma % 11); d2 = d2 > 9 ? 0 : d2;
  return d2 === Number(cpf[10]);
}
function msg(tipo, texto){ return `<p class="${tipo}">${texto}</p>`; }

function irParaForma(){
  limparAlertas();
  const valor = parseValor();
  if (!valor || valor <= 0) { el('valorErro').innerHTML = msg('erro','Informe um valor válido.'); return; }
  venda.valor = Number(valor.toFixed(2));
  venda.valorTexto = el('valor').value || '0,00';
  venda.descricao = el('descricao').value.trim() || 'Venda maquininha';
  el('valorEscolhido').textContent = moeda(venda.valor);
  resetPagamentos();
  hide('stepValor');
  show('stepForma');
  setStep(2);
}
function voltarValor(){
  resetPagamentos();
  hide('stepForma');
  show('stepValor');
  setStep(1);
}
function trocarForma(){
  resetPagamentos();
  show('stepForma');
  hide('stepValor');
  setStep(2);
}
function selecionarPagamento(metodo){
  venda.metodo = metodo;
  hide('stepForma');
  resetPagamentos();
  setStep(3);
  if (metodo === 'pix') gerarPix();
  if (metodo === 'cartao') abrirCartao();
}

function setBadge(status){
  const badge = el('pixStatusBadge');
  if (!badge) return;
  const s = String(status || 'pendente').toLowerCase();
  badge.textContent = s;
  badge.className = 'badge ' + (s.includes('apro') ? 'approved' : (s.includes('recus') || s.includes('cancel') || s.includes('erro') ? 'refused' : 'pending'));
}
async function gerarPix() {
  const area = el('pixArea');
  show('resultadoPix');
  setBadge('pendente');
  area.innerHTML = msg('info','Gerando QR Code Pix...');
  try {
    const r = await fetch('api/criar_pix.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({valor: venda.valorTexto, descricao: venda.descricao})});
    const j = await r.json();
    if (!j.ok) throw new Error(j.message || 'Erro ao gerar Pix');
    setBadge(j.status || 'pendente');
    area.innerHTML = `
      <div class="qrwrap">
        <div class="amount-chip">${moeda(venda.valor)}</div>
        ${j.qr_code_base64 ? `<div class="qr-card"><img class="qr" src="data:image/png;base64,${j.qr_code_base64}" alt="QR Code Pix"></div>` : ''}
        <div class="status-line"><strong>Status:</strong> <span id="pixStatus">${j.status || 'pendente'}</span></div>
        <textarea readonly id="copiaCola" class="pix-code">${j.qr_code || ''}</textarea>
        <button class="copy" onclick="copiarPix(this)">Copiar código Pix</button>
        ${j.ticket_url ? `<a class="link fullcenter" target="_blank" href="${j.ticket_url}">Abrir página do Pix</a>` : ''}
      </div>`;
    if (j.payment_id) acompanharPix(j.payment_id);
  } catch(e) { area.innerHTML = msg('erro', e.message); }
}
function copiarPix(btn){
  const t=el('copiaCola');
  t.select(); t.setSelectionRange(0, 99999); document.execCommand('copy');
  if (btn) { const old = btn.textContent; btn.textContent = 'Código Pix copiado'; setTimeout(()=>btn.textContent=old,1800); }
}
async function acompanharPix(paymentId) {
  const statusEl = el('pixStatus');
  let count = 0;
  const timer = setInterval(async()=>{
    count++;
    try {
      const r = await fetch('api/status.php?payment_id='+encodeURIComponent(paymentId));
      const j = await r.json();
      if (j.ok && statusEl) { statusEl.textContent = j.status; setBadge(j.status); }
      if (['aprovado','cancelado','recusado','erro'].includes(j.status) || count > 120) clearInterval(timer);
    } catch(e) {}
  }, 5000);
}

function abrirCartao(){
  show('cartaoBox');
  if (!window.MP_PUBLIC_KEY || window.MP_PUBLIC_KEY === 'COLE_SUA_PUBLIC_KEY_AQUI') {
    el('cardResult').innerHTML = msg('erro','Configure a MP_PUBLIC_KEY no config.php.');
    return;
  }
  if (cardMounted) return;
  const mp = new MercadoPago(window.MP_PUBLIC_KEY, { locale: 'pt-BR' });
  cardFormInstance = mp.cardForm({
    amount: String(venda.valor.toFixed(2)),
    iframe: true,
    form: {
      id: 'form-checkout',
      cardNumber: { id: 'form-checkout__cardNumber', placeholder: 'Número do cartão' },
      expirationDate: { id: 'form-checkout__expirationDate', placeholder: 'MM/AA' },
      securityCode: { id: 'form-checkout__securityCode', placeholder: 'CVV' },
      cardholderName: { id: 'form-checkout__cardholderName', placeholder: 'Titular' },
      issuer: { id: 'form-checkout__issuer', placeholder: 'Banco emissor' },
      installments: { id: 'form-checkout__installments', placeholder: 'Parcelas' },
      identificationType: { id: 'form-checkout__identificationType', placeholder: 'Tipo doc.' },
      identificationNumber: { id: 'form-checkout__identificationNumber', placeholder: 'CPF' },
      cardholderEmail: { id: 'form-checkout__cardholderEmail', placeholder: 'E-mail' },
    },
    callbacks: {
      onFormMounted: error => { if (error) el('cardResult').innerHTML = msg('erro','Erro ao carregar formulário do cartão. Confira a Public Key.'); },
      onSubmit: event => {
        event.preventDefault();
        if (botaoCartaoTravado) return;
        const data = cardFormInstance.getCardFormData();
        const validacao = validarDadosCartao(data);
        if (!validacao.ok) { el('cardResult').innerHTML = msg('erro', validacao.message); return; }
        const payload = {
          token: data.token,
          issuer_id: data.issuerId,
          payment_method_id: data.paymentMethodId,
          transaction_amount: venda.valor,
          installments: Number(data.installments || 1),
          description: venda.descricao,
          cardholderName: data.cardholderName,
          phone: onlyDigits(el('payerPhone').value),
          payer: { email: data.cardholderEmail.trim(), identification: { type: data.identificationType || 'CPF', number: onlyDigits(data.identificationNumber) } }
        };
        pagarCartao(payload);
      },
      onFetching: () => {
        document.querySelector('.progress-bar').removeAttribute('value');
        return () => document.querySelector('.progress-bar').setAttribute('value', '0');
      }
    },
  });
  cardMounted = true;
}
function validarDadosCartao(data){
  const nome = String(data.cardholderName || '').trim();
  const email = String(data.cardholderEmail || '').trim();
  const cpf = onlyDigits(data.identificationNumber);
  if (venda.valor <= 0) return {ok:false, message:'Valor inválido. Volte e informe o valor novamente.'};
  if (!nome || nome.split(/\s+/).length < 2) return {ok:false, message:'Informe o nome completo igual está no cartão.'};
  if (!validarEmail(email)) return {ok:false, message:'Informe um e-mail válido do cliente.'};
  if (!data.paymentMethodId) return {ok:false, message:'Número do cartão incompleto ou bandeira não identificada.'};
  if (!data.token) return {ok:false, message:'Confira número, validade e CVV do cartão.'};
  if (!data.installments || Number(data.installments) < 1) return {ok:false, message:'Selecione a quantidade de parcelas.'};
  if ((data.identificationType || 'CPF') === 'CPF' && !validarCPF(cpf)) return {ok:false, message:'CPF do titular inválido.'};
  return {ok:true};
}
async function pagarCartao(payload){
  const box = el('cardResult');
  const submit = el('form-checkout__submit');
  botaoCartaoTravado = true;
  submit.disabled = true;
  submit.textContent = 'Processando...';
  box.innerHTML = msg('info','Processando no Mercado Pago. Não feche a tela.');
  try {
    const r = await fetch('api/pagar_cartao.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
    const j = await r.json();
    if (!j.ok) throw new Error(j.message || 'Erro no pagamento');
    const aprovado = String(j.status || '').includes('apro');
    box.innerHTML = aprovado
      ? msg('ok', `Pagamento aprovado. ID: ${j.payment_id || '-'}`)
      : msg('info', `Status: ${j.status}. Detalhe: ${j.status_detail || '-'}`);
  } catch(e) {
    box.innerHTML = msg('erro', e.message);
  } finally {
    botaoCartaoTravado = false;
    submit.disabled = false;
    submit.textContent = 'Processar cartão';
  }
}

const valorInput = el('valor');
if (valorInput) {
  valorInput.addEventListener('input', e => {
    let s = onlyDigits(e.target.value);
    if (!s) { e.target.value=''; el('valorPreview').textContent = 'R$ 0,00'; return; }
    e.target.value = (Number(s)/100).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
    el('valorPreview').textContent = 'R$ ' + e.target.value;
  });
}
const cpfInput = el('form-checkout__identificationNumber');
if (cpfInput) {
  cpfInput.addEventListener('input', e => {
    let v = onlyDigits(e.target.value).slice(0,11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = v;
  });
}
