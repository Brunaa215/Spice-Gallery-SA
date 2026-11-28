<?php
/**
 * Lista de artistas (página nova: no protótipo o menu "Artistas" abria direto uma sala).
 * Reaproveita os cartões .lote da página de leilões.
 * Variáveis: $artistas
 */
?>
  <main id="conteudo">
    <section class="leiloes-cab envolve" aria-labelledby="artistas-titulo">
      <p class="rotulo">Salas individuais</p>
      <h1 id="artistas-titulo">Os <span>artistas</span></h1>
      <p data-revelar>Cada artista tem uma sala só sua, com as obras penduradas na ordem em que foram publicadas.</p>
    </section>

    <section class="secao envolve" aria-label="Artistas" style="padding-top:clamp(30px,4vw,60px)">
      <?php if (!$artistas): ?>
        <p class="vazio">Nenhum artista publicou ainda. Que tal abrir a primeira sala?</p>
      <?php else: ?>
        <div class="lotes">
          <?php foreach ($artistas as $a): ?>
            <a class="lote" href="<?= e(url('artistas.show', ['id' => $a['id']])) ?>">
              <figure class="quadro <?= moldura($a['id']) ?>"><div class="quadro__passe"><img src="<?= e($a['capa']) ?>" alt="Obra mais recente de <?= e($a['nome']) ?>" loading="lazy"></div></figure>
              <span class="lote__num"><?= (int) $a['obras'] ?> <?= (int) $a['obras'] === 1 ? 'obra' : 'obras' ?></span>
              <h2><?= e($a['nome']) ?></h2>
              <p class="lote__autor"><?= $a['media'] !== null ? 'Média ' . nota($a['media']) . ' ★' : 'Ainda sem avaliações' ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
