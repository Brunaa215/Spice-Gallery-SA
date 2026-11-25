<?php
/**
 * Botões da curadoria para um leilão que espera aprovação (RN-07): aprovar (e começar) ou reprovar.
 * Aparece na página do leilão e na da obra, para a curadoria decidir ali mesmo, sem ir até a fila.
 * Variáveis: $leilao, $voltar ('leilao' | 'obra': para onde a curadoria volta depois de decidir)
 */
use App\Core\Csrf;

$abre = (int) $leilao['abreNaAprovacao'];
$jaPodeAbrir = $abre || strtotime($leilao['dataInicio']) <= time();
$vencido = !$abre && strtotime($leilao['dataFim']) <= time();
$campos = fn(string $decisao): string => Csrf::field()
    . '<input type="hidden" name="id" value="' . (int) $leilao['id'] . '">'
    . '<input type="hidden" name="decisao" value="' . $decisao . '">'
    . '<input type="hidden" name="voltar" value="' . e($voltar) . '">';
?>
<div class="decisao-curadoria">
  <p class="legenda">Curadoria: este leilão espera a sua decisão</p>
  <?php if ($vencido): ?>
    <p class="aviso">A data marcada deste leilão já passou. Reprove para o artista marcar de novo.</p>
  <?php else: ?>
    <form method="post" action="<?= e(url('curadoria.leilao')) ?>">
      <?= $campos('aprovar') ?>
      <button class="botao botao--acafrao botao--largo" type="submit"><?= $jaPodeAbrir ? 'Aprovar e começar agora' : 'Aprovar (abre ' . e(dataHora($leilao['dataInicio'])) . ')' ?></button>
    </form>
  <?php endif; ?>
  <form method="post" action="<?= e(url('curadoria.leilao')) ?>" data-confirmar="Reprovar este leilão? O artista poderá marcar a obra de novo.">
    <?= $campos('reprovar') ?>
    <button class="texto-botao" type="submit">Reprovar leilão</button>
  </form>
</div>
