<?php
/**
 * Cabeçalho comum a todas as páginas (era o <header class="topo"> repetido nos .html).
 * Variáveis: $title, $description (opcional), $menu (item do menu marcado), $current, $isAdmin, $flash
 */
$menu = $menu ?? '';
$marcar = fn(string $item): string => $menu === $item ? ' aria-current="page"' : '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(!empty($title) ? $title . ' · Spice Gallery' : 'Spice Gallery — Museu online de jovens artistas') ?></title>
  <meta name="description" content="<?= e($description ?? 'Spice Gallery: um museu online onde jovens artistas publicam suas obras, recebem avaliações e participam de leilões.') ?>">
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Marcellus+SC&family=Pinyon+Script&display=swap">
  <link rel="stylesheet" href="assets/css/style.css">
  <script>document.documentElement.classList.add('js')</script>
</head>
<body>
  <a class="pular" href="#conteudo">Pular para o conteúdo</a>
  <div class="cortina" aria-hidden="true"><div class="cortina__painel"></div><div class="cortina__painel"></div><p class="cortina__marca">Spice</p><span class="cortina__contador">000</span></div>

  <?php if ($flash): ?>
    <!-- Mensagem do servidor: o app.js mostra no aviso flutuante (toast) -->
    <div data-aviso="<?= e($flash['message']) ?>" hidden></div>
    <noscript><p class="aviso envolve"><?= e($flash['message']) ?></p></noscript>
  <?php endif; ?>

  <header class="topo">
    <div class="topo__interno envolve">
      <a class="marca" href="<?= e(url('home')) ?>" aria-label="Spice Gallery, início">
        <span class="marca__nome">Spice<i>.</i></span>
        <span class="marca__sub">Gallery<br>museu online</span>
      </a>
      <button class="topo__menu" type="button" aria-expanded="false" aria-controls="nav">Menu</button>
      <nav class="nav" id="nav" aria-label="Principal">
        <a href="<?= e(url('home')) ?>#galeria"<?= $marcar('galeria') ?>>Galeria</a>
        <a href="<?= e(url('artistas')) ?>"<?= $marcar('artistas') ?>>Artistas</a>
        <a href="<?= e(url('leiloes')) ?>"<?= $marcar('leiloes') ?>>Leilões<span class="ao-vivo" aria-hidden="true"></span></a>
        <?php if ($current): ?>
          <a href="<?= e(url('artistas.show', ['id' => $current['id']])) ?>"<?= $marcar('minha-sala') ?>>Minha sala</a>
          <?php if ($isAdmin): ?>
            <a href="<?= e(url('curadoria')) ?>"<?= $marcar('curadoria') ?>>Curadoria</a>
          <?php endif; ?>
          <form class="nav__sair" method="post" action="<?= e(url('logout')) ?>">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit">Sair</button>
          </form>
        <?php else: ?>
          <a href="<?= e(url('login')) ?>"<?= $marcar('entrar') ?>>Entrar</a>
        <?php endif; ?>
        <a class="botao botao--tinta botao--sem-seta" href="<?= e(url('obras.create')) ?>">Publicar arte</a>
      </nav>
    </div>
  </header>
