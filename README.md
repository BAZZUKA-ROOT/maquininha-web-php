# 💳 Maquininha Web — Sistema de Pagamento Online

Sistema web PHP para aceitar pagamentos com cartão de crédito/débito e PIX via Mercado Pago — interface de terminal de pagamento no navegador.

![Language](https://img.shields.io/badge/Stack-PHP%20%7C%20MySQL-purple) ![License](https://img.shields.io/badge/Licen%C3%A7a-MIT-blue)

---

## 🎯 O que faz

- Interface de terminal POS no navegador (mobile-first)
- Aceita cartão de crédito, débito e PIX via Mercado Pago
- Processamento de pagamento com status em tempo real
- Painel admin com histórico de transações
- Webhook para confirmação de pagamento
- API REST para integração com outros sistemas

## ⚙️ Instalação

### 1. Banco de dados

```sql
-- Importe o arquivo sql/database.sql no MySQL/MariaDB
mysql -u root -p maquininha_web < sql/database.sql
```

### 2. Configuração

```bash
# Copie o .env.example
cp .env.example .env
# Preencha com suas credenciais
```

Ou edite `config.php` diretamente.

### Obter credenciais Mercado Pago

1. Acesse [developers.mercadopago.com](https://www.mercadopago.com.br/developers/pt/docs)
2. Crie um app em **Suas integrações**
3. Copie o `Access Token` e `Public Key`

## 📂 Estrutura

```
maquininha-web-php/
├── index.php          # Terminal de pagamento
├── admin/             # Painel administrativo
├── api/               # Endpoints REST
├── assets/            # CSS, JS, imagens
├── includes/          # Funções auxiliares
├── sql/               # Schema do banco
├── webhook_mp.php     # Webhook do Mercado Pago
└── config.php         # Configurações
```

## 🔒 Segurança

- Validação de webhook via assinatura HMAC
- Dados de cartão nunca passam pelo servidor (tokenização MP)
- Painel admin protegido por senha

## 📄 Licença

MIT

---
Feito com ☕ no Brasil 🇧🇷
