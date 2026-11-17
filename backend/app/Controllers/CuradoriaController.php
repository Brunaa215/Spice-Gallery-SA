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
        if ($decisao === 'aprovar' && $leilao && strtotime($leilao['dataFim']) <= time()) {
            flash('warning', 'O prazo deste leilão já passou. Reprove para o artista marcar de novo.');
            go('curadoria');
        }
        $ok = $model->decidir($id, $decisao === 'aprovar' ? Leilao::APROVADO : Leilao::REPROVADO);
        flash($ok ? 'success' : 'warning', $ok
            ? ($decisao === 'aprovar' ? 'Leilão aprovado.' : 'Leilão reprovado.')
            : 'Leilão não encontrado ou já decidido.');
        go('curadoria');
    }
}
