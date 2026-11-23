-- O arquivo está em UTF-8. Sem esta linha o MySQL lê como latin1 na criação
-- do banco e grava os acentos embaralhados ("ReposiÃ§Ã£o").
SET NAMES utf8mb4;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(100) NOT NULL UNIQUE,
  descricao VARCHAR(255) NOT NULL DEFAULT ''
);

CREATE TABLE enderecos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE,
  tipo ENUM('principal', 'excesso') NOT NULL
);

CREATE TABLE estoque (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT NOT NULL,
  endereco_id INT NOT NULL,
  quantidade INT NOT NULL DEFAULT 0,
  capacidade INT NOT NULL DEFAULT 0,
  UNIQUE KEY (produto_id, endereco_id),
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
  FOREIGN KEY (endereco_id) REFERENCES enderecos(id)
);

CREATE TABLE tipos_movimento (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nome VARCHAR(60) NOT NULL
);

CREATE TABLE importacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  arquivo VARCHAR(255) NOT NULL,
  usuario_id INT NOT NULL,
  linhas INT NOT NULL DEFAULT 0,
  baixas INT NOT NULL DEFAULT 0,
  repetidos INT NOT NULL DEFAULT 0,
  data DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE pedidos_baixados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  importacao_id INT NOT NULL,
  produto_id INT NOT NULL,
  pedido VARCHAR(50) NOT NULL,
  quantidade INT NOT NULL,
  UNIQUE KEY (pedido, produto_id),
  FOREIGN KEY (importacao_id) REFERENCES importacoes(id),
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

CREATE TABLE movimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT NOT NULL,
  endereco_id INT NOT NULL,
  tipo_id INT NOT NULL,
  usuario_id INT NOT NULL,
  quantidade INT NOT NULL,
  data DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
  FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
  FOREIGN KEY (tipo_id) REFERENCES tipos_movimento(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

INSERT INTO usuarios (usuario, senha) VALUES ('admin', '$2y$12$y0HV5ck1QS3d4USL1e7q8.EDqi8VL/VyCf/Z3uOTDw7M6YBWqQV.i');
INSERT INTO enderecos (nome, tipo) VALUES ('Principal', 'principal'), ('Excesso', 'excesso');
INSERT INTO tipos_movimento (codigo, nome) VALUES
  ('venda', 'Baixa de venda'),
  ('reposicao', 'Reposição do excesso'),
  ('entrada_excesso', 'Entrada no excesso'),
  ('acerto', 'Acerto manual');
