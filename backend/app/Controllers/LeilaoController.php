<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Lance;
use App\Models\Leilao;
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
        $leilao = $id ? (new Leilao())->find($id) : null;
        $u = Auth::user();
        $ehAutor = $leilao && $u && (int) $u['id'] === (int) $leilao['idAutor'];
        // Leilão ainda não aprovado só aparece para o autor e para a curadoria.
        if (!$leilao || ($leilao['status'] !== Leilao::APROVADO && !$ehAutor && !Auth::isAdmin()))
            View::notFound('Este lote não está na sala.');

        View::render('leiloes/show', [
            'title' => $leilao['titulo'] . ' · ' . numeroLote($leilao['id']),
            'menu' => 'leiloes',
            'leilao' => $leilao,
            'lances' => (new Lance())->doLeilao($id),
            'ehAutor' => $ehAutor,
        ]);
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
            (new Lance())->registrar($id, (int) $u['id'], $valor);
            flash('success', 'Lance de ' . brl($valor) . ' registrado. Boa sorte!');
        } catch (RuntimeException $e) {
            flash('warning', $e->getMessage());
        }
        go('leiloes.show', ['id' => $id]);
    }
}
