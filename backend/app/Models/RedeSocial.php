<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

// Links do perfil do artista: uma linha por rede (tabela RedeSocial).
final class RedeSocial
{
    // rede => [nome mostrado, prefixo para quem digita só "@usuario", domínios aceitos]
    // "site" aceita qualquer endereço http(s).
    public const REDES = [
        'instagram'  => ['Instagram', 'https://instagram.com/', ['instagram.com']],
        'tiktok'     => ['TikTok', 'https://tiktok.com/@', ['tiktok.com']],
        'x'          => ['X (Twitter)', 'https://x.com/', ['x.com', 'twitter.com']],
        'youtube'    => ['YouTube', 'https://youtube.com/@', ['youtube.com', 'youtu.be']],
        'behance'    => ['Behance', 'https://behance.net/', ['behance.net']],
        'artstation' => ['ArtStation', 'https://artstation.com/', ['artstation.com']],
        'pinterest'  => ['Pinterest', 'https://pinterest.com/', ['pinterest.com']],
        'site'       => ['Site pessoal', null, null],
    ];

    // [rede => url] de um usuário, na ordem de REDES.
    public function doUsuario(int $idUsuario): array
    {
        $s = Database::connection()->prepare('SELECT rede, url FROM RedeSocial WHERE id_usuario = ?');
        $s->execute([$idUsuario]);
        $links = $s->fetchAll(PDO::FETCH_KEY_PAIR);
        $ordenados = [];
        foreach (array_keys(self::REDES) as $rede)
            if (isset($links[$rede]))
                $ordenados[$rede] = $links[$rede];
        return $ordenados;
    }

    // Troca todos os links do usuário pelos novos ([rede => url], já normalizados). Chame dentro de uma transação.
    public static function salvar(PDO $pdo, int $idUsuario, array $links): void
    {
        $pdo->prepare('DELETE FROM RedeSocial WHERE id_usuario = ?')->execute([$idUsuario]);
        $s = $pdo->prepare('INSERT INTO RedeSocial (id_usuario, rede, url) VALUES (?, ?, ?)');
        foreach ($links as $rede => $url)
            $s->execute([$idUsuario, $rede, $url]);
    }

    // O que a pessoa digitou -> link completo, ou null se ficou vazio. Aceita "@usuario" (ou só
    // "usuario") nas redes conhecidas, ou o endereço inteiro, desde que seja daquela rede.
    public static function normalizar(string $rede, string $valor): ?string
    {
        [$nome, $prefixo, $dominios] = self::REDES[$rede] ?? throw new RuntimeException('Rede desconhecida.');
        $valor = trim($valor);
        if ($valor === '')
            return null;
        if ($prefixo && preg_match('/^@?([A-Za-z0-9._-]{1,60})$/', $valor, $m))
            return $prefixo . $m[1];
        if (!preg_match('#^https?://#i', $valor))
            $valor = 'https://' . $valor;
        $host = strtolower((string) parse_url($valor, PHP_URL_HOST));
        if (!filter_var($valor, FILTER_VALIDATE_URL) || $host === '' || mb_strlen($valor) > 255)
            throw new RuntimeException("O link de {$nome} não parece um endereço válido.");
        if ($dominios) {
            $daRede = false;
            foreach ($dominios as $d)
                $daRede = $daRede || $host === $d || str_ends_with($host, '.' . $d);
            if (!$daRede)
                throw new RuntimeException("O link de {$nome} precisa ser de " . implode(' ou ', $dominios) . '.');
        }
        return $valor;
    }
}
