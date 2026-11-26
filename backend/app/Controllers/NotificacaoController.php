<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Notificacao;
use App\Models\RedeSocial;

// Aba "Notificações": avisos de fim de leilão, com o contato entre quem arrematou e quem vendeu.
final class NotificacaoController
{
    public function index(): void
    {
        $u = Auth::requireLogin('Entre na sua conta para ver suas notificações.');
        $id = (int) $u['id'];
        $model = new Notificacao();
        // Busca antes de marcar como lidas: as novas aparecem destacadas desta vez, e o número
        // do sino no menu (calculado ao desenhar a página) já zera.
        $avisos = $model->doUsuario($id);
        $model->marcarLidas($id);

        // Redes sociais de cada pessoa que aparece como contato (uma consulta por pessoa).
        $redes = [];
        $redeSocial = new RedeSocial();
        foreach ($avisos as $a)
            if ($a['id_contato'] && !isset($redes[$a['id_contato']]))
                $redes[$a['id_contato']] = $redeSocial->doUsuario((int) $a['id_contato']);

        View::render('notificacoes/index', [
            'title' => 'Notificações',
            'description' => 'Avisos dos seus leilões no Spice Gallery.',
            'menu' => 'notificacoes',
            'avisos' => $avisos,
            'redes' => $redes,
        ]);
    }
}
