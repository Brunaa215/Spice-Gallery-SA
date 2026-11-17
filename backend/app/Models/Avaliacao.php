<?php
namespace App\Models;

use App\Core\Database;

final class Avaliacao
{
    // Comentários da obra, do mais recente para o mais antigo.
    public function daObra(int $idObra): array
    {
        $s = Database::connection()->prepare(
            'SELECT a.*, u.nome FROM Avaliacao a JOIN Usuario u ON u.id = a.id_usuario
              WHERE a.id_obra = ? AND u.ativo = 1 ORDER BY a.dataHora DESC, a.id DESC'
        );
        $s->execute([$idObra]);
        return $s->fetchAll();
    }

    // Quantas notas 5, 4, 3, 2 e 1 a obra recebeu: [5 => 25, 4 => 4, ...]
    public function distribuicao(int $idObra): array
    {
        $s = Database::connection()->prepare('SELECT nota, COUNT(*) AS total FROM Avaliacao WHERE id_obra = ? GROUP BY nota');
        $s->execute([$idObra]);
        $d = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($s->fetchAll() as $linha)
            $d[(int) $linha['nota']] = (int) $linha['total'];
        return $d;
    }

    public function find(int $id): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM Avaliacao WHERE id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    // Uma avaliação por pessoa em cada obra.
    public function doUsuarioNaObra(int $idUsuario, int $idObra): ?array
    {
        $s = Database::connection()->prepare('SELECT * FROM Avaliacao WHERE id_usuario = ? AND id_obra = ? LIMIT 1');
        $s->execute([$idUsuario, $idObra]);
        return $s->fetch() ?: null;
    }

    public function create(int $idUsuario, int $idObra, int $nota, string $comentario): void
    {
        $s = Database::connection()->prepare('INSERT INTO Avaliacao (nota, comentario, id_usuario, id_obra) VALUES (?, ?, ?, ?)');
        $s->execute([$nota, $comentario === '' ? null : $comentario, $idUsuario, $idObra]);
    }

    // RN-06: o WHERE com id_usuario garante que só o dono altera ou apaga.
    public function update(int $id, int $idUsuario, int $nota, string $comentario): bool
    {
        $s = Database::connection()->prepare('UPDATE Avaliacao SET nota = ?, comentario = ?, dataHora = NOW() WHERE id = ? AND id_usuario = ?');
        $s->execute([$nota, $comentario === '' ? null : $comentario, $id, $idUsuario]);
        return $s->rowCount() > 0;
    }

    public function delete(int $id, int $idUsuario): bool
    {
        $s = Database::connection()->prepare('DELETE FROM Avaliacao WHERE id = ? AND id_usuario = ?');
        $s->execute([$id, $idUsuario]);
        return $s->rowCount() > 0;
    }
}
