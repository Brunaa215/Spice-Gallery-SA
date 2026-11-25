<?php
/**
 * Histórico de lances do leilão (maior primeiro). A página o desenha e o app.js o troca ao vivo.
 * Variáveis: $lances, $current
 */
?>
<?php if ($lances): ?>
          <ol class="historico" aria-live="polite" data-cascata>
            <?php foreach ($lances as $i => $lance): ?>
              <?php $meu = $current && (int) $lance['id_usuario'] === (int) $current['id']; ?>
              <li<?= $i === 0 ? ' class="historico__lider"' : '' ?>><?= avatar($lance['id_usuario'], $lance['nome'], $lance['fotoPerfil'] ?? null) ?><span><strong><?= $meu ? 'Você' : e($lance['nome']) ?></strong><time datetime="<?= dataIso($lance['dataHora']) ?>"><?= quando($lance['dataHora']) ?></time></span><b><?= brl($lance['valor']) ?></b></li>
            <?php endforeach; ?>
          </ol>
<?php else: ?>
          <p class="vazio">Nenhum lance ainda.</p>
<?php endif; ?>
