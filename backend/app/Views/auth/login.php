<?php
/**
 * Entrar e criar conta, em abas (era entrar.html). Abre na aba "Criar conta" com #cadastro.
 * Variáveis: $old (o que foi digitado antes de um erro),
 *            $passarela (obras publicadas que passam no painel da esquerda; vazio = imagem fixa)
 */
use App\Core\Csrf;
use App\Models\Obra;

$passarela = $passarela ?? [];
?>
  <main id="conteudo" class="porta">
    <div class="porta__arte<?= $passarela ? ' porta__arte--obras' : '' ?>"<?= count($passarela) > 1 ? ' data-passarela' : '' ?>>
      <?php foreach ($passarela as $i => $o): ?>
        <!-- a primeira já aparece (sem JS ou com "reduzir movimento" fica só ela); o app.js faz as outras passarem -->
        <figure class="porta__obra<?= $i === 0 ? ' vista' : '' ?>"<?= $i ? ' aria-hidden="true"' : '' ?>>
          <img class="porta__fundo" src="<?= e($o['urlImagem']) ?>" alt="" decoding="async">
          <!-- a mesma moldura que a obra tem na galeria (ouro, laca verde, laca rosa ou camafeu), pendurada num prego -->
          <!-- .porta__tela desliza (passarela); o .quadro dentro dele balança no prego ao passar o mouse -->
          <div class="porta__tela">
            <div class="quadro <?= moldura($o['id']) ?> quadro--pendurado">
              <div class="quadro__passe"><img src="<?= e($o['urlImagem']) ?>" alt="<?= e($o['titulo'] . ', de ' . $o['autor']) ?>" decoding="async"></div>
            </div>
          </div>
          <figcaption class="porta__legenda">
            <span><?= numeroObra($o['id']) ?> · <?= e(Obra::categoria($o['categoria'])) ?></span>
            <strong><?= e($o['titulo']) ?></strong>
            <em><?= e($o['autor']) ?></em>
          </figcaption>
        </figure>
      <?php endforeach; ?>
      <blockquote>
        <p>A primeira parede é a mais difícil de encontrar.</p>
        <cite>Rafa Quintino, 22 · autor de Noite de Cravo</cite>
      </blockquote>
    </div>

    <div class="porta__form">
      <div class="porta__caixa" data-cascata>
        <p class="rotulo">Entrada dos artistas</p>
        <div class="abas" role="tablist" aria-label="Acesso">
          <button class="aba" type="button" role="tab" id="aba-entrar" aria-selected="true" aria-controls="painel-entrar">Entrar</button>
          <button class="aba" type="button" role="tab" id="aba-cadastro" aria-selected="false" aria-controls="painel-cadastro" tabindex="-1">Criar conta</button>
        </div>

        <form id="painel-entrar" role="tabpanel" aria-labelledby="aba-entrar" method="post" action="<?= e(url('login.submit')) ?>">
          <?= Csrf::field() ?>
          <div class="campo">
            <label for="login-email">E-mail</label>
            <input id="login-email" name="email" type="email" autocomplete="email" required value="<?= e($old['login_email'] ?? '') ?>">
          </div>
          <div class="campo olho">
            <label for="login-senha">Senha</label>
            <input id="login-senha" name="senha" type="password" autocomplete="current-password" required>
            <button type="button" aria-pressed="false" aria-label="Mostrar senha">Mostrar</button>
          </div>
          <button class="botao botao--tinta botao--largo" type="submit">Entrar</button>
        </form>

        <form id="painel-cadastro" role="tabpanel" aria-labelledby="aba-cadastro" method="post" action="<?= e(url('register')) ?>" hidden>
          <?= Csrf::field() ?>
          <div class="campo">
            <label for="cad-nome">Nome artístico</label>
            <input id="cad-nome" name="nome" type="text" maxlength="255" autocomplete="name" required value="<?= e($old['nome'] ?? '') ?>">
          </div>
          <div class="campo">
            <label for="cad-email">E-mail</label>
            <input id="cad-email" name="email" type="email" maxlength="255" autocomplete="email" required value="<?= e($old['email'] ?? '') ?>">
          </div>
          <div class="campo olho">
            <label for="cad-senha">Senha</label>
            <input id="cad-senha" name="senha" type="password" minlength="8" autocomplete="new-password" required aria-describedby="cad-senha-ajuda">
            <button type="button" aria-pressed="false" aria-label="Mostrar senha">Mostrar</button>
          </div>
          <p class="campo__ajuda" id="cad-senha-ajuda" style="margin:-12px 0 22px">Pelo menos 8 caracteres.</p>
          <label class="marcar">
            <input type="checkbox" name="aceiteTermos" value="1" required>
            <span>Aceito os termos de uso e de direitos autorais.<small>Obrigatório para publicar obras.</small></span>
          </label>
          <button class="botao botao--tinta botao--largo" type="submit">Criar conta de artista</button>
          <p class="campo__ajuda" style="margin-top:14px">Toda conta nova ganha <?= brl(\App\Models\Movimentacao::BONUS_BOAS_VINDAS) ?> em créditos fictícios para dar lances nos leilões.</p>
        </form>
      </div>
    </div>
  </main>
