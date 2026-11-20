<?php
/**
 * Carteira de créditos fictícios (nova; não existia no protótipo).
 * Variáveis: $resumo (saldo, reservado, disponivel), $reservas, $arremates, $extrato, $voltarLeilao (?int)
 */
use App\Core\Csrf;
use App\Models\Movimentacao;
?>
  <main id="conteudo">
    <section class="leiloes-cab envolve" aria-labelledby="carteira-titulo">
      <p class="rotulo">Créditos fictícios</p>
      <h1 id="carteira-titulo">Car<span>teira</span></h1>
      <p>Os créditos servem para dar lances e arrematar obras. É dinheiro de mentira: nada é cobrado e nada sai do site.</p>
    </section>

    <section class="secao envolve" aria-label="Saldo e compra de créditos" style="padding-top:0">
      <div class="carteira">
        <div>
          <dl class="cartaz__numeros">
            <div><dt>Saldo</dt><dd><?= brl($resumo['saldo']) ?></dd></div>
            <div><dt>Em lances</dt><dd><?= brl($resumo['reservado']) ?></dd></div>
            <div><dt>Disponível</dt><dd><?= brl($resumo['disponivel']) ?></dd></div>
          </dl>
          <p class="secao__nota">Enquanto o seu lance é o maior de um leilão, o valor fica reservado. Se alguém cobrir, ele volta a ficar disponível. Quando o leilão fecha, o valor sai da sua carteira e vai para o artista. Quem vende uma obra recebe o valor aqui.</p>
        </div>

        <form class="cartao-form" method="post" action="<?= e(url('carteira.comprar')) ?>">
          <?= Csrf::field() ?>
          <?php if ($voltarLeilao): ?>
            <input type="hidden" name="voltar_leilao" value="<?= (int) $voltarLeilao ?>">
          <?php endif; ?>
          <p class="legenda">Comprar créditos</p>
          <div class="pacotes" role="group" aria-label="Pacotes de créditos">
            <?php foreach (Movimentacao::PACOTES as $pacote): ?>
              <button class="botao botao--acafrao botao--sem-seta" type="submit" name="valor" value="<?= (int) $pacote ?>">+ <?= brl($pacote) ?></button>
            <?php endforeach; ?>
          </div>
          <p class="campo__ajuda">Compra simulada: o valor entra na hora, sem pagamento.<?= $voltarLeilao ? ' Depois da compra você volta para o leilão.' : '' ?></p>
        </form>
      </div>
    </section>

    <?php if ($reservas): ?>
    <section class="secao envolve" aria-labelledby="reservas-titulo" style="padding-top:0">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Reservado</p>
          <h2 class="secao__titulo" id="reservas-titulo">Você está na frente</h2>
        </div>
      </header>
      <div class="tabela-rolagem">
        <table class="tabela">
          <thead><tr><th>Lote</th><th>Obra</th><th>Seu lance</th><th>Encerra</th></tr></thead>
          <tbody>
            <?php foreach ($reservas as $r): ?>
              <tr>
                <td><a href="<?= e(url('leiloes.show', ['id' => $r['id_leilao']])) ?>"><?= numeroLote($r['id_leilao']) ?></a></td>
                <td><a href="<?= e(url('obras.show', ['id' => $r['id_obra']])) ?>"><?= e($r['titulo']) ?></a></td>
                <td><?= brl($r['valor']) ?></td>
                <td><?= dataHora($r['dataFim']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($arremates): ?>
    <section class="secao envolve" aria-labelledby="arremates-titulo" style="padding-top:0">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Sua coleção</p>
          <h2 class="secao__titulo" id="arremates-titulo">Obras arrematadas</h2>
        </div>
      </header>
      <div class="tabela-rolagem">
        <table class="tabela">
          <thead><tr><th>Obra</th><th>Artista</th><th>Valor pago</th><th>Arrematada em</th></tr></thead>
          <tbody>
            <?php foreach ($arremates as $a): ?>
              <tr>
                <td><a href="<?= e(url('obras.show', ['id' => $a['id_obra']])) ?>"><?= e($a['titulo']) ?></a></td>
                <td><a href="<?= e(url('artistas.show', ['id' => $a['idAutor']])) ?>"><?= e($a['autor']) ?></a></td>
                <td><?= brl($a['maiorLance']) ?></td>
                <td><?= dataCurta($a['dataFim']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <section class="secao envolve" aria-labelledby="extrato-titulo" style="padding-top:0">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Movimentações</p>
          <h2 class="secao__titulo" id="extrato-titulo">Extrato</h2>
        </div>
      </header>
      <?php if (!$extrato): ?>
        <p class="vazio">Nenhuma movimentação ainda.</p>
      <?php else: ?>
        <div class="tabela-rolagem">
          <table class="tabela">
            <thead><tr><th>Data</th><th>Descrição</th><th style="text-align:right">Valor</th></tr></thead>
            <tbody>
              <?php foreach ($extrato as $m): ?>
                <?php $entrada = (float) $m['valor'] >= 0; ?>
                <tr>
                  <td><?= dataCurta($m['dataHora']) ?></td>
                  <td>
                    <?php if ($m['id_leilao']): ?>
                      <a href="<?= e(url('leiloes.show', ['id' => $m['id_leilao']])) ?>"><?= e($m['descricao']) ?></a>
                    <?php else: ?>
                      <?= e($m['descricao']) ?>
                    <?php endif; ?>
                  </td>
                  <td class="<?= $entrada ? 'credito' : 'debito' ?>" style="text-align:right;white-space:nowrap"><?= $entrada ? '+ ' : '− ' ?><?= brl(abs((float) $m['valor'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
