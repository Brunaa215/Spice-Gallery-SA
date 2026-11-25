<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Leilao;

// RN-07: a curadoria aprova o leilão antes de ele abrir.
final class CuradoriaController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('curadoria/index', [
            'title' => 'Curadoria',
            'menu' => 'curadoria',
            'pendentes' => (new Leilao())->pendentes(),
        ]);
    }

    public function decide(): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $id = positiveId($_POST['id'] ?? null);
        $decisao = (string) ($_POST['decisao'] ?? '');
        if (!$id || !in_array($decisao, ['aprovar', 'reprovar'], true)) {
            flash('danger', 'Decisão inválida.');
            go('curadoria');
        }
        $model = new Leilao();
        $leilao = $model->find($id);
        // Data marcada que já passou: não dá para aprovar (o que começa na aprovação nunca vence).
        if ($decisao === 'aprovar' && $leilao && !(int) $leilao['abreNaAprovacao'] && strtotime($leilao['dataFim']) <= time()) {
            flash('warning', 'O prazo deste leilão já passou. Reprove para o artista marcar de novo.');
            $this->voltar($leilao);
        }
        $ok = $model->decidir($id, $decisao === 'aprovar' ? Leilao::APROVADO : Leilao::REPROVADO);
        $aprovado = $ok && $decisao === 'aprovar' ? $model->find($id) : null;
        flash($ok ? 'success' : 'warning', match (true) {
            !$ok => 'Leilão não encontrado ou já decidido.',
            $decisao === 'reprovar' => 'Leilão reprovado.',
            $aprovado && $aprovado['estado'] === 'AO_VIVO' => 'Leilão aprovado e começou: termina ' . dataHora($aprovado['dataFim']) . '.',
            default => 'Leilão aprovado: abre ' . dataHora($leilao['dataInicio']) . '.',
        });
        $this->voltar($leilao);
    }

    // Os botões da página do leilão e da obra mandam voltar=leilao|obra: a curadoria continua
    // onde estava (e vê o leilão já ao vivo). Sem isso, volta para a fila.
    private function voltar(?array $leilao): never
    {
        $voltar = (string) ($_POST['voltar'] ?? '');
        if ($leilao && $voltar === 'leilao')
            go('leiloes.show', ['id' => $leilao['id']]);
        if ($leilao && $voltar === 'obra')
            go('obras.show', ['id' => $leilao['id_obra']]);
        go('curadoria');
    }
}
