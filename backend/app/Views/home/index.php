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
      <canvas class="poeira" aria-hidden="true"></canvas>
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
        <div class="selo" data-magnetico>
          <div class="selo__giro"><svg viewBox="0 0 200 200"><defs><path id="circulo" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs><text font-family="DM Mono, monospace" font-size="13" letter-spacing="3.4" fill="currentColor"><textPath href="#circulo">SPICE GALLERY • MUSEU ONLINE • DESDE 2026 • </textPath></text></svg></div>
          <span class="selo__miolo">S</span>
        </div>
      </div>
    </section>

    <!-- ============ MERGULHO (sequência fixada no scroll) ============ -->
    <section class="mergulho" aria-labelledby="mergulho-titulo">
      <!-- invólucro e miolo: a entrada anima um, o mergulho anima o outro (nunca os dois no mesmo elemento) -->
      <p class="mergulho__frase mergulho__frase--a" aria-hidden="true"><span class="mergulho__miolo">Cada quadro</span></p>
      <p class="mergulho__frase mergulho__frase--b" aria-hidden="true"><span class="mergulho__miolo">é uma porta.</span></p>
      <div class="mergulho__moldura">
      <figure class="quadro quadro--ouro mergulho__quadro" aria-hidden="true">
        <!-- SVG embutido: no zoom o navegador desenha só a área visível, em vetor -->
        <div class="quadro__passe"><svg class="mergulho__obra" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000" width="800" height="1000" focusable="false"><rect width="800" height="1000" fill="#FBF1EC"/><rect x="0" y="620" width="800" height="380" fill="#9DB6D3"/><circle cx="464" cy="400" r="240" fill="#E2C070"/><path d="M 64 620 A 176 176 0 0 1 416 620 Z" fill="#C9647F"/><rect x="560" y="500" width="96" height="360" fill="#7F9A7C"/><line x1="80" y1="120" x2="256" y2="120" stroke="#A07A34" stroke-width="3"/><line x1="80" y1="150" x2="256" y2="150" stroke="#A07A34" stroke-width="3"/><line x1="80" y1="180" x2="256" y2="180" stroke="#A07A34" stroke-width="3"/><line x1="80" y1="210" x2="256" y2="210" stroke="#A07A34" stroke-width="3"/><line x1="80" y1="240" x2="256" y2="240" stroke="#A07A34" stroke-width="3"/><line x1="80" y1="270" x2="256" y2="270" stroke="#A07A34" stroke-width="3"/><line x1="80" y1="300" x2="256" y2="300" stroke="#A07A34" stroke-width="3"/><circle cx="688" cy="140" r="40" fill="none" stroke="#A07A34" stroke-width="4"/></svg></div>
      </figure>
      </div>
      <div class="mergulho__alem">
        <p class="mergulho__sobre">Do outro lado da moldura</p>
        <h2 class="mergulho__titulo" id="mergulho-titulo"><?= number_format($totais['obras'], 0, ',', '.') ?> <?= $totais['obras'] === 1 ? 'obra esperando' : 'obras esperando' ?> seus olhos.</h2>
        <a class="botao botao--tinta" href="#corredor">Começar a visita</a>
      </div>
    </section>

    <!-- ============ FAIXA ============ -->
    <div class="faixa" aria-hidden="true">
      <div class="faixa__trilho">
        <div class="faixa__grupo">
          <span>Pintura</span><i style="--c:var(--acafrao)"></i><span>Ilustração digital</span><i style="--c:var(--paprica)"></i><span>Aquarela</span><i style="--c:var(--rosa)"></i><span>Gravura</span><i style="--c:var(--cardamomo)"></i><span>Fotografia</span><i style="--c:var(--acafrao)"></i><span>Escultura</span><i style="--c:var(--paprica)"></i><span>Colagem</span><i style="--c:var(--rosa)"></i>
        </div>
        <div class="faixa__grupo">
          <span>Pintura</span><i style="--c:var(--acafrao)"></i><span>Ilustração digital</span><i style="--c:var(--paprica)"></i><span>Aquarela</span><i style="--c:var(--rosa)"></i><span>Gravura</span><i style="--c:var(--cardamomo)"></i><span>Fotografia</span><i style="--c:var(--acafrao)"></i><span>Escultura</span><i style="--c:var(--paprica)"></i><span>Colagem</span><i style="--c:var(--rosa)"></i>
        </div>
      </div>
    </div>


    <?php if ($destaques): ?>
    <!-- ============ CORREDOR (rolagem horizontal fixada) ============ -->
    <section class="corredor" id="corredor" aria-labelledby="corredor-titulo">
      <header class="corredor__cab envolve">
        <div>
          <p class="rotulo">Sala 01 · Destaques</p>
          <h2 class="corredor__titulo" id="corredor-titulo">O corredor</h2>
        </div>
        <p class="corredor__contador" aria-hidden="true"><b>01</b> / <?= str_pad((string) count($destaques), 2, '0', STR_PAD_LEFT) ?></p>
      </header>
      <p class="corredor__fundo" aria-hidden="true">Destaques</p>
      <ol class="corredor__trilho">
        <?php foreach ($destaques as $obra): ?>
          <?php $emLeilao = !empty($obra['leilaoAoVivo']); ?>
          <li class="corredor__item">
            <a href="<?= e($emLeilao ? url('leiloes.show', ['id' => $obra['leilaoAoVivo']]) : url('obras.show', ['id' => $obra['id']])) ?>" data-cursor="<?= $emLeilao ? 'Dar lance' : 'Ver obra' ?>">
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
      <div class="corredor__barra" aria-hidden="true"><i></i></div>
    </section>
    <?php endif; ?>

    <!-- ============ GALERIA ============ -->
    <section class="secao envolve" id="galeria" aria-labelledby="galeria-titulo">
      <header class="secao__cab">
        <div>
          <p class="rotulo">Sala 02 · Acervo aberto</p>
          <h2 class="secao__titulo" id="galeria-titulo">Em exposição agora</h2>
        </div>
        <p class="secao__nota" data-revelar>Obras publicadas pela comunidade. Passe o mouse para endireitar um quadro, entre para avaliar.</p>
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
      <canvas class="poeira" aria-hidden="true" data-tom="noite"></canvas>
      <div class="envolve vitrine__grade">
        <figure class="quadro quadro--ouro quadro--pendurado" style="--r:-1.5deg">
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
          <a class="botao botao--acafrao" href="<?= e(url('leiloes.show', ['id' => $vitrine['id']])) ?>" data-cursor="Dar lance">Entrar no leilão</a>
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
          <p>Obras aprovadas pela curadoria vão a leilão com início e fim fixos. Cada lance supera o anterior pelo incremento mínimo.</p>
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
