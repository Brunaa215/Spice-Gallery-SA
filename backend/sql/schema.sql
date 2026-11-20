-- Schema do Spice Gallery (MySQL 5.7+ / MariaDB 10.3+)
-- Uso: mysql -u root -p < sql/schema.sql
--
-- Baseado em "banco de dados SA.sql". As tabelas e colunas originais foram mantidas;
-- as colunas marcadas com [NOVO] foram acrescentadas porque as regras de negócio pedem
-- (veja frontend/README.md, seção "Formulários e banco").

SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS spice_galery
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spice_galery;

-- 1. USUARIO
-- perfil (EnumPerfil): 'ARTISTA' | 'ADMIN' (curadoria)
CREATE TABLE IF NOT EXISTS Usuario (
    id             BIGINT PRIMARY KEY AUTO_INCREMENT,
    nome           VARCHAR(255) NOT NULL,
    email          VARCHAR(255) NOT NULL UNIQUE,
    senhaHash      VARCHAR(255) NOT NULL,
    perfil         VARCHAR(50)  NOT NULL DEFAULT 'ARTISTA',
    ativo          TINYINT(1)   NOT NULL DEFAULT 1,                  -- [NOVO] curadoria pode desativar contas
    saldo          DECIMAL(10,2) NOT NULL DEFAULT 0.00,              -- [NOVO] créditos fictícios da carteira
    aceiteTermosEm DATETIME     NULL,                                -- [NOVO] RN-02: quando aceitou os termos
    criadoEm       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP   -- [NOVO] "membro desde"
) ENGINE=InnoDB;

-- 2. OBRA
-- status (EnumStatus): 'PUBLICADA' | 'REMOVIDA'
-- categoria: 'pintura' | 'digital' | 'aquarela' | 'gravura' | 'fotografia' | 'escultura'
CREATE TABLE IF NOT EXISTS Obra (
    id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    titulo     VARCHAR(255) NOT NULL,
    descricao  TEXT,
    urlImagem  VARCHAR(255),
    status     VARCHAR(50)  NOT NULL,
    categoria  VARCHAR(50)  NOT NULL DEFAULT 'pintura',              -- [NOVO] RN-03
    criadoEm   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,      -- [NOVO] "publicada em"
    id_usuario BIGINT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    KEY idx_obra_categoria (categoria)
) ENGINE=InnoDB;

-- 3. LEILAO
-- status (EnumLeilao): 'AGUARDANDO_CURADORIA' | 'APROVADO' | 'REPROVADO'
-- Um leilão APROVADO é "em breve", "ao vivo" ou "encerrado" conforme dataInicio/dataFim.
-- Encerrado, ele é fechado uma vez só: o vencedor paga com créditos e o artista recebe.
CREATE TABLE IF NOT EXISTS Leilao (
    id           BIGINT PRIMARY KEY AUTO_INCREMENT,
    dataInicio   DATETIME NOT NULL,
    dataFim      DATETIME NOT NULL,
    valorInicial DOUBLE   NOT NULL,
    incremento   DECIMAL(10,2) NOT NULL DEFAULT 1.00,                -- [NOVO] RN-08: incremento mínimo
    status       VARCHAR(50) NOT NULL,
    id_obra      BIGINT NOT NULL,
    id_vencedor  BIGINT NULL,                                        -- [NOVO] quem arrematou (NULL = sem lances)
    liquidadoEm  DATETIME NULL,                                      -- [NOVO] quando o pagamento foi feito
    FOREIGN KEY (id_obra) REFERENCES Obra(id) ON DELETE CASCADE,
    CONSTRAINT fk_leilao_vencedor FOREIGN KEY (id_vencedor) REFERENCES Usuario(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. AVALIACAO
CREATE TABLE IF NOT EXISTS Avaliacao (
    id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    nota       INT NOT NULL CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    dataHora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario BIGINT NOT NULL,
    id_obra    BIGINT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (id_obra)    REFERENCES Obra(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. LANCE
CREATE TABLE IF NOT EXISTS Lance (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    valor      DECIMAL(10,2) NOT NULL,
    dataHora   DATETIME NOT NULL,
    id_usuario BIGINT NOT NULL,
    id_leilao  BIGINT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (id_leilao)  REFERENCES Leilao(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. MOVIMENTACAO [NOVO] extrato da carteira de créditos fictícios
-- Cada entrada (+) ou saída (-) do Usuario.saldo vira uma linha aqui.
-- tipo: 'BONUS' (R$ 10 de boas-vindas) | 'COMPRA' (compra simulada de créditos)
--       'ARREMATE' (o vencedor paga a obra) | 'VENDA' (o artista recebe) | 'AJUSTE' (migração)
CREATE TABLE IF NOT EXISTS Movimentacao (
    id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    tipo       VARCHAR(30)   NOT NULL,
    valor      DECIMAL(10,2) NOT NULL,
    descricao  VARCHAR(255)  NOT NULL,
    dataHora   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario BIGINT NOT NULL,
    id_leilao  BIGINT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (id_leilao)  REFERENCES Leilao(id)  ON DELETE SET NULL,
    KEY idx_movimentacao_usuario (id_usuario, dataHora)
) ENGINE=InnoDB;

-- Curadoria inicial: admin@spice.com / Admin@12345!
-- TROQUE a senha no primeiro acesso (menu "Minha senha").
INSERT INTO Usuario (nome, email, senhaHash, perfil, aceiteTermosEm)
VALUES ('Curadoria Spice', 'admin@spice.com', '$2y$10$sPZuRpNwm/YnuSH1l85ZnuNOfYO7poKiw.7bCCSvStY3Fw6suo7Ye', 'ADMIN', NOW())
ON DUPLICATE KEY UPDATE id = id;
