-- Notificações de fim de leilão (com o contato entre comprador e artista): atualiza um banco criado
-- antes disso. Rode UMA vez, depois do atualizar_perfil.sql:
--   mysql -u root -p < sql/atualizar_notificacoes.sql
-- Banco novo não precisa disto: o schema.sql já cria tudo.
-- Se rodar de novo, o ALTER falha ("Duplicate column") e nada mais é executado.
SET NAMES utf8mb4;
USE spice_galery;

ALTER TABLE Usuario
  ADD COLUMN contatoVenda VARCHAR(40) NULL AFTER wallpaper;

CREATE TABLE IF NOT EXISTS Notificacao (
    id         BIGINT PRIMARY KEY AUTO_INCREMENT,
    id_usuario BIGINT        NOT NULL,
    tipo       VARCHAR(20)   NOT NULL,
    id_leilao  BIGINT        NULL,
    obra       VARCHAR(255)  NOT NULL,
    valor      DECIMAL(10,2) NULL,
    seuLance   DECIMAL(10,2) NULL,
    id_contato BIGINT        NULL,
    criadaEm   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lidaEm     DATETIME      NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (id_leilao)  REFERENCES Leilao(id)  ON DELETE SET NULL,
    FOREIGN KEY (id_contato) REFERENCES Usuario(id) ON DELETE SET NULL,
    KEY idx_notificacao_usuario (id_usuario, lidaEm)
) ENGINE=InnoDB;

-- Leilões que já tinham fechado antes das notificações existirem recebem os mesmos avisos
-- (com a data do martelo), para quem venceu ou vendeu também ver o contato da outra parte.
CREATE TEMPORARY TABLE maior_lance AS
SELECT id_leilao, MAX(valor) AS maior FROM Lance GROUP BY id_leilao;

-- quem venceu: o contato é o do artista
INSERT INTO Notificacao (id_usuario, tipo, id_leilao, obra, valor, id_contato, criadaEm)
SELECT l.id_vencedor, 'ARREMATE', l.id, o.titulo, m.maior, o.id_usuario, l.dataFim
  FROM Leilao l JOIN Obra o ON o.id = l.id_obra JOIN maior_lance m ON m.id_leilao = l.id
 WHERE l.liquidadoEm IS NOT NULL AND l.id_vencedor IS NOT NULL;

-- o artista que vendeu: o contato é o de quem arrematou
INSERT INTO Notificacao (id_usuario, tipo, id_leilao, obra, valor, id_contato, criadaEm)
SELECT o.id_usuario, 'VENDA', l.id, o.titulo, m.maior, l.id_vencedor, l.dataFim
  FROM Leilao l JOIN Obra o ON o.id = l.id_obra JOIN maior_lance m ON m.id_leilao = l.id
 WHERE l.liquidadoEm IS NOT NULL AND l.id_vencedor IS NOT NULL;

-- quem deu lance e não venceu
INSERT INTO Notificacao (id_usuario, tipo, id_leilao, obra, valor, seuLance, criadaEm)
SELECT p.id_usuario, 'NAO_VENCEU', l.id, o.titulo, m.maior, p.seu, l.dataFim
  FROM Leilao l JOIN Obra o ON o.id = l.id_obra JOIN maior_lance m ON m.id_leilao = l.id
  JOIN (SELECT id_leilao, id_usuario, MAX(valor) AS seu FROM Lance GROUP BY id_leilao, id_usuario) p
    ON p.id_leilao = l.id AND p.id_usuario <> l.id_vencedor
 WHERE l.liquidadoEm IS NOT NULL AND l.id_vencedor IS NOT NULL;

-- o artista cujo leilão fechou sem lances
INSERT INTO Notificacao (id_usuario, tipo, id_leilao, obra, criadaEm)
SELECT o.id_usuario, 'SEM_LANCES', l.id, o.titulo, l.dataFim
  FROM Leilao l JOIN Obra o ON o.id = l.id_obra
 WHERE l.liquidadoEm IS NOT NULL AND l.id_vencedor IS NULL;

DROP TEMPORARY TABLE maior_lance;
