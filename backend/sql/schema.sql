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
    apelido        VARCHAR(60)  NULL,                                -- [NOVO] perfil: apelido
    especialidade  VARCHAR(80)  NULL,                                -- [NOVO] perfil: ex. "Pintura digital"
    estilos        VARCHAR(255) NULL,                                -- [NOVO] perfil: estilos separados por vírgula
    fotoPerfil     VARCHAR(255) NULL,                                -- [NOVO] perfil: caminho da foto (uploads/perfis)
    molduraFoto    VARCHAR(20)  NOT NULL DEFAULT 'camafeu',          -- [NOVO] perfil: camafeu | ouro | rosa | verde | nenhuma
    wallpaper      VARCHAR(255) NULL,                                -- [NOVO] perfil: fundo do cabeçalho da sala
    contatoVenda   VARCHAR(40)  NULL,                                -- [NOVO] WhatsApp/telefone: só aparece nas notificações de venda
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
-- status (EnumLeilao): 'APROVADO' (todo leilão novo: não há mais aprovação da curadoria, o grupo mudou a RN-07)
--   'AGUARDANDO_CURADORIA' e 'REPROVADO' só existem em dados antigos.
-- Um leilão APROVADO é "em breve", "ao vivo" ou "encerrado" conforme dataInicio/dataFim.
-- Encerrado, ele é fechado uma vez só: o vencedor paga com créditos e o artista recebe.
-- A duração (dataFim - dataInicio) pode ser de minutos a dias. abreNaAprovacao (dados antigos): as datas
-- são provisórias: quando a curadoria aprova, o leilão começa na hora, com a mesma duração.
CREATE TABLE IF NOT EXISTS Leilao (
    id           BIGINT PRIMARY KEY AUTO_INCREMENT,
    dataInicio   DATETIME NOT NULL,
    dataFim      DATETIME NOT NULL,
    abreNaAprovacao TINYINT(1) NOT NULL DEFAULT 0,                   -- [NOVO] começa quando a curadoria aprovar
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

-- 7. REDESOCIAL [NOVO] links do perfil do artista (uma linha por rede)
-- rede: 'instagram' | 'tiktok' | 'x' | 'youtube' | 'behance' | 'artstation' | 'pinterest' | 'site'
CREATE TABLE IF NOT EXISTS RedeSocial (
    id_usuario BIGINT       NOT NULL,
    rede       VARCHAR(20)  NOT NULL,
    url        VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_usuario, rede),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. NOTIFICACAO [NOVO] avisos que chegam quando um leilão termina
-- tipo: 'ARREMATE' (você venceu) | 'VENDA' (sua obra foi arrematada) | 'NAO_VENCEU' (outro lance venceu)
--       'SEM_LANCES' (seu leilão terminou sem lances)
-- O texto é montado pelo PHP a partir do tipo e dos valores; o contato vem de id_contato (a outra parte
-- da venda) na hora de mostrar, então aparece sempre o contato atual.
CREATE TABLE IF NOT EXISTS Notificacao (
    id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    id_usuario BIGINT        NOT NULL,                -- quem recebe
    tipo       VARCHAR(20)   NOT NULL,
    id_leilao  BIGINT        NULL,
    obra       VARCHAR(255)  NOT NULL,                -- título da obra quando o martelo bateu
    valor      DECIMAL(10,2) NULL,                    -- lance vencedor
    seuLance   DECIMAL(10,2) NULL,                    -- NAO_VENCEU: o maior lance de quem recebe
    id_contato BIGINT        NULL,                    -- ARREMATE: o artista · VENDA: quem arrematou
    criadaEm   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lidaEm     DATETIME      NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (id_leilao)  REFERENCES Leilao(id)  ON DELETE SET NULL,
    FOREIGN KEY (id_contato) REFERENCES Usuario(id) ON DELETE SET NULL,
    KEY idx_notificacao_usuario (id_usuario, lidaEm)
) ENGINE=InnoDB;

-- Curadoria inicial: admin@spice.com / Admin@12345!
-- TROQUE a senha no primeiro acesso (Perfil → Editar perfil → Trocar minha senha).
INSERT INTO Usuario (nome, email, senhaHash, perfil, aceiteTermosEm)
VALUES ('Curadoria Spice', 'admin@spice.com', '$2y$10$sPZuRpNwm/YnuSH1l85ZnuNOfYO7poKiw.7bCCSvStY3Fw6suo7Ye', 'ADMIN', NOW())
ON DUPLICATE KEY UPDATE id = id;
