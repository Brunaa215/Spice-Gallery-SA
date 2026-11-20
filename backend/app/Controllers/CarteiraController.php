<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Lance;
use App\Models\Leilao;
use App\Models\Movimentacao;
use RuntimeException;

// Carteira de créditos fictícios: saldo, compra simulada, lances reservados, arremates e extrato.
final class CarteiraController
{
    public function index(): void
    {
        $u = Auth::requireLogin('Entre na sua conta para ver seus créditos.');
        $id = (int) $u['id'];
        $carteira = new Movimentacao();
        View::render('carteira/index', [
            'title' => 'Carteira',
            'description' => 'Seus créditos fictícios no Spice Gallery.',
            'menu' => 'carteira',
            'resumo' => $carteira->resumo($id),
            'reservas' => (new Lance())->vencendo($id),
            'arremates' => (new Leilao())->arrematadosPor($id),
            'extrato' => $carteira->extrato($id),
            // Veio de um leilão sem crédito suficiente? Depois da compra, volta para ele.
            'voltarLeilao' => positiveId($_GET['leilao'] ?? null),
        ]);
    }

    public function comprar(): void
    {
        $u = Auth::requireLogin();
        Csrf::verify();
        $voltar = positiveId($_POST['voltar_leilao'] ?? null);
        $valor = (string) ($_POST['valor'] ?? '');
        if (!ctype_digit($valor) || !in_array((int) $valor, Movimentacao::PACOTES, true)) {
            flash('danger', 'Escolha um dos pacotes de créditos.');
            go('carteira');
        }
        try {
            (new Movimentacao())->comprar((int) $u['id'], (int) $valor);
            flash('success', brl($valor) . ' em créditos entraram na sua carteira.');
        } catch (RuntimeException $e) {
            flash('warning', $e->getMessage());
            go('carteira', $voltar ? ['leilao' => $voltar] : []);
        }
        $voltar ? go('leiloes.show', ['id' => $voltar]) : go('carteira');
    }
}
