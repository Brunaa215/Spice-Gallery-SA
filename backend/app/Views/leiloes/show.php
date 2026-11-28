<?php
/**
 * Um leilão com contagem regressiva e lances (era leilao.html).
 * O painel e o histórico são partials: o app.js pergunta à rota leiloes.estado a cada poucos
 * segundos e troca os dois quando algo muda (lance novo, abertura, prorrogação, martelo).
 * Variáveis: $leilao, $painel e $historico (HTML já montado pelos partials leilao_painel e
 * leilao_historico), $versao (hash dos dois), $souLider
 */
use App\Models\Leilao;

$prorrogacao = duracao(intdiv(Leilao::PRORROGACAO_SEGUNDOS, 60));
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
        <div class="parede-luz">
          <figure class="quadro quadro--ouro quadro--pendurado">
            <div class="quadro__passe"><img src="<?= e($leilao['urlImagem']) ?>" alt="<?= e($leilao['titulo'] . ', de ' . $leilao['autor']) ?>"></div>
          </figure>
        </div>

        <!-- data-leilao-vivo: endereço que o app.js consulta; data-versao muda quando o painel ou o histórico mudam -->
        <aside class="painel-lance" data-cascata data-leilao-vivo="<?= e(url('leiloes.estado', ['id' => $leilao['id']])) ?>" data-versao="<?= e($versao) ?>" data-estado="<?= e($leilao['estado']) ?>" data-lider="<?= $souLider ? '1' : '0' ?>">
<?= $painel ?>
        </aside>
      </div>
    </div>

    <section class="secao envolve" aria-labelledby="historico-titulo" style="padding-top:0">
      <div class="avaliacoes">
        <div>
          <p class="rotulo">Histórico</p>
          <h2 class="secao__titulo" id="historico-titulo" style="margin-bottom:28px">Quem levantou a mão</h2>
          <ol class="regras-lista" data-revelar>
            <li>O leilão dura o tempo que o artista escolheu: de minutos a dias. Esta página se atualiza sozinha.</li>
            <li>Cada lance supera o atual pelo incremento mínimo e precisa caber nos seus créditos.</li>
            <li>Enquanto você está na frente, o valor do lance fica reservado na sua carteira.</li>
            <?php if (Leilao::PRORROGACAO_SEGUNDOS > 0): ?>
              <li>Lance no último minuto prorroga o leilão por mais <?= $prorrogacao ?>: dou-lhe uma, dou-lhe duas...</li>
            <?php endif; ?>
            <li>No martelo, o maior lance vence: o valor sai da carteira de quem venceu e entra na do artista.</li>
          </ol>
        </div>
        <div data-historico>
<?= $historico ?>
        </div>
      </div>
    </section>
  </main>
