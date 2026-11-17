<?php
namespace App\Models;

use App\Core\Database;

final class Usuario
{
    public const ARTISTA = 'ARTISTA';
    public const ADMIN = 'ADMIN';

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
            "SELECT u.id, u.nome, u.criadoEm,
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
            "SELECT u.id, u.nome,
                    COUNT(o.id) AS obras,
                    (SELECT o2.urlImagem FROM Obra o2 WHERE o2.id_usuario = u.id AND o2.status = 'PUBLICADA'
                      ORDER BY o2.id DESC LIMIT 1) AS capa,
                    (SELECT ROUND(AVG(a.nota), 1) FROM Avaliacao a JOIN Obra o3 ON o3.id = a.id_obra
                      WHERE o3.id_usuario = u.id AND o3.status = 'PUBLICADA') AS media
               FROM Usuario u
               JOIN Obra o ON o.id_usuario = u.id AND o.status = 'PUBLICADA'
              WHERE u.ativo = 1
              GROUP BY u.id, u.nome
              ORDER BY u.nome"
        )->fetchAll();
    }

    public function all(): array
    {
        return Database::connection()
            ->query('SELECT id, nome, email, perfil, ativo, criadoEm FROM Usuario ORDER BY id')
            ->fetchAll();
    }

    public function totalArtistas(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM Usuario WHERE ativo = 1 AND perfil = 'ARTISTA'")
            ->fetchColumn();
    }

    // RN-01/RN-02: toda conta nova é de artista e registra quando aceitou os termos.
    public function create(string $nome, string $email, string $senha): int
    {
        $pdo = Database::connection();
        $s = $pdo->prepare('INSERT INTO Usuario (nome, email, senhaHash, perfil, aceiteTermosEm) VALUES (?, ?, ?, ?, NOW())');
        $s->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), self::ARTISTA]);
        return (int) $pdo->lastInsertId();
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
