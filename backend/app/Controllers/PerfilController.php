<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Upload;
use App\Core\View;
use App\Models\Obra;
use App\Models\RedeSocial;
use App\Models\Usuario;
use RuntimeException;
use Throwable;

// Perfil do artista: nome, apelido, especialidade, estilos, foto com moldura, papel de parede e redes.
final class PerfilController
{
    public function form(): void
    {
        $u = Auth::requireLogin('Entre na sua conta para editar o perfil.');
        $redes = (new RedeSocial())->doUsuario((int) $u['id']);
        // Depois de um erro de validação, o formulário volta com o que foi digitado (menos os arquivos).
        $old = takeOld();
        $campos = ['nome', 'apelido', 'especialidade', 'estilos', 'molduraFoto'];
        $perfil = array_merge($u, array_intersect_key($old, array_flip($campos)));
        foreach (array_keys(RedeSocial::REDES) as $rede)
            $redes[$rede] = $old['rede'][$rede] ?? ($redes[$rede] ?? '');

        View::render('perfil/form', [
            'title' => 'Meu perfil',
            'description' => 'Edite o seu perfil de artista no Spice Gallery.',
            'menu' => 'perfil',
            'perfil' => $perfil,
            'redes' => $redes,
            'especialidades' => array_values(Obra::CATEGORIAS),
        ]);
    }

    public function save(): void
    {
        // POST maior que o post_max_size do PHP chega vazio (sem token e sem arquivo).
        if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('danger', 'O envio passou do limite do servidor. Use imagens menores.');
            go('perfil');
        }
        $u = Auth::requireLogin();
        Csrf::verify();
        $id = (int) $u['id'];

        $nome = trim(preg_replace('/\s+/', ' ', (string) ($_POST['nome'] ?? '')));
        $apelido = trim((string) ($_POST['apelido'] ?? ''));
        $especialidade = trim((string) ($_POST['especialidade'] ?? ''));
        $moldura = (string) ($_POST['molduraFoto'] ?? '');
        $foto = $_FILES['foto'] ?? null;
        $wallpaper = $_FILES['wallpaper'] ?? null;

        $erros = [];
        if ($nome === '' || mb_strlen($nome) > 255)
            $erros[] = 'Informe seu nome artístico (até 255 letras).';
        if (mb_strlen($apelido) > 60)
            $erros[] = 'O apelido pode ter no máximo 60 letras.';
        if (mb_strlen($especialidade) > 80)
            $erros[] = 'A especialidade pode ter no máximo 80 letras.';
        if (!isset(Usuario::MOLDURAS[$moldura]))
            $erros[] = 'Escolha uma das molduras.';
        try {
            $estilos = Usuario::normalizarEstilos((string) ($_POST['estilos'] ?? ''));
        } catch (RuntimeException $e) {
            $erros[] = $e->getMessage();
        }
        $redes = [];
        foreach (array_keys(RedeSocial::REDES) as $rede) {
            try {
                $link = RedeSocial::normalizar($rede, (string) ($_POST['rede'][$rede] ?? ''));
                if ($link !== null)
                    $redes[$rede] = $link;
            } catch (RuntimeException $e) {
                $erros[] = $e->getMessage();
            }
        }
        if ($erros)
            $this->voltar(implode(' ', $erros));

        // Imagens: as novas só valem se o resto der certo; as antigas só saem depois de gravar.
        $novas = [];
        try {
            if (Upload::enviado($foto))
                $novas['fotoPerfil'] = Upload::imagem($foto, 'perfis');
            if (Upload::enviado($wallpaper))
                $novas['wallpaper'] = Upload::imagem($wallpaper, 'perfis');
        } catch (RuntimeException $e) {
            array_map([Upload::class, 'remover'], $novas);
            $this->voltar($e->getMessage());
        }
        $dados = [
            'nome' => $nome,
            'apelido' => $apelido,
            'especialidade' => $especialidade,
            'estilos' => $estilos,
            'molduraFoto' => $moldura,
            // nova imagem > "remover" marcado (vira null) > a que já estava
            'fotoPerfil' => $novas['fotoPerfil'] ?? (($_POST['removerFoto'] ?? '') === '1' ? null : $u['fotoPerfil']),
            'wallpaper' => $novas['wallpaper'] ?? (($_POST['removerWallpaper'] ?? '') === '1' ? null : $u['wallpaper']),
        ];

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Usuario::atualizarPerfil($pdo, $id, $dados);
            RedeSocial::salvar($pdo, $id, $redes);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            array_map([Upload::class, 'remover'], $novas);
            throw $e;
        }
        // Imagens que saíram do perfil (trocadas ou removidas) são apagadas do disco.
        foreach (['fotoPerfil', 'wallpaper'] as $campo)
            if ($u[$campo] && $u[$campo] !== $dados[$campo])
                Upload::remover($u[$campo]);

        flash('success', 'Perfil atualizado.');
        go('artistas.show', ['id' => $id]);
    }

    private function voltar(string $mensagem): never
    {
        $dados = $_POST;
        unset($dados['csrf']);
        remember($dados);
        flash('danger', $mensagem);
        go('perfil');
    }
}
