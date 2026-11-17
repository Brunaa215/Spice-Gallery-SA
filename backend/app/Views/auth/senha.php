<?php
/** Trocar a própria senha (igual ao "Minha senha" do CRUD). */
use App\Core\Csrf;
?>
  <main id="conteudo" class="porta">
    <div class="porta__arte">
      <blockquote>
        <p>Uma boa moldura protege a obra. Uma boa senha protege a sala.</p>
        <cite>Curadoria Spice</cite>
      </blockquote>
    </div>

    <div class="porta__form">
      <div class="porta__caixa" data-cascata>
        <p class="rotulo">Sua conta</p>
        <h1 class="secao__titulo" style="margin:0 0 30px">Minha senha</h1>
        <form method="post" action="<?= e(url('password.save')) ?>">
          <?= Csrf::field() ?>
          <div class="campo olho">
            <label for="atual">Senha atual</label>
            <input id="atual" name="atual" type="password" autocomplete="current-password" required>
            <button type="button" aria-pressed="false" aria-label="Mostrar senha">Mostrar</button>
          </div>
          <div class="campo olho">
            <label for="nova">Nova senha</label>
            <input id="nova" name="nova" type="password" minlength="8" autocomplete="new-password" required>
            <button type="button" aria-pressed="false" aria-label="Mostrar senha">Mostrar</button>
          </div>
          <button class="botao botao--tinta botao--largo" type="submit">Trocar senha</button>
        </form>
      </div>
    </div>
  </main>
