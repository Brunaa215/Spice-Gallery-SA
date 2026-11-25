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

        // dataInicio já vem sugerida (daqui a 10 minutos): se a pessoa escolher "data marcada",
        // o campo não aparece vazio.
        $obra = ['id' => '', 'titulo' => '', 'descricao' => '', 'categoria' => '', 'urlImagem' => '',
                 'inicioModo' => 'aprovacao', 'unidade' => 'minutos', 'dataInicio' => date('Y-m-d\TH:i', time() + 600)];
        $leilao = null;
        if ($id) {
            $obra = array_merge($obra, $this->doDono($id, $u));
            $leilao = (new Leilao())->daObra($id);
            // Leilão que ainda espera a curadoria: o formulário já vem com os dados dele, para alterar.
            if ($leilao && $leilao['status'] === Leilao::AGUARDANDO)
                $obra = array_merge($obra, $this->camposDoLeilao($leilao));
        }
        // Depois de um erro de validação, o formulário volta com o que foi digitado.
        $old = takeOld();
        $obra = array_merge($obra, array_intersect_key($old, array_flip(
            ['titulo', 'descricao', 'categoria', 'disponivelLeilao', 'valorInicial', 'incremento', 'inicioModo', 'dataInicio', 'duracao', 'unidade']
        )));

        View::render('obras/form', [
            'title' => $id ? 'Editar obra' : 'Publicar obra',
            'description' => 'Publique sua obra no Spice Gallery.',
            'obra' => $obra,
            'editando' => (bool) $id,
            'leilao' => $leilao,
            'categorias' => Obra::CATEGORIAS,
            'unidades' => Leilao::UNIDADES,
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
        // Aguardando a curadoria: pode ser alterado ou cancelado. Aprovado e em andamento, ou vendido:
        // fica como está. Reprovado ou encerrado sem lances: a obra pode ir a um leilão novo.
        $pendente = $leilaoExistente && $leilaoExistente['status'] === Leilao::AGUARDANDO;
        $travado = $leilaoExistente && Leilao::travado($leilaoExistente);

        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $categoria = (string) ($_POST['categoria'] ?? '');
        $marcouLeilao = ($_POST['disponivelLeilao'] ?? '') === '1';
        $querLeilao = $marcouLeilao && !$travado;
        $cancelarPendente = $pendente && !$marcouLeilao;
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

        // RN-07/RN-08: dados do leilão. Ele dura de 1 minuto a 30 dias e começa quando a curadoria
        // aprovar (as datas gravadas são provisórias, só a duração vale) ou numa data marcada.
        $valorInicial = $incremento = $inicio = $fim = null;
        $abreNaAprovacao = false;
        if ($querLeilao) {
            $valorInicial = money((string) ($_POST['valorInicial'] ?? ''));
            $incremento = money((string) ($_POST['incremento'] ?? ''));
            $abreNaAprovacao = ($_POST['inicioModo'] ?? '') !== 'data';
            $duracao = filter_var($_POST['duracao'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $unidade = (string) ($_POST['unidade'] ?? '');
            $minutos = $duracao !== false && isset(Leilao::UNIDADES[$unidade]) ? $duracao * Leilao::UNIDADES[$unidade] : null;
            $inicio = $abreNaAprovacao ? date('Y-m-d H:i:s') : localDateTime((string) ($_POST['dataInicio'] ?? ''));

            if ($valorInicial === null || (float) $valorInicial < 1)
                $erros[] = 'O lance inicial precisa ser de pelo menos R$ 1.';
            if ($incremento === null || (float) $incremento < 1)
                $erros[] = 'O incremento mínimo precisa ser de pelo menos R$ 1.';
            if ($minutos === null)
                $erros[] = 'Informe a duração do leilão (por exemplo, 10 minutos).';
            elseif ($minutos > Leilao::DURACAO_MAXIMA)
                $erros[] = 'O leilão pode durar no máximo ' . duracao(Leilao::DURACAO_MAXIMA) . '.';
            if (!$inicio)
                $erros[] = 'Informe a data e a hora de início do leilão.';
            elseif (!$abreNaAprovacao && strtotime($inicio) < time() - 60)
                $erros[] = 'O início do leilão precisa ser no futuro.';
            if ($inicio && $minutos !== null)
                $fim = date('Y-m-d H:i:s', strtotime($inicio) + $minutos * 60);
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
            $leiloes = new Leilao();
            if ($querLeilao && $pendente)
                $leiloes->atualizarPendente((int) $leilaoExistente['id'], $valorInicial, $incremento, $inicio, $fim, $abreNaAprovacao);
            elseif ($querLeilao)
                $leiloes->create($id, $valorInicial, $incremento, $inicio, $fim, $abreNaAprovacao);
            elseif ($cancelarPendente)
                $leiloes->cancelarPendente((int) $leilaoExistente['id']);
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
        $msg .= match (true) {
            $querLeilao && $pendente => ' O leilão (ainda com a curadoria) foi alterado.',
            $querLeilao => ' O leilão foi enviado para a curadoria.',
            $cancelarPendente => ' O leilão que esperava a curadoria foi cancelado.',
            default => '',
        };
        flash('success', $msg);
        go('obras.show', ['id' => $id]);
    }

    // Leilão gravado -> valores dos campos do formulário (duração na maior unidade exata).
    private function camposDoLeilao(array $leilao): array
    {
        $minutos = Leilao::duracaoMinutos($leilao);
        $unidade = 'minutos';
        foreach (array_reverse(Leilao::UNIDADES, true) as $nome => $tamanho)
            if ($minutos % $tamanho === 0) {
                $unidade = $nome;
                break;
            }
        $inteiro = fn(string $v): string => fmod((float) $v, 1.0) == 0 ? (string) (int) $v : $v;
        $campos = [
            'disponivelLeilao' => '1',
            'valorInicial' => $inteiro((string) $leilao['valorInicial']),
            'incremento' => $inteiro((string) $leilao['incremento']),
            'inicioModo' => (int) $leilao['abreNaAprovacao'] ? 'aprovacao' : 'data',
            'duracao' => (string) intdiv($minutos, Leilao::UNIDADES[$unidade]),
            'unidade' => $unidade,
        ];
        // Com "na aprovação" a data gravada é provisória: fica a sugestão padrão do formulário.
        if (!(int) $leilao['abreNaAprovacao'])
            $campos['dataInicio'] = date('Y-m-d\TH:i', strtotime($leilao['dataInicio']));
        return $campos;
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
        // Obra arrematada já foi paga com créditos: ela pertence a quem venceu.
        if ($leilao && $leilao['id_vencedor']) {
            flash('warning', 'Esta obra foi arrematada em leilão e não pode mais ser excluída.');
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
