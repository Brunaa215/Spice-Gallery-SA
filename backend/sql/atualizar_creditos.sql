-- Carteira de créditos: atualiza um banco criado ANTES dela. Rode UMA vez:
--   mysql -u root -p < sql/atualizar_creditos.sql
-- Banco novo não precisa disto: o schema.sql já cria tudo.
-- Se rodar de novo, o primeiro ALTER falha ("Duplicate column") e nada mais é executado.
SET NAMES utf8mb4;
USE spice_galery;

ALTER TABLE Usuario
  ADD COLUMN saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER ativo;

ALTER TABLE Leilao
  ADD COLUMN id_vencedor BIGINT NULL,
  ADD COLUMN liquidadoEm DATETIME NULL,
  ADD CONSTRAINT fk_leilao_vencedor FOREIGN KEY (id_vencedor) REFERENCES Usuario(id) ON DELETE SET NULL;

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

-- Contas de artista que já existiam ganham os mesmos R$ 10 de boas-vindas das contas novas.
INSERT INTO Movimentacao (tipo, valor, descricao, id_usuario)
SELECT 'BONUS', 10.00, 'Créditos de boas-vindas', id FROM Usuario WHERE perfil = 'ARTISTA';

-- Lances que já estavam na frente antes da carteira existir recebem o mesmo valor em créditos.
-- Sem isso o vencedor não teria saldo e o leilão não conseguiria fechar.
INSERT INTO Movimentacao (tipo, valor, descricao, id_usuario, id_leilao)
SELECT 'AJUSTE', la.valor, LEFT(CONCAT('Créditos para cobrir lance anterior à carteira: ', o.titulo), 255),
       la.id_usuario, la.id_leilao
  FROM Lance la
  JOIN Leilao l ON l.id = la.id_leilao
  JOIN Obra o ON o.id = l.id_obra
 WHERE la.valor = (SELECT MAX(x.valor) FROM Lance x WHERE x.id_leilao = la.id_leilao);

UPDATE Usuario u
   SET saldo = (SELECT COALESCE(SUM(m.valor), 0) FROM Movimentacao m WHERE m.id_usuario = u.id);
