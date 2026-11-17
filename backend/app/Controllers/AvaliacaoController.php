<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Avaliacao;
use App\Models\Obra;

final class AvaliacaoController
{
    // CREATE / UPDATE: id_avaliacao vazio = nova; preenchido = edição da própria.
    public function save(): void
    {
        $u = Auth::requireLogin('Entre na sua conta para avaliar.');
        Csrf::verify();
        $idObra = positiveId($_POST['id_obra'] ?? null);
        $obra = $idObra ? (new Obra())->find($idObra) : null;
        if (!$obra) {
            flash('warning', 'Obra não encontrada.');
            go('home');
        }
        // RN-04/RN-05: artista não avalia a própria obra.
        if ((int) $obra['id_usuario'] === (int) $u['id']) {
            flash('warning', 'Artistas não avaliam a própria obra.');
            go('obras.show', ['id' => $idObra]);
        }

        $nota = filter_var($_POST['nota'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        if ($nota === false || mb_strlen($comentario) > 1000) {
            flash('danger', 'Escolha uma nota de 1 a 5 e um comentário de até 1000 caracteres.');
            go('obras.show', ['id' => $idObra], 'avaliar');
        }

        $model = new Avaliacao();
        $idAvaliacao = positiveId($_POST['id_avaliacao'] ?? null);
        // Uma avaliação por pessoa por obra: se já existe, vira edição.
        $existente = $idAvaliacao ? $model->find($idAvaliacao) : $model->doUsuarioNaObra((int) $u['id'], $idObra);
        if ($existente) {
            // RN-06: só edita a própria avaliação, e da mesma obra.
            if ((int) $existente['id_usuario'] !== (int) $u['id'] || (int) $existente['id_obra'] !== $idObra) {
                http_response_code(403);
                exit('Você só pode editar as suas avaliações.');
            }
            $model->update((int) $existente['id'], (int) $u['id'], $nota, $comentario);
            flash('success', 'Avaliação atualizada.');
        } elseif ($idAvaliacao) {
            flash('warning', 'Avaliação não encontrada.');
        } else {
            $model->create((int) $u['id'], $idObra, $nota, $comentario);
            flash('success', 'Obrigado pela avaliação!');
        }
        go('obras.show', ['id' => $idObra], 'avaliacoes');
    }

    // DELETE: só quem escreveu (RN-06).
    public function delete(): void
    {
        $u = Auth::requireLogin();
        Csrf::verify();
        $id = positiveId($_POST['id_avaliacao'] ?? null);
        $model = new Avaliacao();
        $avaliacao = $id ? $model->find($id) : null;
        if (!$avaliacao || !$model->delete($id, (int) $u['id'])) {
            flash('warning', 'Avaliação não encontrada ou não é sua.');
            go($avaliacao ? 'obras.show' : 'home', $avaliacao ? ['id' => $avaliacao['id_obra']] : []);
        }
        flash('success', 'Avaliação excluída.');
        go('obras.show', ['id' => $avaliacao['id_obra']], 'avaliacoes');
    }
}
