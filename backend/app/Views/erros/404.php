<?php /** Variáveis: $mensagem */ ?>
  <main id="conteudo">
    <section class="chamada envolve" aria-labelledby="erro-titulo">
      <p class="rotulo">Erro 404</p>
      <h2 class="chamada__titulo" id="erro-titulo"><?= e($mensagem) ?></h2>
      <a class="botao botao--tinta" href="<?= e(url('home')) ?>">Voltar para a galeria</a>
    </section>
  </main>
