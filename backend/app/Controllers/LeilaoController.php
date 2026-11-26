<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Lance;
use App\Models\Leilao;
use App\Models\Movimentacao;
use App\Models\Notificacao;
use RuntimeException;

final class LeilaoController
{
    public function index(): void
    {
        View::render('leiloes/index', [
            'title' => 'Leilões',
            'description' => 'Leilões do Spice Gallery: lances ao vivo em obras de jovens artistas.',
            'menu' => 'leiloes',
            'leiloes' => (new Leilao())->publicos(),
        ]);
    }

    public function show(): void
    {
        $id = positiveId($_GET['id'] ?? null);
        $dados = $id ? $this->dados($id) : null;
        if (!$dados)
            View::notFound('Este lote não está na sala.');

        View::render('leiloes/show', [
            'title' => $dados['leilao']['titulo'] . ' · ' . numeroLote($dados['leilao']['id']),
            'menu' => 'leiloes',
        ] + $dados);
    }

    // Leilão ao vivo: o app.js chama esta rota a cada poucos segundos (?v= é a versão que ele tem).
    // Se nada mudou, devolve só a versão; se mudou, devolve o painel e o histórico novos em HTML.
    // Como toda requisição passa pelo index.php, perguntar também fecha o leilão na hora certa.
    public function estado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $id = positiveId($_GET['id'] ?? null);
        $dados = $id ? $this->dados($id) : null;
        if (!$dados) {
            http_response_code(404);
            echo json_encode(['erro' => 'Leilão não encontrado.']);
            return;
        }
        $u = Auth::user();
        $resposta = [
            'versao' => $dados['versao'],
            'estado' => $dados['leilao']['estado'],
            'souLider' => $dados['souLider'],
            // Saldo do menu "Carteira" (muda quando o leilão fecha e a pessoa arremata ou vende).
            'saldo' => $u ? brl($u['saldo']) : null,
            // Número do sino (o aviso de quem venceu chega na hora em que o martelo bate).
            'notificacoes' => $u ? (new Notificacao())->naoLidas((int) $u['id']) : null,
        ];
        if (($_GET['v'] ?? '') !== $dados['versao']) {
            $resposta['painel'] = $dados['painel'];
            $resposta['historico'] = $dados['historico'];
        }
        echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // Dar lance: a validação de verdade (RN-05, RN-08) acontece no Model, dentro da transação.
    public function bid(): void
    {
        $u = Auth::requireLogin('Entre na sua conta para dar lances.');
        Csrf::verify();
        $id = positiveId($_POST['id_leilao'] ?? null);
        $valor = money((string) ($_POST['valor'] ?? ''));
        if (!$id) {
            flash('danger', 'Leilão inválido.');
            go('leiloes');
        }
        if ($valor === null || (float) $valor <= 0) {
            flash('danger', 'Informe um valor de lance válido.');
            go('leiloes.show', ['id' => $id]);
        }
        try {
            $prorrogou = (new Lance())->registrar($id, (int) $u['id'], $valor);
            flash('success', 'Lance de ' . brl($valor) . ' registrado. Boa sorte!'
                . ($prorrogou ? ' Foi no último minuto: o leilão ganhou mais ' . duracao(intdiv(Leilao::PRORROGACAO_SEGUNDOS, 60)) . '.' : ''));
        } catch (RuntimeException $e) {
            flash('warning', $e->getMessage());
        }
        go('leiloes.show', ['id' => $id]);
    }

    // Tudo o que a página do leilão e a atualização ao vivo precisam; null se o lote não pode ser
    // visto por quem pediu (leilão ainda não aprovado só aparece para o autor e para a curadoria).
    private function dados(int $id): ?array
    {
        $leilao = (new Leilao())->find($id);
        $u = Auth::user();
        $ehAutor = $leilao && $u && (int) $u['id'] === (int) $leilao['idAutor'];
        if (!$leilao || ($leilao['status'] !== Leilao::APROVADO && !$ehAutor && !Auth::isAdmin()))
            return null;

        $lances = (new Lance())->doLeilao($id);
        $partes = [
            'leilao' => $leilao,
            'lances' => $lances,
            'ehAutor' => $ehAutor,
            'isAdmin' => Auth::isAdmin(),
            'current' => $u,
            // Crédito que a pessoa ainda pode usar em lances (saldo menos o que está reservado).
            'disponivel' => $u ? (new Movimentacao())->resumo((int) $u['id'])['disponivel'] : 0,
        ];
        $painel = View::capturar('leilao_painel', $partes);
        $historico = View::capturar('leilao_historico', $partes);
        $lider = $lances[0] ?? null;
        return [
            'leilao' => $leilao,
            'painel' => $painel,
            'historico' => $historico,
            'versao' => md5($painel . $historico),
            'souLider' => $u && $lider && (int) $lider['id_usuario'] === (int) $u['id'],
        ];
    }
}
