<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

// Carteira de créditos fictícios. O saldo fica em Usuario.saldo e cada entrada ou saída
// vira uma linha em Movimentacao (o extrato). Nenhum dinheiro de verdade entra ou sai do site.
final class Movimentacao
{
    // Movimentacao.tipo
    public const BONUS = 'BONUS';         // boas-vindas de toda conta nova
    public const COMPRA = 'COMPRA';       // compra simulada de créditos
    public const ARREMATE = 'ARREMATE';   // saída: quem venceu o leilão paga a obra
    public const VENDA = 'VENDA';         // entrada: o artista recebe pela obra vendida

    public const BONUS_BOAS_VINDAS = '10.00';
    // Pacotes da compra simulada: o servidor só aceita estes valores.
    public const PACOTES = [10, 50, 100, 500, 1000];
    // Teto do saldo comprado, para ninguém estourar a coluna DECIMAL(10,2) clicando sem parar.
    public const SALDO_MAXIMO = 100000;

    // Trava a conta até o fim da transação e devolve o saldo. Assim duas operações da mesma
    // pessoa (dois lances ao mesmo tempo, por exemplo) não usam o mesmo crédito.
    public static function travar(PDO $pdo, int $idUsuario): string
    {
        $s = $pdo->prepare('SELECT saldo FROM Usuario WHERE id = ? FOR UPDATE');
        $s->execute([$idUsuario]);
        $saldo = $s->fetchColumn();
        if ($saldo === false)
            throw new RuntimeException('Conta não encontrada.');
        return (string) $saldo;
    }

    // Grava uma entrada (valor positivo) ou saída (negativo) e atualiza o saldo junto.
    // Chame dentro de uma transação. O saldo nunca fica negativo: a saída é recusada.
    public static function lancar(PDO $pdo, int $idUsuario, string $tipo, string $valor, string $descricao,
                                  ?int $idLeilao = null, ?string $quando = null): void
    {
        $s = $pdo->prepare('UPDATE Usuario SET saldo = saldo + ? WHERE id = ? AND saldo + ? >= 0');
        $s->execute([$valor, $idUsuario, $valor]);
        if ($s->rowCount() !== 1)
            throw new RuntimeException('Saldo insuficiente para esta operação.');
        $s = $pdo->prepare(
            'INSERT INTO Movimentacao (tipo, valor, descricao, dataHora, id_usuario, id_leilao)
             VALUES (?, ?, ?, COALESCE(?, NOW()), ?, ?)'
        );
        $s->execute([$tipo, $valor, mb_substr($descricao, 0, 255), $quando, $idUsuario, $idLeilao]);
    }

    // Saldo, quanto está reservado em lances e quanto sobra para usar.
    public function resumo(int $idUsuario): array
    {
        $s = Database::connection()->prepare('SELECT saldo FROM Usuario WHERE id = ?');
        $s->execute([$idUsuario]);
        $saldo = (string) ($s->fetchColumn() ?: '0');
        $reservado = Lance::reservado($idUsuario);
        return [
            'saldo' => $saldo,
            'reservado' => $reservado,
            'disponivel' => (centavos($saldo) - centavos($reservado)) / 100,
        ];
    }

    public function extrato(int $idUsuario): array
    {
        $s = Database::connection()->prepare(
            'SELECT * FROM Movimentacao WHERE id_usuario = ? ORDER BY dataHora DESC, id DESC LIMIT 100'
        );
        $s->execute([$idUsuario]);
        return $s->fetchAll();
    }

    // Compra simulada: não há pagamento, o valor do pacote só entra no saldo.
    public function comprar(int $idUsuario, int $valor): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $saldo = self::travar($pdo, $idUsuario);
            if (centavos($saldo) + $valor * 100 > self::SALDO_MAXIMO * 100)
                throw new RuntimeException('A carteira aceita no máximo ' . brl(self::SALDO_MAXIMO) . ' em créditos.');
            self::lancar($pdo, $idUsuario, self::COMPRA, $valor . '.00', 'Compra de créditos (simulada)');
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
    }
}
