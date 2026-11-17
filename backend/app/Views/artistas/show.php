<?php
/**
 * Sala individual do artista (era artista.html).
 * Variáveis: $artista, $obras, $ehDono, $leiloes (só quando $ehDono)
 */
use App\Models\Leilao;

$partes = explode(' ', trim($artista['nome']), 2);
$primeiro = explode(' ', $artista['nome'])[0];
$total = (int) $artista['obras'];
// Obra mais avaliada (para o botão do cartaz)
$maisAvaliada = null;
foreach ($obras as $o)
    if (!$maisAvaliada || (int) $o['avaliacoes'] > (int) $maisAvaliada['avaliacoes'])
        $maisAvaliada = $o;
?>
  <main id="conteudo">
    <section class="cartaz envolve" aria-labelledby="artista-nome">
      <div>
        <p class="rotulo">Exposição individual · <?= date('Y') ?></p>
        <h1 class="cartaz__nome" id="artista-nome"><?= e($partes[0]) ?><?php if (isset($partes[1])): ?> <span><?= e($partes[1]) ?></span><?php endif; ?></h1>
      </div>
      <div data-cascata>
        <p class="cartaz__bio">No Spice Gallery desde <?= e(MESES[(int) date('n', strtotime($artista['criadoEm'])) - 1] . ' de ' . date('Y', strtotime($artista['criadoEm']))) ?>. <?= $total ?> <?= $total === 1 ? 'obra pendurada' : 'obras penduradas' ?> nesta sala.</p>
        <dl class="cartaz__numeros">
          <div><dt>Obras</dt><dd data-contar="<?= $total ?>"><?= $total ?></dd></div>
          <div><dt>Avaliações</dt><dd data-contar="<?= (int) $artista['avaliacoes'] ?>"><?= (int) $artista['avaliacoes'] ?></dd></div>
          <div><dt>Média</dt><dd><?= $artista['media'] !== null ? nota($artista['media']) : '–' ?></dd></div>
        </dl>
        <div class="hero__acoes" style="margin-top:0">
          <?php if ($ehDono): ?>
            <a class="botao botao--tinta" href="<?= e(url('obras.create')) ?>">Publicar nova obra</a>
            <a class="botao" href="<?= e(url('password')) ?>">Minha senha</a>
          <?php else: ?>
            <a class="botao botao--tinta" href="#obras">Ver as obras</a>
            <?php if ($maisAvaliada): ?>
              <a class="botao" href="<?= e(url('obras.show', ['id' => $maisAvaliada['id']])) ?>">Obra mais avaliada</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="secao envolve" id="obras" aria-labelledby="obras-titulo">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Sala única · <?= $total ?> <?= $total === 1 ? 'obra' : 'obras' ?></p>
          <h2 class="secao__titulo" id="obras-titulo">Parede de <?= e($primeiro) ?></h2>
        </div>
        <p class="secao__nota" data-revelar>Cada artista tem uma sala só sua. As obras ficam penduradas aqui na ordem em que foram publicadas.</p>
      </header>
      <?php if ($obras): ?>
        <div class="salao salao--artista">
          <?php foreach ($obras as $obra): ?>
            <?php \App\Core\View::partial('obra_card', ['obra' => $obra, 'mostrarAutor' => false]); ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="vazio"><?= $ehDono ? 'Sua parede está vazia. Pendure a primeira obra!' : 'Esta parede ainda está vazia.' ?></p>
      <?php endif; ?>
    </section>

    <?php if ($ehDono && $leiloes): ?>
    <section class="secao envolve" aria-labelledby="meus-leiloes" style="padding-top:0">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Só você vê</p>
          <h2 class="secao__titulo" id="meus-leiloes">Seus leilões</h2>
        </div>
        <p class="secao__nota">A curadoria aprova cada leilão antes de ele abrir.</p>
      </header>
      <div class="tabela-rolagem">
        <table class="tabela">
          <thead><tr><th>Lote</th><th>Obra</th><th>Situação</th><th>Início</th><th>Fim</th><th>Lance atual</th></tr></thead>
          <tbody>
            <?php foreach ($leiloes as $l): ?>
              <tr>
                <td><a href="<?= e(url('leiloes.show', ['id' => $l['id']])) ?>"><?= numeroLote($l['id']) ?></a></td>
                <td><?= e($l['titulo']) ?></td>
                <td><?= e(Leilao::estado($l)) ?></td>
                <td><?= dataHora($l['dataInicio']) ?></td>
                <td><?= dataHora($l['dataFim']) ?></td>
                <td><?= brl(Leilao::atual($l)) ?> (<?= (int) $l['lances'] ?>)</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <?php if (!$current): ?>
    <section class="chamada envolve" aria-labelledby="chamada-titulo">
      <p class="rotulo">Para artistas</p>
      <h2 class="chamada__titulo" id="chamada-titulo">Abra a sua <em>sala</em>.</h2>
      <a class="botao botao--tinta" href="<?= e(url('login')) ?>#cadastro" data-revelar>Criar conta de artista</a>
    </section>
    <?php endif; ?>
  </main>
