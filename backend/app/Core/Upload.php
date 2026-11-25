<?php
namespace App\Core;

use RuntimeException;

// Recebe uma imagem e grava em frontend/uploads/<pasta> com nome aleatório:
// obras (imagem da obra) ou perfis (foto de perfil e papel de parede da sala).
final class Upload
{
    public const LIMITE = 10 * 1024 * 1024; // 10 MB, igual ao aviso do front
    private const TIPOS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    private const PASTAS = ['obras', 'perfis'];

    public static function enviado(?array $arquivo): bool
    {
        return $arquivo !== null && ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    // Devolve o caminho relativo à pasta frontend/ (vai para Obra.urlImagem, Usuario.fotoPerfil...).
    public static function imagem(array $arquivo, string $destino = 'obras'): string
    {
        if (!in_array($destino, self::PASTAS, true))
            throw new RuntimeException('Pasta de upload inválida.');
        $relativa = 'uploads/' . $destino;
        $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE)
            throw new RuntimeException('A imagem passa do limite de tamanho do servidor.');
        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name']))
            throw new RuntimeException('Não foi possível receber a imagem. Tente de novo.');
        if ($arquivo['size'] > self::LIMITE)
            throw new RuntimeException('A imagem passa de 10 MB. Exporte em tamanho menor.');

        // O tipo é descoberto pelo conteúdo do arquivo, nunca pelo nome ou pelo navegador.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        if (!isset(self::TIPOS[$mime]) || @getimagesize($arquivo['tmp_name']) === false)
            throw new RuntimeException('Use uma imagem PNG, JPG ou WEBP.');

        $pasta = self::pastaPublica() . '/' . $relativa;
        if (!is_dir($pasta) && !mkdir($pasta, 0775, true))
            throw new RuntimeException('Pasta de uploads indisponível.');

        $nome = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        if (!move_uploaded_file($arquivo['tmp_name'], $pasta . '/' . $nome))
            throw new RuntimeException('Não foi possível salvar a imagem.');
        return $relativa . '/' . $nome;
    }

    // Apaga só arquivos enviados (as imagens de exemplo em assets/ ficam).
    public static function remover(?string $caminho): void
    {
        if (!$caminho || !preg_match('#^uploads/(obras|perfis)/[a-f0-9]{32}\.(png|jpg|webp)$#', $caminho))
            return;
        $arquivo = self::pastaPublica() . '/' . $caminho;
        if (is_file($arquivo))
            unlink($arquivo);
    }

    // A pasta que o navegador acessa: Spice-Gallery-SA/frontend (ao lado de backend/).
    private static function pastaPublica(): string
    {
        return dirname(__DIR__, 3) . '/frontend';
    }
}
