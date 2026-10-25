CREATE DATABASE IF NOT EXISTS maquininha_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE maquininha_web;

CREATE TABLE IF NOT EXISTS vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  valor DECIMAL(10,2) NOT NULL,
  metodo ENUM('pix','cartao') NOT NULL,
  descricao VARCHAR(255) NULL,
  cliente_nome VARCHAR(160) NULL,
  cliente_email VARCHAR(160) NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'iniciado',
  mp_payment_id VARCHAR(80) NULL,
  qr_code_base64 LONGTEXT NULL,
  qr_code_text LONGTEXT NULL,
  recibo_url TEXT NULL,
  erro TEXT NULL,
  criado_em DATETIME NOT NULL,
  atualizado_em DATETIME NULL,
  INDEX idx_status (status),
  INDEX idx_mp_payment_id (mp_payment_id),
  INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
