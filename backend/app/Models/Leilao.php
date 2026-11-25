<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class Leilao
{
    // Leilao.status (EnumLeilao), gravado no banco
    public const AGUARDANDO = 'AGUARDANDO_CURADORIA';
    public const APROVADO = 'APROVADO';
    public const REPROVADO = 'REPROVADO';

    // Duração escolhida pelo artista: número + unidade (o valor é quantos minutos cada unidade tem).
    public const UNIDADES = ['minutos' => 1, 'horas' => 60, 'dias' => 1440];
    public const DURACAO_MAXIMA = 30 * 1440; // 30 dias, em minutos

    // Prorrogação ("dou-lhe uma, dou-lhe duas..."): um lance que chega quando falta menos que isto
    // empurra o fim para este tempo depois do lance. 0 desliga.
    public const PRORROGACAO_SEGUNDOS = 60;

    // "estado" é calculado: um leilão APROVADO vira EM_BREVE, AO_VIVO ou ENCERRADO pelo relógio.
    public const ESTADOS = [
        'AO_VIVO' => 'Ao vivo',
        'EM_BREVE' => 'Em breve',
        'ENCERRADO' => 'Encerrado',
        self::AGUARDANDO => 'Aguardando curadoria',
        self::REPROVADO => 'Reprovado pela curadoria',
    ];

    // Leilão + obra + autor + maior lance + quantidade de lances + estado + quem arrematou.
    private const SELECT = "
        SELECT l.*, o.titulo, o.urlImagem, o.categoria, o.id_usuario AS idAutor, u.nome AS autor, u.fotoPerfil AS autorFoto,
               COALESCE(x.maior, 0) AS maiorLance, COALESCE(x.total, 0) AS lances, v.nome AS vencedor,
               CASE WHEN l.status <> 'APROVADO' THEN l.status
                    WHEN NOW() < l.dataInicio THEN 'EM_BREVE'
                    WHEN NOW() <= l.dataFim THEN 'AO_VIVO'
                    ELSE 'ENCERRADO' END AS estado
          FROM Leilao l
          JOIN Obra o ON o.id = l.id_obra
          JOIN Usuario u ON u.id = o.id_usuario
          LEFT JOIN Usuario v ON v.id = l.id_vencedor
          LEFT JOIN (SELECT id_leilao, MAX(valor) AS maior, COUNT(*) AS total
                       FROM Lance GROUP BY id_leilao) x ON x.id_leilao = l.id
         WHERE o.status = 'PUBLICADA' AND u.ativo = 1";

    // Lance atual: o maior lance ou, sem lances, o valor inicial.
    public static function atual(array $l): float
    {
        return (int) $l['lances'] > 0 ? (float) $l['maiorLance'] : (float) $l['valorInicial'];
    }

    // RN-08: o primeiro lance vale o valor inicial; os seguintes, o atual + incremento.
    public static function minimo(array $l): float
    {
        return (int) $l['lances'] > 0 ? (float) $l['maiorLance'] + (float) $l['incremento'] : (float) $l['valorInicial'];
    }

    public static function estado(array $l): string
    {
        return self::ESTADOS[$l['estado']] ?? $l['estado'];
    }

    // Duração em minutos (para "começa quando aprovar", as datas guardadas são provisórias,
    // mas a diferença entre elas é a duração escolhida).
    public static function duracaoMinutos(array $l): int
    {
        return (int) round((strtotime($l['dataFim']) - strtotime($l['dataInicio'])) / 60);
    }

    // Leilão que não pode mais ser mexido nem substituído: aprovado e ainda não encerrado,
    // ou encerrado com lances (a obra foi vendida). Reprovado ou encerrado sem lances libera
    // a obra para um leilão novo; aguardando a curadoria ainda pode ser alterado ou cancelado.
    public static function travado(array $l): bool
    {
        return $l['status'] === self::APROVADO && ($l['estado'] !== 'ENCERRADO' || (int) $l['lances'] > 0);
    }

    // Página "Leilões": só os aprovados; ao vivo primeiro, depois em breve, depois encerrados.
    public function publicos(): array
    {
        return Database::connection()->query(
            'SELECT * FROM (' . self::SELECT . " AND l.status = 'APROVADO') t
             ORDER BY FIELD(estado, 'AO_VIVO', 'EM_BREVE', 'ENCERRADO'), dataFim"
        )->fetchAll();
    }

    // Vitrine da home: o leilão ao vivo com mais lances.
    public function destaque(): ?array
    {
        return Database::connection()->query(
            'SELECT * FROM (' . self::SELECT . ") t WHERE estado = 'AO_VIVO' ORDER BY lances DESC, dataFim LIMIT 1"
        )->fetch() ?: null;
    }

    public function totalAoVivo(): int
    {
        return (int) Database::connection()->query(
            'SELECT COUNT(*) FROM (' . self::SELECT . ") t WHERE estado = 'AO_VIVO'"
        )->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare(self::SELECT . ' AND l.id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    // Leilão em andamento de uma obra (ignora os reprovados, que podem ser refeitos).
    public function daObra(int $idObra): ?array
    {
        $s = Database::connection()->prepare(self::SELECT . " AND l.id_obra = ? AND l.status <> 'REPROVADO' ORDER BY l.id DESC LIMIT 1");
        $s->execute([$idObra]);
        return $s->fetch() ?: null;
    }

    // Todos os leilões das obras de um artista (aparece na sala dele).
    public function doArtista(int $idUsuario): array
    {
        $s = Database::connection()->prepare(self::SELECT . ' AND o.id_usuario = ? ORDER BY l.id DESC');
        $s->execute([$idUsuario]);
        return $s->fetchAll();
    }

    // Obras que a pessoa arrematou (aparece na carteira).
    public function arrematadosPor(int $idUsuario): array
    {
        $s = Database::connection()->prepare(self::SELECT . ' AND l.id_vencedor = ? ORDER BY l.dataFim DESC');
        $s->execute([$idUsuario]);
        return $s->fetchAll();
    }

    // Fecha os leilões que passaram do horário final. O index.php chama isto a cada acesso,
    // então o leilão "fecha sozinho" sem precisar de agendador (cron).
    public function liquidarEncerrados(): void
    {
        $ids = Database::connection()->query(
            "SELECT id FROM Leilao WHERE status = 'APROVADO' AND liquidadoEm IS NULL AND dataFim < NOW()"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            try {
                $this->liquidar((int) $id);
            } catch (Throwable $e) {
                // Um leilão com problema não derruba o site: fica no log e tenta de novo no próximo acesso.
                error_log('Falha ao fechar o leilão ' . $id . ': ' . $e);
            }
        }
    }

    // Martelo: o maior lance paga com créditos e o valor entra na carteira do artista.
    // Tudo numa transação, e cada leilão fecha uma vez só (liquidadoEm).
    private function liquidar(int $id): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            // O FOR UPDATE espera um lance que esteja sendo gravado agora e impede dois acessos
            // de fecharem o mesmo leilão (o segundo já encontra liquidadoEm preenchido).
            $s = $pdo->prepare(
                "SELECT l.dataFim, o.titulo, o.id_usuario AS idAutor
                   FROM Leilao l JOIN Obra o ON o.id = l.id_obra
                  WHERE l.id = ? AND l.status = 'APROVADO' AND l.liquidadoEm IS NULL AND l.dataFim < NOW()
                    FOR UPDATE"
            );
            $s->execute([$id]);
            $leilao = $s->fetch();
            if (!$leilao) {
                $pdo->commit();
                return;
            }

            $s = $pdo->prepare('SELECT valor, id_usuario FROM Lance WHERE id_leilao = ? ORDER BY valor DESC, id ASC LIMIT 1');
            $s->execute([$id]);
            $vencedor = $s->fetch();
            if ($vencedor) {
                $comprador = (int) $vencedor['id_usuario'];
                $artista = (int) $leilao['idAutor'];
                // Trava as duas contas sempre na mesma ordem (menor id primeiro) para não haver impasse.
                Movimentacao::travar($pdo, min($comprador, $artista));
                Movimentacao::travar($pdo, max($comprador, $artista));
                $valor = (string) $vencedor['valor'];
                Movimentacao::lancar($pdo, $comprador, Movimentacao::ARREMATE, '-' . $valor,
                    'Arremate: ' . $leilao['titulo'], $id, $leilao['dataFim']);
                Movimentacao::lancar($pdo, $artista, Movimentacao::VENDA, $valor,
                    'Venda em leilão: ' . $leilao['titulo'], $id, $leilao['dataFim']);
            }
            $s = $pdo->prepare('UPDATE Leilao SET liquidadoEm = NOW(), id_vencedor = ? WHERE id = ?');
            $s->execute([$vencedor ? (int) $vencedor['id_usuario'] : null, $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
    }

    // Quantos esperam a curadoria (o número que aparece no menu dela).
    public function totalPendentes(): int
    {
        return (int) Database::connection()->query(
            'SELECT COUNT(*) FROM (' . self::SELECT . " AND l.status = 'AGUARDANDO_CURADORIA') t"
        )->fetchColumn();
    }

    // Fila da curadoria (RN-07).
    public function pendentes(): array
    {
        return Database::connection()->query(self::SELECT . " AND l.status = 'AGUARDANDO_CURADORIA' ORDER BY l.dataInicio")->fetchAll();
    }

    // Com $abreNaAprovacao, $inicio/$fim são provisórios: só a duração entre eles importa.
    public function create(int $idObra, string $valorInicial, string $incremento, string $inicio, string $fim, bool $abreNaAprovacao): void
    {
        $s = Database::connection()->prepare(
            'INSERT INTO Leilao (dataInicio, dataFim, abreNaAprovacao, valorInicial, incremento, status, id_obra) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $s->execute([$inicio, $fim, (int) $abreNaAprovacao, $valorInicial, $incremento, self::AGUARDANDO, $idObra]);
    }

    // Enquanto a curadoria não decide, o artista pode mudar valores, início e duração...
    public function atualizarPendente(int $id, string $valorInicial, string $incremento, string $inicio, string $fim, bool $abreNaAprovacao): void
    {
        $s = Database::connection()->prepare(
            "UPDATE Leilao SET dataInicio = ?, dataFim = ?, abreNaAprovacao = ?, valorInicial = ?, incremento = ?
              WHERE id = ? AND status = 'AGUARDANDO_CURADORIA'"
        );
        $s->execute([$inicio, $fim, (int) $abreNaAprovacao, $valorInicial, $incremento, $id]);
    }

    // ...ou desistir dele (sem lances: ele nunca abriu).
    public function cancelarPendente(int $id): void
    {
        $s = Database::connection()->prepare("DELETE FROM Leilao WHERE id = ? AND status = 'AGUARDANDO_CURADORIA'");
        $s->execute([$id]);
    }

    // Aprova ou reprova; só muda quem ainda está aguardando.
    // Aprovado com abreNaAprovacao, o leilão começa agora e termina daqui a (fim - início) da duração
    // escolhida. dataFim vem antes de dataInicio no SET para ainda usar o início provisório.
    public function decidir(int $id, string $status): bool
    {
        $s = Database::connection()->prepare(
            "UPDATE Leilao
                SET status = ?,
                    dataFim = IF(? = 'APROVADO' AND abreNaAprovacao = 1,
                                 NOW() + INTERVAL TIMESTAMPDIFF(SECOND, dataInicio, dataFim) SECOND, dataFim),
                    dataInicio = IF(? = 'APROVADO' AND abreNaAprovacao = 1, NOW(), dataInicio)
              WHERE id = ? AND status = 'AGUARDANDO_CURADORIA'"
        );
        $s->execute([$status, $status, $status, $id]);
        return $s->rowCount() > 0;
    }
}
