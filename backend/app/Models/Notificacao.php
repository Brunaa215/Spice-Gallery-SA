<?php
namespace App\Models;

use App\Core\Database;
use PDO;

// Avisos que chegam quando um leilão termina. Ficam guardados só os fatos (tipo, obra, valores, quem é
// a outra parte); o texto é montado em texto() e o contato da outra parte vem do perfil dela na hora
// de mostrar, então aparece sempre o e-mail/WhatsApp atual.
final class Notificacao
{
    public const ARREMATE = 'ARREMATE';       // você venceu: contato do artista
    public const VENDA = 'VENDA';             // sua obra foi arrematada: contato de quem arrematou
    public const NAO_VENCEU = 'NAO_VENCEU';   // você deu lance, outro venceu
    public const SEM_LANCES = 'SEM_LANCES';   // seu leilão terminou sem lances

    // Chamado por Leilao::liquidar(), dentro da mesma transação do pagamento: ou o leilão fecha com
    // os avisos, ou nada acontece. $vencedor = ['id_usuario' => .., 'valor' => ..] ou null.
    public static function avisarFechamento(PDO $pdo, int $idLeilao, string $obra, int $idArtista, ?array $vencedor, string $quando): void
    {
        $s = $pdo->prepare(
            'INSERT INTO Notificacao (id_usuario, tipo, id_leilao, obra, valor, seuLance, id_contato, criadaEm)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $obra = mb_substr($obra, 0, 255);
        if (!$vencedor) {
            $s->execute([$idArtista, self::SEM_LANCES, $idLeilao, $obra, null, null, null, $quando]);
            return;
        }
        $idVencedor = (int) $vencedor['id_usuario'];
        $valor = (string) $vencedor['valor'];
        $s->execute([$idVencedor, self::ARREMATE, $idLeilao, $obra, $valor, null, $idArtista, $quando]);
        $s->execute([$idArtista, self::VENDA, $idLeilao, $obra, $valor, null, $idVencedor, $quando]);

        // Todo mundo que deu lance e não venceu fica sabendo (com o próprio maior lance).
        $p = $pdo->prepare('SELECT id_usuario, MAX(valor) AS seu FROM Lance WHERE id_leilao = ? AND id_usuario <> ? GROUP BY id_usuario');
        $p->execute([$idLeilao, $idVencedor]);
        foreach ($p->fetchAll() as $perdeu)
            $s->execute([(int) $perdeu['id_usuario'], self::NAO_VENCEU, $idLeilao, $obra, $valor, $perdeu['seu'], null, $quando]);
    }

    // [título, mensagem] de um aviso (sem HTML: a view escapa).
    public static function texto(array $n): array
    {
        $obra = '“' . $n['obra'] . '”';
        $outro = $n['contatoNome'] ?? null;
        return match ($n['tipo']) {
            self::ARREMATE => [
                "Você venceu o leilão de {$obra}!",
                'Seu lance de ' . brl($n['valor']) . ' foi o maior quando o martelo bateu. O valor saiu da sua carteira e a obra '
                . 'já está na sua coleção. Combine a entrega com ' . ($outro ?? 'o artista') . ' pelos contatos abaixo.',
            ],
            self::VENDA => [
                "{$obra} foi arrematada por " . brl($n['valor']) . '!',
                ($outro ?? 'Quem arrematou') . ' venceu o leilão e o valor já entrou na sua carteira. '
                . 'Combine a entrega pelos contatos abaixo.',
            ],
            self::NAO_VENCEU => [
                "O leilão de {$obra} terminou",
                'Seu maior lance foi ' . brl($n['seuLance']) . ', mas o lance vencedor foi de ' . brl($n['valor'])
                . '. O crédito que estava reservado voltou a ficar disponível na sua carteira.',
            ],
            self::SEM_LANCES => [
                "O leilão de {$obra} terminou sem lances",
                'Ninguém deu lance desta vez. Você pode colocar a obra em leilão de novo editando a obra.',
            ],
            default => ['Aviso', ''],
        };
    }

    // Avisos de uma pessoa, mais novos primeiro, com o contato atual da outra parte e o link da obra.
    public function doUsuario(int $idUsuario): array
    {
        $s = Database::connection()->prepare(
            'SELECT n.*, l.id_obra,
                    c.nome AS contatoNome, c.email AS contatoEmail, c.contatoVenda, c.fotoPerfil AS contatoFoto
               FROM Notificacao n
               LEFT JOIN Leilao l ON l.id = n.id_leilao
               LEFT JOIN Usuario c ON c.id = n.id_contato
              WHERE n.id_usuario = ?
              ORDER BY n.criadaEm DESC, n.id DESC
              LIMIT 100'
        );
        $s->execute([$idUsuario]);
        return $s->fetchAll();
    }

    public function naoLidas(int $idUsuario): int
    {
        $s = Database::connection()->prepare('SELECT COUNT(*) FROM Notificacao WHERE id_usuario = ? AND lidaEm IS NULL');
        $s->execute([$idUsuario]);
        return (int) $s->fetchColumn();
    }

    public function marcarLidas(int $idUsuario): void
    {
        $s = Database::connection()->prepare('UPDATE Notificacao SET lidaEm = NOW() WHERE id_usuario = ? AND lidaEm IS NULL');
        $s->execute([$idUsuario]);
    }
}
