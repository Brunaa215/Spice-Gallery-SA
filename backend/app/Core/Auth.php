<?php
namespace App\Core;

use App\Models\Usuario;

final class Auth
{
    // Guardado por requisição: várias views pedem o usuário, o banco é consultado uma vez.
    private static ?array $cache = null;
    private static bool $carregado = false;

    public static function user(): ?array
    {
        if (self::$carregado)
            return self::$cache;
        self::$carregado = true;
        $id = positiveId($_SESSION['user_id'] ?? null);
        if (!$id)
            return null;
        // Verifica perfil/ativo no banco a cada request: desativação tem efeito imediato.
        $u = (new Usuario())->find($id);
        if (!$u || !(int) $u['ativo']) {
            unset($_SESSION['user_id']);
            return null;
        }
        return self::$cache = $u;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['perfil'] ?? '') === Usuario::ADMIN;
    }

    public static function requireLogin(string $mensagem = 'Entre na sua conta para continuar.'): array
    {
        $u = self::user();
        if (!$u) {
            flash('warning', $mensagem);
            go('login');
        }
        return $u;
    }

    public static function requireAdmin(): array
    {
        $u = self::requireLogin();
        if ($u['perfil'] !== Usuario::ADMIN) {
            http_response_code(403);
            exit('Acesso negado: requer curadoria.');
        }
        return $u;
    }

    public static function login(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        self::$carregado = false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$cache = null;
        self::$carregado = true;
    }
}
