<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Upload;
use App\Core\View;
use App\Models\Avaliacao;
use App\Models\Leilao;
use App\Models\Obra;
use App\Models\Usuario;
use RuntimeException;
use Throwable;

final class ObraController
{
    // READ: detalhe da obra com avaliações
    public function show(): void
    {
        $id = positiveId($_GET['id'] ?? null);
        $obra = $id ? (new Obra())->find($id) : null;
        if (!$obra)
            View::notFound('Esta obra não está na parede.');

        $u = Auth::user();
        $avaliacoes = new Avaliacao();
        View::render('obras/show', [
            'title' => $obra['titulo'] . ', de ' . $obra['autor'],
            'description' => mb_substr((string) $obra['descricao'], 0, 155),
            'menu' => 'galeria',
            'obra' => $obra,
            'artista' => (new Usuario())->artista((int) $obra['id_usuario']),
            'avaliacoes' => $avaliacoes->daObra($id),
            'distribuicao' => $avaliacoes->distribuicao($id),
            'minha' => $u ? $avaliacoes->doUsuarioNaObra((int) $u['id'], $id) : null,
            'ehAutor' => $u && (int) $u['id'] === (int) $obra['id_usuario'],
            'leilao' => (new Leilao())->daObra($id),
            'maisDoAutor' => array_slice((new Obra())->doAutor((int) $obra['id_usuario'], $id), 0, 3),
        ]);
    }

    // Formulário de publicar (sem id) ou editar (com id; só o autor).
    public function form(): void
    {
        $u = Auth::requireLogin('Entre na sua conta para publicar uma obra.');
        $id = isset($_GET['id']) ? positiveId($_GET['id']) : null;
        if (isset($_GET['id']) && !$id)
            View::notFound('Obra não encontrada.');

        $obra = ['id' => '', 'titulo' => '', 'descricao' => '', 'categoria' => '', 'urlImagem' => ''];
        $leilao = null;
        if ($id) {
            $obra = $this->doDono($id, $u);
            $leilao = (new Leilao())->daObra($id);
        }
        // Depois de um erro de validação, o formulário volta com o que foi digitado.
        $old = takeOld();
        $obra = array_merge($obra, array_intersect_key($old, array_flip(
            ['titulo', 'descricao', 'categoria', 'disponivelLeilao', 'valorInicial', 'incremento', 'dataInicio', 'dataFim']
        )));

        View::render('obras/form', [
            'title' => $id ? 'Editar obra' : 'Publicar obra',
            'description' => 'Publique sua obra no Spice Gallery.',
            'obra' => $obra,
            'editando' => (bool) $id,
            'leilao' => $leilao,
            'categorias' => Obra::CATEGORIAS,
        ]);
    }

    // CREATE e UPDATE: grava a obra e, se pedido, o leilão (que vai para a curadoria).
    public function save(): void
    {
        // POST maior que o post_max_size do PHP chega vazio (sem token e sem arquivo).
        if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('danger', 'O envio passou do limite do servidor. Use uma imagem menor.');
            go('obras.create');
        }
        $u = Auth::requireLogin();
        Csrf::verify();

        $idTexto = (string) ($_POST['id'] ?? '');
        $id = $idTexto === '' ? null : positiveId($idTexto);
        if ($idTexto !== '' && !$id) {
            flash('danger', 'ID inválido.');
            go('home');
        }
        $atual = $id ? $this->doDono($id, $u) : null;
        $leilaoExistente = $id ? (new Leilao())->daObra($id) : null;

        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $categoria = (string) ($_POST['categoria'] ?? '');
        $querLeilao = ($_POST['disponivelLeilao'] ?? '') === '1' && !$leilaoExistente;
        $arquivo = $_FILES['imagem'] ?? null;

        $erros = [];
        if ($titulo === '' || mb_strlen($titulo) > 255)
            $erros[] = 'Informe um título de até 255 caracteres.';
        if ($descricao === '' || mb_strlen($descricao) > 2000)
            $erros[] = 'Escreva uma descrição de até 2000 caracteres.';
        if (!isset(Obra::CATEGORIAS[$categoria]))
            $erros[] = 'Escolha uma categoria.';
        if (($_POST['aceiteTermos'] ?? '') !== '1')
            $erros[] = 'É preciso aceitar os termos de direitos autorais.';
        if (!$id && !Upload::enviado($arquivo))
            $erros[] = 'Escolha a imagem da obra.';

        // RN-07/RN-08: dados do leilão
        $valorInicial = $incremento = $inicio = $fim = null;
        if ($querLeilao) {
            $valorInicial = money((string) ($_POST['valorInicial'] ?? ''));
            $incremento = money((string) ($_POST['incremento'] ?? ''));
            $inicio = localDateTime((string) ($_POST['dataInicio'] ?? ''));
            $fim = localDateTime((string) ($_POST['dataFim'] ?? ''));
            if ($valorInicial === null || (float) $valorInicial < 1)
                $erros[] = 'O lance inicial precisa ser de pelo menos R$ 1.';
            if ($incremento === null || (float) $incremento < 1)
                $erros[] = 'O incremento mínimo precisa ser de pelo menos R$ 1.';
            if (!$inicio || !$fim)
                $erros[] = 'Informe início e término do leilão.';
            elseif (strtotime($inicio) < time() - 60)
                $erros[] = 'O início do leilão precisa ser no futuro.';
            elseif (strtotime($fim) <= strtotime($inicio))
                $erros[] = 'O término precisa ser depois do início.';
        }

        if ($erros)
            $this->voltar($id, implode(' ', $erros));

        try {
            $novaImagem = Upload::enviado($arquivo) ? Upload::imagem($arquivo) : null;
        } catch (RuntimeException $e) {
            $this->voltar($id, $e->getMessage());
        }

        // Obra e leilão entram juntos ou nenhum entra.
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $obras = new Obra();
            if ($id)
                $obras->update($id, $titulo, $descricao, $categoria, $novaImagem);
            else
                $id = $obras->create((int) $u['id'], $titulo, $descricao, $categoria, $novaImagem);
            if ($querLeilao)
                (new Leilao())->create($id, $valorInicial, $incremento, $inicio, $fim);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            Upload::remover($novaImagem);
            throw $e;
        }
        if ($atual && $novaImagem)
            Upload::remover($atual['urlImagem']);

        $msg = $atual ? 'Obra atualizada.' : 'Obra publicada! Ela já está na parede.';
        flash('success', $msg . ($querLeilao ? ' O leilão foi enviado para a curadoria.' : ''));
        go('obras.show', ['id' => $id]);
    }

    // DELETE: o autor (ou a curadoria) apaga a obra.
    public function delete(): void
    {
        $u = Auth::requireLogin();
        Csrf::verify();
        $id = positiveId($_POST['id'] ?? null);
        $obra = $id ? (new Obra())->find($id) : null;
        if (!$obra) {
            flash('warning', 'Obra não encontrada.');
            go('home');
        }
        $ehAutor = (int) $obra['id_usuario'] === (int) $u['id'];
        if (!$ehAutor && !Auth::isAdmin()) {
            http_response_code(403);
            exit('Você só pode excluir as suas obras.');
        }
        // Leilão ao vivo com lances não pode sumir: alguém já se comprometeu a pagar.
        $leilao = (new Leilao())->daObra($id);
        if ($leilao && $leilao['estado'] === 'AO_VIVO' && (int) $leilao['lances'] > 0) {
            flash('warning', 'Esta obra está em leilão com lances e não pode ser excluída agora.');
            go('obras.show', ['id' => $id]);
        }
        (new Obra())->delete($id);
        Upload::remover($obra['urlImagem']);
        flash('success', 'Obra excluída.');
        go('artistas.show', ['id' => $obra['id_usuario']]);
    }

    // Busca a obra e garante que o usuário logado é o autor.
    private function doDono(int $id, array $u): array
    {
        $obra = (new Obra())->find($id);
        if (!$obra)
            View::notFound('Obra não encontrada.');
        if ((int) $obra['id_usuario'] !== (int) $u['id']) {
            http_response_code(403);
            exit('Você só pode editar as suas obras.');
        }
        return $obra;
    }

    private function voltar(?int $id, string $mensagem): never
    {
        $dados = $_POST;
        unset($dados['csrf']);
        remember($dados);
        flash('danger', $mensagem);
        $id ? go('obras.edit', ['id' => $id]) : go('obras.create');
    }
}
