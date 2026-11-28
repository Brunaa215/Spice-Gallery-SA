<?php
/**
 * Home (era index.html).
 * Variáveis: $totais ['obras','artistas','aoVivo'], $destaques, $obras, $vitrine (?array), $categorias
 */
use App\Models\Leilao;
?>
  <main id="conteudo">
    <!-- ============ HERO ============ -->
    <section class="hero envolve" aria-labelledby="hero-titulo">
      <div class="hero__texto">
        <p class="rotulo">Sala de entrada · Nº 000</p>
        <h1 class="hero__titulo" id="hero-titulo">Toda arte merece uma <em>parede</em>.</h1>
        <p class="hero__lead">Um museu online para artistas de 16 a 25 anos. Publique suas obras, seja visto, receba avaliações honestas e, quando estiver pronto, leve seu trabalho a leilão. Sem medo de julgamento.</p>
        <div class="hero__acoes">
          <a class="botao botao--tinta" href="<?= e(url('obras.create')) ?>">Publicar minha arte</a>
          <a class="botao" href="#galeria">Visitar a galeria</a>
        </div>
        <dl class="hero__numeros">
          <div><dt>obras na parede</dt><dd data-contar="<?= $totais['obras'] ?>"><?= number_format($totais['obras'], 0, ',', '.') ?></dd></div>
          <div><dt>artistas</dt><dd data-contar="<?= $totais['artistas'] ?>"><?= number_format($totais['artistas'], 0, ',', '.') ?></dd></div>
          <div><dt>leilões ao vivo</dt><dd data-contar="<?= $totais['aoVivo'] ?>"><?= $totais['aoVivo'] ?></dd></div>
        </dl>
      </div>


      <!-- três quadros lado a lado, retos e sem se cobrir: a obra aparece inteira -->
      <div class="hero__parede" aria-hidden="true">
        <figure class="quadro quadro--ouro quadro--pendurado q-a">
          <div class="quadro__passe"><img src="assets/img/obras/sol-de-acafrao.webp" alt="" width="800" height="1000"></div>
        </figure>
        <figure class="quadro quadro--fino q-b">
          <div class="quadro__passe"><img src="assets/img/obras/orbitas-lentas.webp" alt="" width="800" height="800"></div>
        </figure>
        <figure class="quadro quadro--madeira q-c">
          <div class="quadro__passe"><img src="assets/img/obras/mercado-6h.webp" alt="" width="800" height="600"></div>
        </figure>
      </div>
    </section>

    <?php if ($destaques): ?>
    <!-- ============ DESTAQUES (parede parada; era o corredor com rolagem lateral presa) ============ -->
    <section class="secao envolve destaques" id="destaques" aria-labelledby="destaques-titulo">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Sala 01 · Destaques</p>
          <h2 class="secao__titulo" id="destaques-titulo">As mais bem avaliadas</h2>
        </div>
        <p class="secao__nota">As obras com as melhores notas da comunidade. Clique numa delas para ver de perto.</p>
      </header>
      <ol class="destaques__parede">
        <?php foreach ($destaques as $obra): ?>
          <?php $emLeilao = !empty($obra['leilaoAoVivo']); ?>
          <li class="destaques__item">
            <a href="<?= e($emLeilao ? url('leiloes.show', ['id' => $obra['leilaoAoVivo']]) : url('obras.show', ['id' => $obra['id']])) ?>">
              <figure class="quadro <?= moldura($obra['id']) ?> quadro--pendurado"><div class="quadro__passe"><img src="<?= e($obra['urlImagem']) ?>" alt="<?= e($obra['titulo'] . ', de ' . $obra['autor']) ?>" loading="lazy"></div></figure>
              <div class="placa">
                <span class="placa__num"><?= numeroObra($obra['id']) ?><?= (int) $obra['avaliacoes'] > 0 ? ' · ' . nota($obra['media']) . ' ★' : '' ?><?= $emLeilao ? ' · em leilão' : '' ?></span>
                <h3 class="placa__titulo"><?= e($obra['titulo']) ?></h3>
                <p class="placa__autor"><?= e($obra['autor']) ?></p>
              </div>
            </a>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
    <?php endif; ?>

    <!-- ============ GALERIA ============ -->
    <section class="secao envolve" id="galeria" aria-labelledby="galeria-titulo">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Sala 02 · Acervo aberto</p>
          <h2 class="secao__titulo" id="galeria-titulo">Em exposição agora</h2>
        </div>
        <p class="secao__nota">Obras publicadas pela comunidade. Clique numa obra para ver de perto e avaliar.</p>
      </header>

      <div class="filtros" role="group" aria-label="Filtrar por categoria" data-revelar>
        <button class="filtro" type="button" aria-pressed="true" data-filtro="todas">Todas</button>
        <?php foreach ($categorias as $chave => $nome): ?>
          <button class="filtro" type="button" aria-pressed="false" data-filtro="<?= e($chave) ?>"><?= e($chave === 'digital' ? 'Digital' : $nome) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="salao" data-salao>
        <?php foreach ($obras as $obra): ?>
          <?php \App\Core\View::partial('obra_card', ['obra' => $obra]); ?>
        <?php endforeach; ?>
      </div>
      <p class="vazio" data-vazio<?= $obras ? ' hidden' : '' ?>>Nenhuma obra nesta categoria ainda. Que tal ser a primeira?</p>
    </section>

    <?php if ($vitrine): ?>
    <!-- ============ VITRINE NOTURNA (leilão em destaque) ============ -->
    <section class="secao vitrine noite" aria-labelledby="vitrine-titulo">
      <div class="envolve vitrine__grade">
        <figure class="quadro quadro--ouro quadro--pendurado">
          <div class="quadro__passe"><img src="<?= e($vitrine['urlImagem']) ?>" alt="<?= e($vitrine['titulo'] . ', de ' . $vitrine['autor']) ?>" loading="lazy"></div>
        </figure>
        <div>
          <p class="rotulo">Sala 03 · Leilão noturno · <?= numeroLote($vitrine['id']) ?></p>
          <h2 class="vitrine__titulo" id="vitrine-titulo"><?= e($vitrine['titulo']) ?> <span>vai a martelo.</span></h2>
          <p class="secao__nota" data-revelar><?= e(\App\Models\Obra::categoria($vitrine['categoria'])) ?> de <?= e($vitrine['autor']) ?>. Cada novo lance precisa superar o atual em pelo menos <?= brl($vitrine['incremento']) ?>.</p>
          <dl class="lance-resumo" data-revelar>
            <div><dt>Lance atual</dt><dd data-contar="<?= (int) Leilao::atual($vitrine) ?>" data-moeda><?= brl(Leilao::atual($vitrine)) ?></dd></div>
            <div><dt>Lances</dt><dd data-contar="<?= (int) $vitrine['lances'] ?>"><?= (int) $vitrine['lances'] ?></dd></div>
          </dl>
          <p class="legenda">Encerra em</p>
          <div class="contagem" data-fim="<?= dataIso($vitrine['dataFim']) ?>" style="margin:12px 0 34px"><?= dataHora($vitrine['dataFim']) ?></div>
          <a class="botao botao--acafrao" href="<?= e(url('leiloes.show', ['id' => $vitrine['id']])) ?>">Entrar no leilão</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- ============ REGRAS DA CASA ============ -->
    <section class="secao envolve" id="regras" aria-labelledby="regras-titulo">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Sala 04 · Regras da casa</p>
          <h2 class="secao__titulo" id="regras-titulo">Três gestos, um museu.</h2>
        </div>
        <p class="secao__nota" data-revelar>Visitantes passeiam à vontade. Para publicar, avaliar ou dar lances, basta criar uma conta de artista.</p>
      </header>
      <div class="regras">
        <article class="regra">
          <span class="regra__num" aria-hidden="true">I</span>
          <h3>Publique</h3>
          <p>Título, descrição, categoria e a imagem da obra. Você aceita os termos de direitos autorais e seu trabalho ganha uma parede.</p>
        </article>
        <article class="regra">
          <span class="regra__num" aria-hidden="true">II</span>
          <h3>Avalie</h3>
          <p>De 1 a 5 estrelas, com comentário. Só não vale avaliar a própria obra, e você edita ou apaga apenas o que escreveu.</p>
        </article>
        <article class="regra">
          <span class="regra__num" aria-hidden="true">III</span>
          <h3>Arremate</h3>
          <p>Obras exclusivas vão a leilão pelo tempo que o artista escolheu, de minutos a dias. Cada lance supera o anterior pelo incremento mínimo.</p>
        </article>
      </div>
    </section>

    <!-- ============ CHAMADA ============ -->
    <section class="chamada envolve" aria-labelledby="chamada-titulo">
      <p class="rotulo">Sala 05 · Sua vez</p>
      <h2 class="chamada__titulo" id="chamada-titulo">Sua parede está <em>vazia</em>.</h2>
      <a class="botao botao--tinta" href="<?= e(url('obras.create')) ?>" data-revelar>Pendurar minha primeira obra</a>
    </section>
  </main>
