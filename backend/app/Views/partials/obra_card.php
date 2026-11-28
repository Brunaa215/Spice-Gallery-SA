<?php
/**
 * Cartão de uma obra na parede (<article class="obra">). Usado na home, na sala do artista e na obra.
 * Variáveis: $obra (linha de Obra::SELECT), $mostrarAutor (bool, padrão true),
 *            $selo (opcional: frase embaixo da placa, ex. "Arrematada por R$ 340" na coleção)
 */
$mostrarAutor = $mostrarAutor ?? true;
$selo = $selo ?? null;
$titulo = $obra['titulo'];
$total = (int) $obra['avaliacoes'];
?>
<article class="obra" data-categoria="<?= e($obra['categoria']) ?>">
  <a class="obra__link" href="<?= e(url('obras.show', ['id' => $obra['id']])) ?>">
    <figure class="quadro <?= moldura($obra['id']) ?>"><div class="quadro__passe"><img src="<?= e($obra['urlImagem']) ?>" alt="<?= e($titulo . ', de ' . $obra['autor']) ?>" loading="lazy"></div></figure>
    <div class="placa">
      <span class="placa__num"><?= numeroObra($obra['id']) ?></span>
      <h3 class="placa__titulo"><?= e($titulo) ?></h3>
      <?php if ($mostrarAutor): ?><p class="placa__autor"><?= e($obra['autor']) ?></p><?php endif; ?>
      <p class="placa__meta"><?= e(\App\Models\Obra::categoria($obra['categoria'])) ?> · <?= date('Y', strtotime($obra['criadoEm'])) ?></p>
      <?php if ($total > 0): ?>
        <p class="placa__nota"><span class="estrelas" style="--nota:<?= e($obra['media']) ?>" aria-hidden="true"></span><span><?= nota($obra['media']) ?></span><small>(<?= $total ?>)</small><span class="sr-only">Nota <?= nota($obra['media']) ?> de 5 em <?= $total ?> avaliações</span></p>
      <?php else: ?>
        <p class="placa__nota"><small>Ainda sem avaliações</small></p>
      <?php endif; ?>
      <?php if ($selo): ?><p class="placa__selo"><?= e($selo) ?></p><?php endif; ?>
    </div>
  </a>
</article>
