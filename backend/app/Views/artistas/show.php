<?php
/**
 * Sala individual do artista (era artista.html): o perfil (retrato na moldura escolhida, apelido,
 * especialidade, estilos, redes, papel de parede), as obras dele e a coleção (obras que arrematou).
 * Variáveis: $artista, $obras, $ehDono, $leiloes (só quando $ehDono), $colecao, $redes
 */
use App\Core\View;
use App\Models\Leilao;
use App\Models\RedeSocial;
use App\Models\Usuario;

$partes = explode(' ', trim($artista['nome']), 2);
$primeiro = explode(' ', $artista['nome'])[0];
// Nome com uma palavra muito comprida ("Migsdeboobies") não cabe no tamanho gigante: o CSS encolhe
// a fonte pelo número de letras da maior palavra (--letras) para ela caber na coluna.
$letras = max(array_map('mb_strlen', preg_split('/\s+/', trim($artista['nome'])) ?: ['']));
$estiloNome = $letras > 8 ? ' style="--letras:' . $letras . '"' : '';
$total = (int) $artista['obras'];
// Obra mais avaliada (para o botão do cartaz)
$maisAvaliada = null;
foreach ($obras as $o)
    if (!$maisAvaliada || (int) $o['avaliacoes'] > (int) $maisAvaliada['avaliacoes'])
        $maisAvaliada = $o;
$naColecao = count($colecao);
$estilos = Usuario::estilos($artista['estilos']);
// Papel de parede: só caminhos do próprio site entram no url() do CSS (imagemLocal).
$wallpaper = imagemLocal($artista['wallpaper']);
?>
  <main id="conteudo">
    <div class="sala-topo<?= $wallpaper ? ' sala-topo--wallpaper' : '' ?>">
    <?php if ($wallpaper): ?>
      <div class="sala-topo__fundo" style="background-image:url('<?= e($wallpaper) ?>')" aria-hidden="true"></div>
    <?php endif; ?>
    <section class="cartaz envolve" aria-labelledby="artista-nome">
      <div>
        <div class="cartaz__perfil">
          <?php View::partial('retrato', ['pessoa' => $artista]); ?>
          <div>
            <p class="rotulo">Exposição individual · <?= date('Y') ?></p>
            <?php if ($artista['especialidade']): ?>
              <p class="cartaz__especialidade"><?= e($artista['especialidade']) ?></p>
            <?php endif; ?>
            <?php if ($artista['apelido']): ?>
              <p class="cartaz__apelido"><small>apelido</small> “<?= e($artista['apelido']) ?>”</p>
            <?php endif; ?>
          </div>
        </div>
        <h1 class="cartaz__nome" id="artista-nome"<?= $estiloNome ?>><?= e($partes[0]) ?><?php if (isset($partes[1])): ?> <span><?= e($partes[1]) ?></span><?php endif; ?></h1>
      </div>
      <div data-cascata>
        <p class="cartaz__bio">No Spice Gallery desde <?= e(MESES[(int) date('n', strtotime($artista['criadoEm'])) - 1] . ' de ' . date('Y', strtotime($artista['criadoEm']))) ?>. <?= $total ?> <?= $total === 1 ? 'obra pendurada' : 'obras penduradas' ?> nesta sala<?= $naColecao ? ' e ' . $naColecao . ($naColecao === 1 ? ' arrematada na coleção' : ' arrematadas na coleção') : '' ?>.</p>
        <?php if ($estilos): ?>
          <ul class="estilos" aria-label="Estilos">
            <?php foreach ($estilos as $estilo): ?><li><?= e($estilo) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <dl class="cartaz__numeros">
          <div><dt>Obras</dt><dd data-contar="<?= $total ?>"><?= $total ?></dd></div>
          <div><dt>Avaliações</dt><dd data-contar="<?= (int) $artista['avaliacoes'] ?>"><?= (int) $artista['avaliacoes'] ?></dd></div>
          <div><dt>Média</dt><dd><?= $artista['media'] !== null ? nota($artista['media']) : '–' ?></dd></div>
        </dl>
        <?php if ($redes): ?>
          <ul class="redes" aria-label="Redes sociais">
            <?php foreach ($redes as $rede => $link): ?>
              <li><a href="<?= e($link) ?>" target="_blank" rel="me noopener noreferrer"><?= e(RedeSocial::REDES[$rede][0]) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <div class="hero__acoes" style="margin-top:0">
          <?php if ($ehDono): ?>
            <a class="botao botao--tinta" href="<?= e(url('obras.create')) ?>">Publicar nova obra</a>
            <a class="botao" href="<?= e(url('perfil.editar')) ?>">Editar perfil</a>
            <a class="botao" href="<?= e(url('carteira')) ?>">Minha carteira</a>
          <?php else: ?>
            <a class="botao botao--tinta" href="#obras">Ver as obras</a>
            <?php if ($maisAvaliada): ?>
              <a class="botao" href="<?= e(url('obras.show', ['id' => $maisAvaliada['id']])) ?>">Obra mais avaliada</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
    </div>

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
            <?php View::partial('obra_card', ['obra' => $obra, 'mostrarAutor' => false]); ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="vazio"><?= $ehDono ? 'Sua parede está vazia. Pendure a primeira obra!' : 'Esta parede ainda está vazia.' ?></p>
      <?php endif; ?>
    </section>

    <?php if ($colecao || $ehDono): ?>
    <!-- Coleção: obras de outros artistas que esta pessoa arrematou. O valor pago só o dono vê. -->
    <section class="secao envolve" id="colecao" aria-labelledby="colecao-titulo"<?= $obras ? ' style="padding-top:0"' : '' ?>>
      <header class="secao__cab">
        <div>
          <p class="rotulo">Coleção · <?= $naColecao ?> <?= $naColecao === 1 ? 'obra arrematada' : 'obras arrematadas' ?></p>
          <h2 class="secao__titulo" id="colecao-titulo"><?= $ehDono ? 'Sua coleção' : 'Coleção de ' . e($primeiro) ?></h2>
        </div>
        <p class="secao__nota" data-revelar>Obras de outros artistas arrematadas nos leilões do Spice Gallery. Quem arremata leva a obra para a própria sala.</p>
      </header>
      <?php if ($colecao): ?>
        <div class="salao salao--artista">
          <?php foreach ($colecao as $obra): ?>
            <?php View::partial('obra_card', ['obra' => $obra, 'mostrarAutor' => true,
              'selo' => $ehDono ? 'Arrematada por ' . brl($obra['valorPago']) . ' em ' . dataCurta($obra['arrematadaEm']) : 'Arrematada em leilão']); ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="vazio">Quando você arrematar uma obra num <a href="<?= e(url('leiloes')) ?>">leilão</a>, ela aparece aqui.</p>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($ehDono && $leiloes): ?>
    <section class="secao envolve" aria-labelledby="meus-leiloes" style="padding-top:0">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Só você vê</p>
          <h2 class="secao__titulo" id="meus-leiloes">Seus leilões</h2>
        </div>
        <p class="secao__nota">Leilões que ainda não começaram podem ser alterados ou cancelados editando a obra.</p>
      </header>
      <div class="tabela-rolagem">
        <table class="tabela">
          <thead><tr><th>Lote</th><th>Obra</th><th>Situação</th><th>Início</th><th>Fim</th><th>Lance atual</th></tr></thead>
          <tbody>
            <?php foreach ($leiloes as $l): ?>
              <?php $esperaAprovacao = $l['status'] === Leilao::AGUARDANDO && (int) $l['abreNaAprovacao']; ?>
              <tr>
                <td><a href="<?= e(url('leiloes.show', ['id' => $l['id']])) ?>"><?= numeroLote($l['id']) ?></a></td>
                <td><?= e($l['titulo']) ?></td>
                <td><?= $l['id_vencedor'] ? 'Vendida para ' . e($l['vencedor'] ?? 'outra conta') : e(Leilao::estado($l)) ?></td>
                <td><?= $esperaAprovacao ? 'Na aprovação' : dataHora($l['dataInicio']) ?></td>
                <td><?= $esperaAprovacao ? 'Dura ' . duracao(Leilao::duracaoMinutos($l)) : dataHora($l['dataFim']) ?></td>
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
