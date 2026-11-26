<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Movimentacao;
use App\Models\Usuario;
use PDOException;

final class AuthController
{
    public function form(): void
    {
        if (Auth::user())
            go('home');
        View::render('auth/login', ['title' => 'Entrar', 'menu' => 'entrar', 'old' => takeOld(),
            'description' => 'Entre ou crie sua conta de artista no Spice Gallery.']);
    }

    // Login: busca o Usuario por e-mail e confere password_verify com senhaHash.
    public function login(): void
    {
        Csrf::verify();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');
        $u = filter_var($email, FILTER_VALIDATE_EMAIL) ? (new Usuario())->byEmail($email) : null;
        if (!$u || !(int) $u['ativo'] || !password_verify($senha, $u['senhaHash'])) {
            remember(['login_email' => $email]);
            flash('danger', 'E-mail ou senha incorretos.');
            go('login');
        }
        Auth::login((int) $u['id']);
        flash('success', 'Bem-vindo(a) de volta, ' . explode(' ', $u['nome'])[0] . '!');
        go('home');
    }

    // Cadastro (RN-01, RN-02): conta de artista, senha com hash e aceite dos termos.
    public function register(): void
    {
        Csrf::verify();
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');
        $aceite = ($_POST['aceiteTermos'] ?? '') === '1';

        $erro = match (true) {
            $nome === '' || mb_strlen($nome) > 255 => 'Informe seu nome artístico.',
            !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255 => 'Informe um e-mail válido.',
            strlen($senha) < 8 => 'A senha precisa de pelo menos 8 caracteres.',
            !$aceite => 'Para criar a conta é preciso aceitar os termos.',
            default => null,
        };
        $model = new Usuario();
        if (!$erro && $model->byEmail($email))
            $erro = 'Já existe uma conta com esse e-mail.';
        if ($erro) {
            remember(['nome' => $nome, 'email' => $email]);
            flash('danger', $erro);
            go('login', [], 'cadastro');
        }

        try {
            $id = $model->create($nome, $email, $senha);
        } catch (PDOException $e) {
            // Dois cadastros simultâneos com o mesmo e-mail: o UNIQUE do banco barra o segundo.
            remember(['nome' => $nome, 'email' => $email]);
            flash('danger', 'Não foi possível criar a conta. O e-mail talvez já esteja em uso.');
            go('login', [], 'cadastro');
        }
        Auth::login($id);
        flash('success', 'Conta criada! Sua sala já está aberta e você ganhou ' . brl(Movimentacao::BONUS_BOAS_VINDAS) . ' em créditos para dar lances.');
        go('artistas.show', ['id' => $id]);
    }

    public function logout(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        Auth::logout();
        go('home');
    }

    public function passwordForm(): void
    {
        Auth::requireLogin();
        View::render('auth/senha', ['title' => 'Minha senha', 'menu' => 'perfil']);
    }

    public function changePassword(): void
    {
        $u = Auth::requireLogin();
        Csrf::verify();
        $atual = (string) ($_POST['atual'] ?? '');
        $nova = (string) ($_POST['nova'] ?? '');
        if (!password_verify($atual, $u['senhaHash']) || strlen($nova) < 8) {
            flash('danger', 'Senha atual incorreta ou nova senha com menos de 8 caracteres.');
            go('password');
        }
        (new Usuario())->changePassword((int) $u['id'], $nova);
        flash('success', 'Senha alterada.');
        go('perfil');
    }
}
