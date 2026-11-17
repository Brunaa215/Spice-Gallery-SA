<?php
/**
 * Entrar e criar conta, em abas (era entrar.html). Abre na aba "Criar conta" com #cadastro.
 * Variáveis: $old (o que foi digitado antes de um erro)
 */
use App\Core\Csrf;
?>
  <main id="conteudo" class="porta">
    <div class="porta__arte">
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
        </form>
      </div>
    </div>
  </main>
