<?php
/**
 * Retrato do artista: a foto de perfil pendurada na moldura que ele escolheu (as mesmas das obras).
 * Sem foto, o passe-partout mostra as iniciais no camafeu colorido.
 * Variáveis: $pessoa (id, nome, fotoPerfil, molduraFoto), $classe (opcional, ex. "retrato--mini"),
 *            $decorativa (opcional: true nas miniaturas de escolha de moldura, sem texto alternativo)
 */
use App\Models\Usuario;

$foto = imagemLocal($pessoa['fotoPerfil'] ?? null);
$decorativa = $decorativa ?? false;
?>
<figure class="quadro retrato <?= e(Usuario::classeMoldura($pessoa['molduraFoto'] ?? null)) ?> <?= e($classe ?? '') ?>" data-retrato<?= $decorativa ? ' aria-hidden="true"' : '' ?>>
  <div class="quadro__passe">
    <?php if ($foto): ?>
      <img src="<?= e($foto) ?>" alt="<?= $decorativa ? '' : 'Foto de ' . e($pessoa['nome']) ?>">
    <?php else: ?>
      <span class="retrato__iniciais" style="--cor:var(--<?= corAvatar($pessoa['id']) ?>)"<?= $decorativa ? '' : ' role="img" aria-label="Sem foto: iniciais de ' . e($pessoa['nome']) . '"' ?>><?= e(iniciais($pessoa['nome'])) ?></span>
    <?php endif; ?>
  </div>
</figure>
