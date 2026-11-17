<?php
namespace App\Core;

final class View
{
    private const BASE = __DIR__ . '/../Views/';

    // Página completa: layout/topo + template + layout/rodape.
    public static function render(string $template, array $data = []): void
    {
        // O caminho é escolhido apenas pelos controllers, não pelo parâmetro da URL.
        $file = self::BASE . $template . '.php';
        if (!is_file($file))
            throw new \RuntimeException('Template ausente: ' . $template);
        // Usuário logado, disponível em todas as views.
        $current = Auth::user();
        $isAdmin = Auth::isAdmin();
        $flash = takeFlash();
        extract($data, EXTR_SKIP);
        require self::BASE . 'layout/topo.php';
        require $file;
        require self::BASE . 'layout/rodape.php';
    }

    // Pedaço de HTML repetido (ex.: o cartão de uma obra), com variáveis só dele.
    public static function partial(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require self::BASE . 'partials/' . $template . '.php';
    }

    // Página 404 com o cabeçalho e o rodapé do site.
    public static function notFound(string $mensagem = 'Esta parede está vazia.'): never
    {
        http_response_code(404);
        self::render('erros/404', ['title' => 'Não encontrado', 'mensagem' => $mensagem]);
        exit;
    }
}
