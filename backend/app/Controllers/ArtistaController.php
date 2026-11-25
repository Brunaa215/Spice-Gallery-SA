<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Leilao;
use App\Models\Obra;
use App\Models\RedeSocial;
use App\Models\Usuario;

final class ArtistaController
{
    public function index(): void
    {
        View::render('artistas/index', [
            'title' => 'Artistas',
            'menu' => 'artistas',
            'artistas' => (new Usuario())->artistas(),
        ]);
    }

    // Sala do artista. Sem ?id, abre a sala de quem está logado.
    public function show(): void
    {
        $u = Auth::user();
        $id = positiveId($_GET['id'] ?? null) ?? ($u ? (int) $u['id'] : null);
        if (!$id)
            go('artistas');
        $artista = (new Usuario())->artista($id);
        if (!$artista)
            View::notFound('Artista não encontrado.');

        $ehDono = $u && (int) $u['id'] === $id;
        View::render('artistas/show', [
            'title' => $artista['nome'],
            'description' => 'Galeria individual de ' . $artista['nome'] . ' no Spice Gallery.',
            'menu' => $ehDono ? 'minha-sala' : 'artistas',
            'artista' => $artista,
            'obras' => (new Obra())->doAutor($id),
            'ehDono' => $ehDono,
            'leiloes' => $ehDono ? (new Leilao())->doArtista($id) : [],
            // Coleção: obras de outros artistas que esta pessoa arrematou em leilão.
            'colecao' => (new Obra())->arrematadasPor($id),
            'redes' => (new RedeSocial())->doUsuario($id),
        ]);
    }
}
