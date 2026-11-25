<?php
namespace App\Models;

use App\Core\Database;
use Throwable;

final class Usuario
{
    public const ARTISTA = 'ARTISTA';
    public const ADMIN = 'ADMIN';

    // Moldura da foto de perfil (Usuario.molduraFoto) => [nome mostrado, classe do quadro no CSS].
    // São as mesmas molduras das obras: o retrato do artista fica pendurado como um quadro.
    public const MOLDURAS = [
        'camafeu' => ['Camafeu dourado', 'quadro--fino'],
        'ouro'    => ['Ouro entalhado', 'quadro--ouro'],
        'rosa'    => ['Laca rosa', 'quadro--madeira'],
        'verde'   => ['Laca verde-sálvia', ''],
        'nenhuma' => ['Sem moldura', 'quadro--nu'],
    ];
    public const MAX_ESTILOS = 8;

    public static function classeMoldura(?string $moldura): string
    {
        return self::MOLDURAS[$moldura][1] ?? self::MOLDURAS['camafeu'][1];
    }

    // "Surrealismo, pop art; surrealismo" -> "Surrealismo, pop art" (sem vazios nem repetidos, até 8, 30 letras cada).
    public static function normalizarEstilos(string $texto): string
    {
        $vistos = [];
        foreach (preg_split('/[,;\n]+/', $texto) as $estilo) {
            $estilo = trim(preg_replace('/\s+/', ' ', $estilo));
            if ($estilo === '' || isset($vistos[mb_strtolower($estilo)]))
                continue;
            if (mb_strlen($estilo) > 30)
                throw new \RuntimeException('Cada estilo pode ter no máximo 30 letras ("' . mb_substr($estilo, 0, 30) . '...").');
            $vistos[mb_strtolower($estilo)] = $estilo;
        }
        if (count($vistos) > self::MAX_ESTILOS)
            throw new \RuntimeException('Escolha no máximo ' . self::MAX_ESTILOS . ' estilos.');
        return implode(', ', $vistos);
    }

    public static function estilos(?string $estilos): array
    {
        return $estilos ? array_map('trim', explode(',', $estilos)) : [];
    }

    public function byEmail(string $email): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM Usuario WHERE email = ? LIMIT 1');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM Usuario WHERE id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    // Artista ativo com os números da sala dele (obras, avaliações recebidas, média).
    public function artista(int $id): ?array
    {
        $s = Database::connection()->prepare(
            "SELECT u.id, u.nome, u.criadoEm, u.apelido, u.especialidade, u.estilos, u.fotoPerfil, u.molduraFoto, u.wallpaper,
                    (SELECT COUNT(*) FROM Obra o WHERE o.id_usuario = u.id AND o.status = 'PUBLICADA') AS obras,
                    (SELECT COUNT(*) FROM Avaliacao a JOIN Obra o ON o.id = a.id_obra
                      WHERE o.id_usuario = u.id AND o.status = 'PUBLICADA') AS avaliacoes,
                    (SELECT ROUND(AVG(a.nota), 1) FROM Avaliacao a JOIN Obra o ON o.id = a.id_obra
                      WHERE o.id_usuario = u.id AND o.status = 'PUBLICADA') AS media
               FROM Usuario u
              WHERE u.id = ? AND u.ativo = 1"
        );
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    // Lista da página "Artistas": quem tem obra publicada, com a capa mais recente.
    public function artistas(): array
    {
        return Database::connection()->query(
            "SELECT u.id, u.nome, u.especialidade,
                    COUNT(o.id) AS obras,
                    (SELECT o2.urlImagem FROM Obra o2 WHERE o2.id_usuario = u.id AND o2.status = 'PUBLICADA'
                      ORDER BY o2.id DESC LIMIT 1) AS capa,
                    (SELECT ROUND(AVG(a.nota), 1) FROM Avaliacao a JOIN Obra o3 ON o3.id = a.id_obra
                      WHERE o3.id_usuario = u.id AND o3.status = 'PUBLICADA') AS media
               FROM Usuario u
               JOIN Obra o ON o.id_usuario = u.id AND o.status = 'PUBLICADA'
              WHERE u.ativo = 1
              GROUP BY u.id, u.nome, u.especialidade
              ORDER BY u.nome"
        )->fetchAll();
    }

    public function all(): array
    {
        return Database::connection()
            ->query('SELECT id, nome, email, perfil, ativo, saldo, criadoEm FROM Usuario ORDER BY id')
            ->fetchAll();
    }

    public function totalArtistas(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM Usuario WHERE ativo = 1 AND perfil = 'ARTISTA'")
            ->fetchColumn();
    }

    // RN-01/RN-02: toda conta nova é de artista e registra quando aceitou os termos.
    // Ela já nasce com os créditos de boas-vindas; conta e créditos entram juntos ou nenhum entra.
    public function create(string $nome, string $email, string $senha): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare('INSERT INTO Usuario (nome, email, senhaHash, perfil, aceiteTermosEm) VALUES (?, ?, ?, ?, NOW())');
            $s->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), self::ARTISTA]);
            $id = (int) $pdo->lastInsertId();
            Movimentacao::lancar($pdo, $id, Movimentacao::BONUS, Movimentacao::BONUS_BOAS_VINDAS, 'Créditos de boas-vindas');
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            throw $e;
        }
    }

    // Perfil: $dados com nome, apelido, especialidade, estilos, fotoPerfil, molduraFoto, wallpaper
    // (vazio vira NULL). Chame dentro de uma transação, junto com RedeSocial::salvar().
    public static function atualizarPerfil(\PDO $pdo, int $id, array $dados): void
    {
        $nulo = fn(?string $v): ?string => $v === null || $v === '' ? null : $v;
        $s = $pdo->prepare(
            'UPDATE Usuario SET nome = ?, apelido = ?, especialidade = ?, estilos = ?, fotoPerfil = ?, molduraFoto = ?, wallpaper = ?
              WHERE id = ?'
        );
        $s->execute([
            $dados['nome'], $nulo($dados['apelido']), $nulo($dados['especialidade']), $nulo($dados['estilos']),
            $nulo($dados['fotoPerfil']), $dados['molduraFoto'], $nulo($dados['wallpaper']), $id,
        ]);
    }

    public function changePassword(int $id, string $senha): void
    {
        $s = Database::connection()->prepare('UPDATE Usuario SET senhaHash = ? WHERE id = ?');
        $s->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
    }

    public function toggle(int $id): void
    {
        $s = Database::connection()->prepare('UPDATE Usuario SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?');
        $s->execute([$id]);
    }
}
