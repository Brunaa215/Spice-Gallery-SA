-- Leilões sem aprovação da curadoria (o grupo mudou a RN-07): leilões que ainda esperavam a curadoria
-- num banco antigo passam a valer. Rode depois do atualizar_notificacoes.sql:
--   mysql -u root -p < sql/atualizar_sem_curadoria.sql
-- Rodar de novo não faz mal: só mexe em leilões que ainda estão AGUARDANDO_CURADORIA.
SET NAMES utf8mb4;
USE spice_galery;

-- "Começa quando aprovar": começa agora, com a duração escolhida.
-- (dataFim vem antes de dataInicio no SET para ainda usar o início antigo no cálculo da duração)
UPDATE Leilao
   SET dataFim = NOW() + INTERVAL TIMESTAMPDIFF(SECOND, dataInicio, dataFim) SECOND,
       dataInicio = NOW(),
       abreNaAprovacao = 0,
       status = 'APROVADO'
 WHERE status = 'AGUARDANDO_CURADORIA' AND abreNaAprovacao = 1;

-- Data marcada que ainda não terminou: vale a data marcada (se o início já passou, já abre).
UPDATE Leilao
   SET status = 'APROVADO'
 WHERE status = 'AGUARDANDO_CURADORIA' AND abreNaAprovacao = 0 AND dataFim > NOW();

-- Data marcada que passou sem o leilão nunca abrir: sai, e a obra fica livre para um leilão novo.
DELETE FROM Leilao
 WHERE status = 'AGUARDANDO_CURADORIA' AND abreNaAprovacao = 0 AND dataFim <= NOW()
   AND NOT EXISTS (SELECT 1 FROM Lance la WHERE la.id_leilao = Leilao.id);
