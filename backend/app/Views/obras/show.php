<?php
/**
 * Detalhe da obra + avaliações (era obra.html).
 * Variáveis: $obra, $artista, $avaliacoes, $distribuicao, $minha (?array), $ehAutor, $leilao (?array), $maisDoAutor
 */
use App\Core\Csrf;
use App\Models\Leilao;
use App\Models\Obra;

$total = (int) $obra['avaliacoes'];
$alt = $obra['titulo'] . ', de ' . $obra['autor'];
$linkArtista = url('artistas.show', ['id' => $obra['id_usuario']]);
// RN-04/RN-05: só avalia quem está logado e não é o autor
$podeAvaliar = $current && !$ehAutor;
// Obra vendida em leilão (já paga com créditos): não pode mais ser excluída.
$arrematada = $leilao && $leilao['id_vencedor'];
?>
  <main id="conteudo">
    <div class="envolve">
      <nav class="trilha" aria-label="Você está em">
        <ol>
          <li><a href="<?= e(url('home')) ?>">Galeria</a></li>
          <li><a href="<?= e(url('home')) ?>#galeria">Sala 02</a></li>
          <li aria-current="page"><?= numeroObra($obra['id']) ?></li>
        </ol>
      </nav>

      <div class="exposicao">
        <div class="parede-luz" data-holofote>
          <figure class="quadro quadro--ouro quadro--pendurado">
            <div class="quadro__passe"><img src="<?= e($obra['urlImagem']) ?>" alt="<?= e($alt) ?>"></div>
          </figure>
        </div>

        <aside class="ficha" data-cascata>
          <p class="rotulo"><?= numeroObra($obra['id']) ?> · <?= e(Obra::categoria($obra['categoria'])) ?></p>
          <h1 class="ficha__titulo"><?= e($obra['titulo']) ?></h1>
          <a class="ficha__artista" href="<?= e($linkArtista) ?>">
            <span class="avatar" style="--cor:var(--<?= corAvatar($obra['id_usuario']) ?>)"><?= e(iniciais($obra['autor'])) ?></span>
            <span><strong><?= e($obra['autor']) ?></strong><small><?= (int) $artista['obras'] ?> <?= (int) $artista['obras'] === 1 ? 'obra' : 'obras' ?> na galeria</small></span>
          </a>
          <p class="ficha__texto"><?= nl2br(e($obra['descricao'])) ?></p>
          <dl>
            <div><dt>Categoria</dt><dd><?= e(Obra::categoria($obra['categoria'])) ?></dd></div>
            <div><dt>Publicada em</dt><dd><?= dataCurta($obra['criadoEm']) ?></dd></div>
            <div><dt>Avaliação</dt><dd>
              <?php if ($total > 0): ?>
                <span class="estrelas" style="--nota:<?= e($obra['media']) ?>" aria-hidden="true"></span> <?= nota($obra['media']) ?>
              <?php else: ?>
                Sem avaliações
              <?php endif; ?>
            </dd></div>
            <div><dt>Status</dt><dd><?php if ($arrematada): ?>Arrematada por <?= e($leilao['vencedor'] ?? 'outra conta') ?> (<?= brl($leilao['maiorLance']) ?>)<?php else: ?><?= $leilao ? e(Leilao::estado($leilao)) . ' (leilão)' : 'Publicada' ?><?php endif; ?></dd></div>
          </dl>
          <div class="hero__acoes" style="margin-top:0">
            <?php if ($leilao && ($leilao['status'] === Leilao::APROVADO || $ehAutor)): ?>
              <a class="botao botao--acafrao" href="<?= e(url('leiloes.show', ['id' => $leilao['id']])) ?>">Ver leilão</a>
            <?php endif; ?>
            <?php if ($podeAvaliar): ?>
              <a class="botao botao--tinta" href="#avaliar"><?= $minha ? 'Editar minha avaliação' : 'Avaliar esta obra' ?></a>
            <?php endif; ?>
            <a class="botao" href="<?= e($linkArtista) ?>">Galeria do artista</a>
          </div>

          <?php if ($ehAutor || $isAdmin): ?>
            <div class="comentario__acoes" style="margin-top:22px">
              <?php if ($ehAutor): ?>
                <a class="texto-botao" href="<?= e(url('obras.edit', ['id' => $obra['id']])) ?>">Editar obra</a>
              <?php endif; ?>
              <?php if (!$arrematada): ?>
                <form method="post" action="<?= e(url('obras.delete')) ?>" data-confirmar="Excluir esta obra? As avaliações e o leilão dela também serão apagados.">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $obra['id'] ?>">
                  <button class="texto-botao" type="submit">Excluir obra</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </aside>
      </div>
    </div>

    <section class="secao envolve" id="avaliacoes" aria-labelledby="avaliacoes-titulo" style="padding-top:0">
      <div class="avaliacoes">
        <div>
          <p class="rotulo">Avaliações</p>
          <?php if ($total > 0): ?>
            <div class="media" data-revelar>
              <b><?= nota($obra['media']) ?></b>
              <div><span class="estrelas" style="--nota:<?= e($obra['media']) ?>" aria-hidden="true"></span><small>média de <?= $total ?> <?= $total === 1 ? 'avaliação' : 'avaliações' ?></small></div>
            </div>
            <ul class="distribuicao" aria-label="Distribuição das notas" data-revelar>
              <?php foreach ($distribuicao as $estrelas => $qtd): ?>
                <li><span><?= $estrelas ?>★</span><span class="barra"><i style="--p:<?= round($qtd / $total * 100) ?>%"></i></span><span><?= $qtd ?></span></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="secao__nota" style="margin-bottom:28px">Ninguém avaliou esta obra ainda.</p>
          <?php endif; ?>

          <?php if ($podeAvaliar): ?>
            <!-- id_avaliacao preenchido = edição da própria avaliação (RN-06) -->
            <form class="cartao-form" id="avaliar" method="post" action="<?= e(url('avaliacoes.save')) ?>" data-revelar>
              <?= Csrf::field() ?>
              <input type="hidden" name="id_obra" value="<?= (int) $obra['id'] ?>">
              <input type="hidden" name="id_avaliacao" value="<?= $minha ? (int) $minha['id'] : '' ?>">
              <fieldset class="campo">
                <legend>Sua nota</legend>
                <div class="estrelas-input">
                  <?php for ($n = 5; $n >= 1; $n--): ?>
                    <input type="radio" id="nota-<?= $n ?>" name="nota" value="<?= $n ?>"<?= $n === 5 ? ' required' : '' ?><?= $minha && (int) $minha['nota'] === $n ? ' checked' : '' ?>><label for="nota-<?= $n ?>">★<span class="sr-only"><?= $n ?> <?= $n === 1 ? 'estrela' : 'estrelas' ?></span></label>
                  <?php endfor; ?>
                </div>
              </fieldset>
              <div class="campo">
                <label for="comentario">Comentário</label>
                <textarea id="comentario" name="comentario" maxlength="1000" placeholder="O que essa obra fez você sentir?"><?= e($minha['comentario'] ?? '') ?></textarea>
              </div>
              <button class="botao botao--tinta botao--largo" type="submit"><?= $minha ? 'Salvar alterações' : 'Publicar avaliação' ?></button>
              <p class="campo__ajuda" style="margin-top:14px">Artistas não avaliam a própria obra. Você pode editar ou apagar só as suas avaliações.</p>
            </form>
          <?php elseif ($ehAutor): ?>
            <p class="campo__ajuda">Esta obra é sua. Artistas não avaliam a própria obra.</p>
          <?php else: ?>
            <div class="cartao-form" id="avaliar">
              <p class="secao__nota" style="margin-top:0">Entre na sua conta para deixar uma nota e um comentário.</p>
              <a class="botao botao--tinta botao--largo" href="<?= e(url('login')) ?>">Entrar para avaliar</a>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <h2 class="secao__titulo" id="avaliacoes-titulo" style="margin:0 0 36px">O que a parede ouviu</h2>
          <?php if (!$avaliacoes): ?>
            <p class="vazio">Silêncio por enquanto. A primeira avaliação pode ser a sua.</p>
          <?php else: ?>
            <ol class="comentarios" data-cascata>
              <?php foreach ($avaliacoes as $a): ?>
                <?php $meu = $current && (int) $a['id_usuario'] === (int) $current['id']; ?>
                <li class="comentario<?= $meu ? ' comentario--meu' : '' ?>">
                  <span class="avatar" style="--cor:var(--<?= corAvatar($a['id_usuario']) ?>)"><?= e(iniciais($a['nome'])) ?></span>
                  <div>
                    <div class="comentario__cab"><strong><?= $meu ? 'Você' : e($a['nome']) ?></strong><span class="estrelas" role="img" style="--nota:<?= (int) $a['nota'] ?>" aria-label="<?= (int) $a['nota'] ?> de 5"></span><time datetime="<?= dataIso($a['dataHora']) ?>"><?= quando($a['dataHora']) ?></time></div>
                    <p><?= e($a['comentario']) ?></p>
                    <?php if ($meu): ?>
                      <!-- RN-06: ações só para quem escreveu -->
                      <div class="comentario__acoes">
                        <button class="texto-botao" type="button" data-editar="<?= (int) $a['id'] ?>" data-nota="<?= (int) $a['nota'] ?>">Editar</button>
                        <form method="post" action="<?= e(url('avaliacoes.delete')) ?>" data-confirmar="Excluir sua avaliação? Isso não pode ser desfeito.">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id_avaliacao" value="<?= (int) $a['id'] ?>">
                          <button class="texto-botao" type="submit">Excluir</button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <?php if ($maisDoAutor): ?>
    <section class="secao envolve" aria-labelledby="mais-titulo" style="padding-top:0">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Da mesma parede</p>
          <h2 class="secao__titulo" id="mais-titulo">Mais de <?= e($obra['autor']) ?></h2>
        </div>
        <a class="link-seta" href="<?= e($linkArtista) ?>" data-revelar>Ver galeria completa</a>
      </header>
      <div class="salao salao--artista">
        <?php foreach ($maisDoAutor as $outra): ?>
          <?php \App\Core\View::partial('obra_card', ['obra' => $outra, 'mostrarAutor' => false]); ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </main>
