<?php
/**
 * Um leilão com contagem regressiva e lances (era leilao.html).
 * Variáveis: $leilao, $lances, $ehAutor
 */
use App\Core\Csrf;
use App\Models\Leilao;
use App\Models\Obra;

$estado = $leilao['estado'];
$aoVivo = $estado === 'AO_VIVO';
$atual = Leilao::atual($leilao);
$minimo = Leilao::minimo($leilao);
$qtd = (int) $leilao['lances'];
?>
  <main id="conteudo">
    <div class="envolve">
      <nav class="trilha" aria-label="Você está em">
        <ol>
          <li><a href="<?= e(url('leiloes')) ?>">Leilões</a></li>
          <li aria-current="page"><?= numeroLote($leilao['id']) ?></li>
        </ol>
      </nav>

      <div class="exposicao">
        <div class="parede-luz" data-holofote>
          <figure class="quadro quadro--ouro quadro--pendurado">
            <div class="quadro__passe"><img src="<?= e($leilao['urlImagem']) ?>" alt="<?= e($leilao['titulo'] . ', de ' . $leilao['autor']) ?>"></div>
          </figure>
        </div>

        <aside class="painel-lance" data-cascata>
          <p class="rotulo"><?= numeroLote($leilao['id']) ?> · <?= e(Leilao::estado($leilao)) ?></p>
          <h1 class="ficha__titulo"><a href="<?= e(url('obras.show', ['id' => $leilao['id_obra']])) ?>" style="color:inherit;text-decoration:none"><?= e($leilao['titulo']) ?></a></h1>
          <a class="ficha__artista" href="<?= e(url('artistas.show', ['id' => $leilao['idAutor']])) ?>">
            <span class="avatar" style="--cor:var(--<?= corAvatar($leilao['idAutor']) ?>)"><?= e(iniciais($leilao['autor'])) ?></span>
            <span><strong><?= e($leilao['autor']) ?></strong><small><?= e(Obra::categoria($leilao['categoria'])) ?></small></span>
          </a>

          <?php if ($estado === 'EM_BREVE' || $estado === Leilao::AGUARDANDO): ?>
            <p class="legenda">Abre em</p>
            <div class="contagem" data-inicio="<?= dataIso($leilao['dataInicio']) ?>" data-fim="<?= dataIso($leilao['dataFim']) ?>" style="margin:12px 0 30px"><?= dataHora($leilao['dataInicio']) ?></div>
          <?php elseif ($aoVivo): ?>
            <p class="legenda">Encerra em</p>
            <div class="contagem" data-fim="<?= dataIso($leilao['dataFim']) ?>" style="margin:12px 0 30px"><?= dataHora($leilao['dataFim']) ?></div>
          <?php else: ?>
            <p class="legenda">Encerrado em</p>
            <div class="contagem contagem--fim" style="margin:12px 0 30px"><?= dataCurta($leilao['dataFim']) ?></div>
          <?php endif; ?>

          <p class="legenda"><?= $qtd ? ($estado === 'ENCERRADO' ? 'Arrematado por' : 'Lance atual') : 'Lance inicial' ?></p>
          <p class="painel-lance__valor" data-lance-atual data-contar="<?= (int) $atual ?>" data-moeda><?= brl($atual) ?></p>
          <p class="painel-lance__sub"><span data-qtd-lances><?= $qtd ?></span> <?= $qtd === 1 ? 'lance' : 'lances' ?> · incremento mínimo <?= brl($leilao['incremento']) ?> · inicial <?= brl($leilao['valorInicial']) ?></p>

          <?php if ($aoVivo && $current && !$ehAutor): ?>
            <!-- data-lance-minimo: o mesmo mínimo que o servidor vai exigir (RN-08) -->
            <form class="cartao-form" method="post" action="<?= e(url('lances.save')) ?>" data-lance data-atual="<?= (int) $atual ?>" data-lance-minimo="<?= (int) ceil($minimo) ?>" data-incremento="<?= (int) $leilao['incremento'] ?>" data-fim="<?= dataIso($leilao['dataFim']) ?>" style="margin-top:26px">
              <?= Csrf::field() ?>
              <input type="hidden" name="id_leilao" value="<?= (int) $leilao['id'] ?>">
              <div class="campo campo-valor" style="margin-bottom:6px">
                <label for="valor">Seu lance (mínimo <span data-minimo><?= brl($minimo) ?></span>)</label>
                <span aria-hidden="true">R$</span>
                <input id="valor" name="valor" type="number" inputmode="numeric" step="1" min="<?= (int) ceil($minimo) ?>" value="<?= (int) ceil($minimo) ?>" required>
              </div>
              <div class="rapidos" role="group" aria-label="Lances rápidos">
                <button type="button" data-soma="<?= (int) $leilao['incremento'] ?>">+ <?= brl($leilao['incremento']) ?></button>
                <button type="button" data-soma="<?= (int) $leilao['incremento'] * 2 ?>">+ <?= brl($leilao['incremento'] * 2) ?></button>
                <button type="button" data-soma="<?= (int) $leilao['incremento'] * 5 ?>">+ <?= brl($leilao['incremento'] * 5) ?></button>
              </div>
              <p class="campo__erro" role="alert"></p>
              <button class="botao botao--acafrao botao--largo" type="submit">Dar lance</button>
            </form>
          <?php elseif ($aoVivo && !$current): ?>
            <a class="botao botao--acafrao botao--largo" href="<?= e(url('login')) ?>" style="margin-top:26px">Entrar para dar lances</a>
          <?php elseif ($estado === Leilao::AGUARDANDO): ?>
            <p class="aviso" style="margin-top:26px">Este leilão ainda está com a curadoria. Só você e a curadoria conseguem vê-lo.</p>
          <?php elseif ($estado === Leilao::REPROVADO): ?>
            <p class="aviso" style="margin-top:26px">A curadoria não aprovou este leilão. Você pode marcar a obra para leilão de novo ao editá-la.</p>
          <?php endif; ?>
          <p class="campo__ajuda" style="margin-top:14px"><?= $ehAutor ? 'Esta obra é sua: artistas não dão lances nas próprias obras.' : 'Artistas não dão lances nas próprias obras. Lances não podem ser cancelados.' ?></p>
        </aside>
      </div>
    </div>

    <section class="secao envolve" aria-labelledby="historico-titulo" style="padding-top:0">
      <div class="avaliacoes">
        <div>
          <p class="rotulo">Histórico</p>
          <h2 class="secao__titulo" id="historico-titulo" style="margin-bottom:28px">Quem levantou a mão</h2>
          <ol class="regras-lista" data-revelar>
            <li>Início e fim do leilão são fixos.</li>
            <li>Cada lance supera o atual pelo incremento mínimo.</li>
            <li>No horário final, o maior lance válido vence e abre-se a pendência de pagamento.</li>
          </ol>
        </div>
        <?php if ($lances): ?>
          <ol class="historico" aria-live="polite" data-cascata>
            <?php foreach ($lances as $lance): ?>
              <?php $meu = $current && (int) $lance['id_usuario'] === (int) $current['id']; ?>
              <li><span class="avatar" style="--cor:var(--<?= corAvatar($lance['id_usuario']) ?>)"><?= e(iniciais($lance['nome'])) ?></span><span><strong><?= $meu ? 'Você' : e($lance['nome']) ?></strong><time datetime="<?= dataIso($lance['dataHora']) ?>"><?= quando($lance['dataHora']) ?></time></span><b><?= brl($lance['valor']) ?></b></li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <p class="vazio">Nenhum lance ainda.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>
