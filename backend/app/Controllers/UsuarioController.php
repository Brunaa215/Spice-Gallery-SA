<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Usuario;

final class UsuarioController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('usuarios/index', [
            'title' => 'Contas',
            'menu' => 'curadoria',
            'usuarios' => (new Usuario())->all(),
        ]);
    }

    public function toggle(): void
    {
        $admin = Auth::requireAdmin();
        Csrf::verify();
        $id = positiveId($_POST['id'] ?? null);
        if (!$id || $id === (int) $admin['id']) {
            flash('warning', 'Você não pode desativar sua própria conta.');
            go('usuarios');
        }
        (new Usuario())->toggle($id);
        flash('success', 'Situação da conta atualizada.');
        go('usuarios');
    }
}
