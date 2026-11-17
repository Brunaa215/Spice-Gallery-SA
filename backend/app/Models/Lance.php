<?php
namespace App\Models;

use App\Core\Database;
use RuntimeException;
use Throwable;

final class Lance
{
    public function doLeilao(int $idLeilao): array
    {
        $s = Database::connection()->prepare(
            'SELECT la.*, u.nome FROM Lance la JOIN Usuario u ON u.id = la.id_usuario
              WHERE la.id_leilao = ? ORDER BY la.valor DESC, la.id DESC'
        );
        $s->execute([$idLeilao]);
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

            $s = $pdo->prepare('SELECT MAX(valor) FROM Lance WHERE id_leilao = ?');
            $s->execute([$idLeilao]);
            $maior = $s->fetchColumn();
            // Compara em centavos (inteiros) para evitar erro de arredondamento de float.
            $minimo = $maior === null
                ? (int) round((float) $leilao['valorInicial'] * 100)
                : (int) round(((float) $maior + (float) $leilao['incremento']) * 100);
            if ((int) round((float) $valor * 100) < $minimo)
                throw new RuntimeException('O lance mínimo agora é ' . brl($minimo / 100) . '.');

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
