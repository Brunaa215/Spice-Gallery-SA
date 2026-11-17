<?php
namespace App\Models;

use App\Core\Database;

final class Obra
{
    public const PUBLICADA = 'PUBLICADA';

    // valor do <select name="categoria"> => nome mostrado na placa
    public const CATEGORIAS = [
        'pintura' => 'Pintura',
        'digital' => 'Pintura digital',
        'aquarela' => 'Aquarela',
        'gravura' => 'Gravura',
        'fotografia' => 'Fotografia',
        'escultura' => 'Escultura',
    ];

    // Obra + nome do autor + média e total de avaliações + id do leilão ao vivo (se houver).
    // As médias vêm de uma subconsulta agrupada, assim o SELECT externo não precisa de GROUP BY.
    private const SELECT = "
        SELECT o.*, u.nome AS autor,
               s.media, COALESCE(s.total, 0) AS avaliacoes,
               (SELECT l.id FROM Leilao l
                 WHERE l.id_obra = o.id AND l.status = 'APROVADO' AND NOW() BETWEEN l.dataInicio AND l.dataFim
                 LIMIT 1) AS leilaoAoVivo
          FROM Obra o
          JOIN Usuario u ON u.id = o.id_usuario
          LEFT JOIN (SELECT id_obra, ROUND(AVG(nota), 1) AS media, COUNT(*) AS total
                       FROM Avaliacao GROUP BY id_obra) s ON s.id_obra = o.id
         WHERE o.status = 'PUBLICADA' AND u.ativo = 1";

    public static function categoria(string $chave): string
    {
        return self::CATEGORIAS[$chave] ?? ucfirst($chave);
    }

    public function publicadas(): array
    {
        return Database::connection()->query(self::SELECT . ' ORDER BY o.id DESC')->fetchAll();
    }

    // Corredor da home: as mais bem avaliadas (LIMIT é fixado no controller, não vem do usuário).
    public function destaques(int $limite = 6): array
    {
        $sql = self::SELECT . ' ORDER BY s.media IS NULL, s.media DESC, s.total DESC, o.id DESC LIMIT ' . max(1, min(20, $limite));
        return Database::connection()->query($sql)->fetchAll();
    }

    public function doAutor(int $idUsuario, int $exceto = 0): array
    {
        $s = Database::connection()->prepare(self::SELECT . ' AND o.id_usuario = ? AND o.id <> ? ORDER BY o.id');
        $s->execute([$idUsuario, $exceto]);
        return $s->fetchAll();
    }

    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare(self::SELECT . ' AND o.id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function total(): int
    {
        return (int) Database::connection()
            ->query("SELECT COUNT(*) FROM Obra o JOIN Usuario u ON u.id = o.id_usuario WHERE o.status = 'PUBLICADA' AND u.ativo = 1")
            ->fetchColumn();
    }

    public function create(int $idUsuario, string $titulo, string $descricao, string $categoria, string $urlImagem): int
    {
        $pdo = Database::connection();
        $s = $pdo->prepare('INSERT INTO Obra (titulo, descricao, urlImagem, status, categoria, id_usuario) VALUES (?, ?, ?, ?, ?, ?)');
        $s->execute([$titulo, $descricao, $urlImagem, self::PUBLICADA, $categoria, $idUsuario]);
        return (int) $pdo->lastInsertId();
    }

    // $urlImagem null = mantém a imagem atual.
    public function update(int $id, string $titulo, string $descricao, string $categoria, ?string $urlImagem): void
    {
        $s = Database::connection()->prepare(
            'UPDATE Obra SET titulo = ?, descricao = ?, categoria = ?, urlImagem = COALESCE(?, urlImagem) WHERE id = ?'
        );
        $s->execute([$titulo, $descricao, $categoria, $urlImagem, $id]);
    }

    // As FKs com ON DELETE CASCADE apagam avaliações, leilões e lances da obra.
    public function delete(int $id): void
    {
        $s = Database::connection()->prepare('DELETE FROM Obra WHERE id = ?');
        $s->execute([$id]);
    }
}
