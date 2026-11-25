<?php
/**
 * Painel do leilão: situação, cronômetro, lance atual e o botão de lance (ou o aviso que o substitui).
 * É o conteúdo do <aside class="painel-lance">: a página o desenha e o app.js o troca ao vivo
 * pelo que a rota leiloes.estado devolve, sem recarregar.
 * Variáveis: $leilao, $lances, $ehAutor, $isAdmin, $disponivel (crédito livre), $current
 */
use App\Core\Csrf;
use App\Core\View;
use App\Models\Leilao;
use App\Models\Obra;

$estado = $leilao['estado'];
$aoVivo = $estado === 'AO_VIVO';
$atual = Leilao::atual($leilao);
$minimo = Leilao::minimo($leilao);
$qtd = (int) $leilao['lances'];
$abreNaAprovacao = $estado === Leilao::AGUARDANDO && (int) $leilao['abreNaAprovacao'];
// Quem está na frente: o primeiro da lista (maior valor).
$lider = $lances[0] ?? null;
$souLider = $current && $lider && (int) $lider['id_usuario'] === (int) $current['id'];
$podeDarLance = $aoVivo && $current && !$ehAutor;
$semCredito = centavos($disponivel) < centavos($minimo);
$venci = $current && $leilao['id_vencedor'] && (int) $leilao['id_vencedor'] === (int) $current['id'];
$prorrogacao = duracao(intdiv(Leilao::PRORROGACAO_SEGUNDOS, 60));
?>
          <p class="rotulo"><?= numeroLote($leilao['id']) ?> · <?= e(Leilao::estado($leilao)) ?></p>
          <h1 class="ficha__titulo"><a href="<?= e(url('obras.show', ['id' => $leilao['id_obra']])) ?>" style="color:inherit;text-decoration:none"><?= e($leilao['titulo']) ?></a></h1>
          <a class="ficha__artista" href="<?= e(url('artistas.show', ['id' => $leilao['idAutor']])) ?>">
            <?= avatar($leilao['idAutor'], $leilao['autor'], $leilao['autorFoto'] ?? null) ?>
            <span><strong><?= e($leilao['autor']) ?></strong><small><?= e(Obra::categoria($leilao['categoria'])) ?></small></span>
          </a>

          <?php if ($abreNaAprovacao): ?>
            <p class="legenda">Abre quando a curadoria aprovar</p>
            <div class="contagem contagem--fim" style="margin:12px 0 30px">Duração: <?= duracao(Leilao::duracaoMinutos($leilao)) ?></div>
          <?php elseif ($estado === 'EM_BREVE' || $estado === Leilao::AGUARDANDO): ?>
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
          <p class="painel-lance__sub"><span data-qtd-lances><?= $qtd ?></span> <?= $qtd === 1 ? 'lance' : 'lances' ?> · incremento mínimo <?= brl($leilao['incremento']) ?> · inicial <?= brl($leilao['valorInicial']) ?><?= $estado !== 'ENCERRADO' && !$abreNaAprovacao ? ' · duração ' . duracao(Leilao::duracaoMinutos($leilao)) : '' ?></p>

          <?php if ($podeDarLance && $souLider): ?>
            <p class="aviso" style="margin-top:26px">Você está na frente com <b><?= brl($lider['valor']) ?></b>. Esse valor fica reservado na sua carteira até alguém cobrir ou o leilão fechar.</p>
          <?php elseif ($podeDarLance && $semCredito): ?>
            <p class="aviso" style="margin-top:26px">Você tem <b><?= brl($disponivel) ?></b> disponíveis na carteira e o lance mínimo é <b><?= brl($minimo) ?></b>.</p>
            <a class="botao botao--acafrao botao--largo" href="<?= e(url('carteira', ['leilao' => $leilao['id']])) ?>">Comprar créditos</a>
          <?php elseif ($podeDarLance): ?>
            <!-- data-lance-minimo: o mesmo mínimo que o servidor vai exigir (RN-08); data-disponivel: crédito livre -->
            <form class="cartao-form" method="post" action="<?= e(url('lances.save')) ?>" data-lance data-atual="<?= (int) $atual ?>" data-lance-minimo="<?= (int) ceil($minimo) ?>" data-incremento="<?= (int) $leilao['incremento'] ?>" data-disponivel="<?= (int) floor($disponivel) ?>" data-fim="<?= dataIso($leilao['dataFim']) ?>" style="margin-top:26px">
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
              <p class="campo__ajuda" style="margin-top:12px">Disponível na carteira: <?= brl($disponivel) ?> · <a href="<?= e(url('carteira', ['leilao' => $leilao['id']])) ?>">comprar créditos</a></p>
            </form>
          <?php elseif ($aoVivo && !$current): ?>
            <a class="botao botao--acafrao botao--largo" href="<?= e(url('login')) ?>" style="margin-top:26px">Entrar para dar lances</a>
          <?php elseif ($estado === 'ENCERRADO' && $leilao['id_vencedor']): ?>
            <?php if ($venci): ?>
              <p class="aviso" style="margin-top:26px">Você arrematou esta obra! <b><?= brl($atual) ?></b> saiu da sua carteira e foi para o artista.</p>
            <?php elseif ($ehAutor): ?>
              <p class="aviso" style="margin-top:26px">Obra vendida para <?= e($leilao['vencedor'] ?? 'outra conta') ?>: <b><?= brl($atual) ?></b> entrou na sua carteira.</p>
            <?php else: ?>
              <p class="aviso" style="margin-top:26px">Arrematada por <?= e($leilao['vencedor'] ?? 'outra conta') ?>.</p>
            <?php endif; ?>
          <?php elseif ($estado === 'ENCERRADO' && !$qtd): ?>
            <p class="aviso" style="margin-top:26px">O leilão fechou sem lances.<?= $ehAutor ? ' Você pode colocar a obra em leilão de novo ao editá-la.' : '' ?></p>
          <?php elseif ($estado === Leilao::AGUARDANDO && $isAdmin): ?>
            <?php View::partial('leilao_decisao', ['leilao' => $leilao, 'voltar' => 'leilao']); ?>
          <?php elseif ($estado === Leilao::AGUARDANDO): ?>
            <p class="aviso" style="margin-top:26px">Para começar, a curadoria precisa aprovar este leilão (só obras aprovadas entram em leilão).
              <?= $abreNaAprovacao
                ? 'Assim que ela aprovar, ele abre na hora e dura ' . duracao(Leilao::duracaoMinutos($leilao)) . '.'
                : 'Aprovado, ele abre ' . dataHora($leilao['dataInicio']) . '.' ?>
              <?= $ehAutor ? 'Enquanto isso, dá para mudar valores, início e duração editando a obra.' : '' ?></p>
          <?php elseif ($estado === Leilao::REPROVADO): ?>
            <p class="aviso" style="margin-top:26px">A curadoria não aprovou este leilão. Você pode marcar a obra para leilão de novo ao editá-la.</p>
          <?php endif; ?>
          <p class="campo__ajuda" style="margin-top:14px"><?= $ehAutor
            ? 'Esta obra é sua: artistas não dão lances nas próprias obras.'
            : 'Os lances usam os créditos fictícios da sua carteira e não podem ser cancelados.'
              . (Leilao::PRORROGACAO_SEGUNDOS > 0 ? ' Lance no último minuto prorroga o leilão por mais ' . $prorrogacao . '.' : '') ?></p>
