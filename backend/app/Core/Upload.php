<?php
namespace App\Core;

use RuntimeException;

// Recebe a imagem da obra e grava em public/uploads/obras com nome aleatório.
final class Upload
{
    public const LIMITE = 10 * 1024 * 1024; // 10 MB, igual ao aviso do front
    private const TIPOS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    private const PASTA = 'uploads/obras';

    public static function enviado(?array $arquivo): bool
    {
        return $arquivo !== null && ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    // Devolve o caminho relativo à pasta public/ (vai para Obra.urlImagem).
    public static function imagem(array $arquivo): string
    {
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

        $pasta = self::pastaPublica() . '/' . self::PASTA;
        if (!is_dir($pasta) && !mkdir($pasta, 0775, true))
            throw new RuntimeException('Pasta de uploads indisponível.');

        $nome = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        if (!move_uploaded_file($arquivo['tmp_name'], $pasta . '/' . $nome))
            throw new RuntimeException('Não foi possível salvar a imagem.');
        return self::PASTA . '/' . $nome;
    }

    // Apaga só arquivos enviados (as imagens de exemplo em assets/ ficam).
    public static function remover(?string $caminho): void
    {
        if (!$caminho || !preg_match('#^' . self::PASTA . '/[a-f0-9]{32}\.(png|jpg|webp)$#', $caminho))
            return;
        $arquivo = self::pastaPublica() . '/' . $caminho;
        if (is_file($arquivo))
            unlink($arquivo);
    }

    private static function pastaPublica(): string
    {
        return dirname(__DIR__, 2) . '/public';
    }
}
