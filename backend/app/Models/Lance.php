<?php
namespace App\Models;

use App\Core\Database;
use RuntimeException;
use Throwable;

final class Lance
{
    // Lances em que a pessoa está na frente, em leilões que ainda não fecharam.
    // (O MAX por leilão define quem está na frente: cada lance supera o anterior.)
    private const NA_FRENTE = "
          FROM Lance la
          JOIN Leilao l ON l.id = la.id_leilao
          JOIN Obra o ON o.id = l.id_obra
         WHERE la.id_usuario = ? AND l.liquidadoEm IS NULL
           AND la.valor = (SELECT MAX(x.valor) FROM Lance x WHERE x.id_leilao = la.id_leilao)";

    public function doLeilao(int $idLeilao): array
    {
        $s = Database::connection()->prepare(
            'SELECT la.*, u.nome FROM Lance la JOIN Usuario u ON u.id = la.id_usuario
              WHERE la.id_leilao = ? ORDER BY la.valor DESC, la.id DESC'
        );
        $s->execute([$idLeilao]);
        return $s->fetchAll();
    }

    // Crédito reservado: o valor dos lances em que a pessoa está na frente. Continua no saldo,
    // mas não pode ir para outro lance. Se alguém cobrir, a reserva some sozinha.
    public static function reservado(int $idUsuario): string
    {
        $s = Database::connection()->prepare('SELECT COALESCE(SUM(la.valor), 0)' . self::NA_FRENTE);
        $s->execute([$idUsuario]);
        return (string) $s->fetchColumn();
    }

    // Os mesmos lances, com a obra, para a página da carteira.
    public function vencendo(int $idUsuario): array
    {
        $s = Database::connection()->prepare(
            'SELECT la.valor, l.id AS id_leilao, l.dataFim, l.id_obra, o.titulo' . self::NA_FRENTE . ' ORDER BY l.dataFim'
        );
        $s->execute([$idUsuario]);
        return $s->fetchAll();
    }

    // RN-05 e RN-08 validados no servidor, dentro de uma transação:
    // o FOR UPDATE trava o leilão para dois lances simultâneos não passarem com o mesmo mínimo.
    public function registrar(int $idLeilao, int $idUsuario, string $valor): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare(
                "SELECT l.valorInicial, l.incremento, o.id_usuario AS idAutor,
                        (l.status = 'APROVADO' AND NOW() BETWEEN l.dataInicio AND l.dataFim) AS aberto
                   FROM Leilao l JOIN Obra o ON o.id = l.id_obra
                  WHERE l.id = ? FOR UPDATE"
            );
            $s->execute([$idLeilao]);
            $leilao = $s->fetch();
            if (!$leilao)
                throw new RuntimeException('Leilão não encontrado.');
            if (!(int) $leilao['aberto'])
                throw new RuntimeException('Este leilão não está aberto para lances.');
            if ((int) $leilao['idAutor'] === $idUsuario)
                throw new RuntimeException('Artistas não dão lances nas próprias obras.');

            // Trava a carteira de quem dá o lance: dois lances dela em leilões diferentes,
            // ao mesmo tempo, não conseguem usar o mesmo crédito.
            $saldo = Movimentacao::travar($pdo, $idUsuario);

            $s = $pdo->prepare('SELECT valor, id_usuario FROM Lance WHERE id_leilao = ? ORDER BY valor DESC, id DESC LIMIT 1');
            $s->execute([$idLeilao]);
            $lider = $s->fetch();
            if ($lider && (int) $lider['id_usuario'] === $idUsuario)
                throw new RuntimeException('Você já está com o maior lance deste leilão.');

            // Compara em centavos (inteiros) para evitar erro de arredondamento de float.
            $minimo = $lider
                ? centavos($lider['valor']) + centavos($leilao['incremento'])
                : centavos($leilao['valorInicial']);
            if (centavos($valor) < $minimo)
                throw new RuntimeException('O lance mínimo agora é ' . brl($minimo / 100) . '.');

            // O lance precisa caber no crédito que sobra depois dos lances em que a pessoa já está na frente.
            $disponivel = centavos($saldo) - centavos(self::reservado($idUsuario));
            if (centavos($valor) > $disponivel)
                throw new RuntimeException('Créditos insuficientes: você tem ' . brl(max(0, $disponivel) / 100)
                    . ' disponíveis. Compre mais na sua carteira.');

            $s = $pdo->prepare('INSERT INTO Lance (valor, dataHora, id_usuario, id_leilao) VALUES (?, NOW(), ?, ?)');
            $s->execute([$valor, $idUsuario, $idLeilao]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
    }
}
