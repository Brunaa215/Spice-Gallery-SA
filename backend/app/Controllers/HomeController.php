<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Leilao;
use App\Models\Obra;
use App\Models\Usuario;

final class HomeController
{
    public function index(): void
    {
        $obras = new Obra();
        $leiloes = new Leilao();
        View::render('home/index', [
            'title' => '',
            'menu' => 'galeria',
            'totais' => [
                'obras' => $obras->total(),
                'artistas' => (new Usuario())->totalArtistas(),
                'aoVivo' => $leiloes->totalAoVivo(),
            ],
            'destaques' => $obras->destaques(6),
            'obras' => $obras->publicadas(),
            'vitrine' => $leiloes->destaque(),
            'categorias' => Obra::CATEGORIAS,
        ]);
    }
}
