-- Leilão por minutos e "começa quando a curadoria aprovar": atualiza um banco criado antes disso.
-- Rode UMA vez, depois do atualizar_creditos.sql:
--   mysql -u root -p < sql/atualizar_leilao_minutos.sql
-- Banco novo não precisa disto: o schema.sql já cria tudo.
-- Se rodar de novo, o ALTER falha ("Duplicate column") e nada mais é executado.
SET NAMES utf8mb4;
USE spice_galery;

ALTER TABLE Leilao
  ADD COLUMN abreNaAprovacao TINYINT(1) NOT NULL DEFAULT 0 AFTER dataFim;

-- Leilões que ainda esperam a curadoria e já perderam a data marcada (antes só podiam ser
-- reprovados) passam a começar quando forem aprovados, com a mesma duração.
UPDATE Leilao
   SET abreNaAprovacao = 1
 WHERE status = 'AGUARDANDO_CURADORIA' AND dataFim <= NOW();
