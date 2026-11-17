<?php
/**
 * Lista de leilões (era leiloes.html).
 * Variáveis: $leiloes (só APROVADOS, com o estado calculado)
 */
use App\Models\Leilao;
use App\Models\Obra;
?>
  <main id="conteudo">
    <section class="leiloes-cab envolve" aria-labelledby="leiloes-titulo">
      <canvas class="poeira" aria-hidden="true"></canvas>
      <p class="rotulo">Sala noturna · as luzes baixam às 19h</p>
      <h1 id="leiloes-titulo">Leilão <span>noturno</span></h1>
      <p data-revelar>Só entram aqui obras exclusivas que o artista marcou como disponíveis e que a curadoria aprovou. Início e fim são fixos. O maior lance válido leva.</p>
    </section>

    <section class="secao envolve" aria-label="Lotes" style="padding-top:clamp(30px,4vw,60px)">
      <?php if (!$leiloes): ?>
        <p class="vazio">Nenhum leilão na sala por enquanto.</p>
      <?php else: ?>
        <div class="lotes">
          <?php foreach ($leiloes as $l): ?>
            <?php
              $estado = $l['estado'];
              $lances = (int) $l['lances'];
            ?>
            <a class="lote<?= $estado === 'ENCERRADO' ? ' lote--encerrado' : '' ?>" href="<?= e(url('leiloes.show', ['id' => $l['id']])) ?>" data-cursor="<?= $estado === 'AO_VIVO' ? 'Dar lance' : 'Ver lote' ?>">
              <?php if ($estado === 'AO_VIVO'): ?>
                <span class="estado"><span class="ao-vivo"></span> Ao vivo</span>
              <?php elseif ($estado === 'EM_BREVE'): ?>
                <span class="estado" style="color:var(--acafrao)">Em breve</span>
              <?php else: ?>
                <span class="estado">Encerrado</span>
              <?php endif; ?>
              <figure class="quadro <?= moldura($l['id_obra']) ?>"><div class="quadro__passe"><img src="<?= e($l['urlImagem']) ?>" alt="<?= e($l['titulo'] . ', de ' . $l['autor']) ?>" loading="lazy"></div></figure>
              <span class="lote__num"><?= numeroLote($l['id']) ?> · <?= numeroObra($l['id_obra']) ?></span>
              <h2><?= e($l['titulo']) ?></h2>
              <p class="lote__autor"><?= e($l['autor']) ?> · <?= e(Obra::categoria($l['categoria'])) ?></p>

              <?php if ($estado === 'AO_VIVO'): ?>
                <p class="legenda">Encerra em</p>
                <div class="contagem contagem--mini" data-fim="<?= dataIso($l['dataFim']) ?>"><?= dataHora($l['dataFim']) ?></div>
                <dl class="lote__pe">
                  <div><dt>Lance atual</dt><dd><?= brl(Leilao::atual($l)) ?></dd></div>
                  <div style="text-align:right"><dt>Lances</dt><dd><?= $lances ?></dd></div>
                </dl>
              <?php elseif ($estado === 'EM_BREVE'): ?>
                <p class="legenda">Abre em</p>
                <div class="contagem contagem--mini" data-inicio="<?= dataIso($l['dataInicio']) ?>" data-fim="<?= dataIso($l['dataFim']) ?>"><?= dataHora($l['dataInicio']) ?></div>
                <dl class="lote__pe">
                  <div><dt>Lance inicial</dt><dd><?= brl($l['valorInicial']) ?></dd></div>
                  <div style="text-align:right"><dt>Incremento</dt><dd><?= brl($l['incremento']) ?></dd></div>
                </dl>
              <?php else: ?>
                <p class="legenda">Encerrado em</p>
                <div class="contagem contagem--fim"><?= dataCurta($l['dataFim']) ?></div>
                <dl class="lote__pe">
                  <div><dt><?= $lances ? 'Arrematado por' : 'Sem lances' ?></dt><dd><?= $lances ? brl($l['maiorLance']) : '–' ?></dd></div>
                  <div style="text-align:right"><dt>Lances</dt><dd><?= $lances ?></dd></div>
                </dl>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="secao envolve" id="como-funciona" aria-labelledby="como-titulo" style="padding-top:0">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Regras do martelo</p>
          <h2 class="secao__titulo" id="como-titulo">Como funciona</h2>
        </div>
        <p class="secao__nota" data-revelar>O leilão encerra sozinho no horário marcado. O maior lance válido vence e o sistema abre uma pendência de pagamento entre comprador e artista.</p>
      </header>
      <div class="regras">
        <article class="regra">
          <span class="regra__num" aria-hidden="true">I</span>
          <h3>Curadoria</h3>
          <p>O artista marca a obra como disponível para leilão. A curadoria aprova antes de abrir.</p>
        </article>
        <article class="regra">
          <span class="regra__num" aria-hidden="true">II</span>
          <h3>Lances</h3>
          <p>Cada lance supera o atual somando o incremento mínimo. Quem criou a obra não pode dar lance nela.</p>
        </article>
        <article class="regra">
          <span class="regra__num" aria-hidden="true">III</span>
          <h3>Martelo</h3>
          <p>No horário final o leilão fecha. Vence o maior lance e comprador e artista combinam o pagamento.</p>
        </article>
      </div>
    </section>
  </main>
