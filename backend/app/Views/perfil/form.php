<?php
/**
 * Meu perfil (aba nova): como o artista aparece na sala, nas obras e nos leilões.
 * À esquerda, a prévia (retrato na moldura, papel de parede, nome); à direita, os campos.
 * O app.js atualiza a prévia na hora em que a pessoa troca foto, moldura, papel de parede ou nome.
 * Variáveis: $perfil (linha do usuário + o que foi digitado antes de um erro), $redes, $especialidades
 */
use App\Core\Csrf;
use App\Core\View;
use App\Models\RedeSocial;
use App\Models\Usuario;

$v = fn(string $campo): string => e((string) ($perfil[$campo] ?? ''));
$moldura = isset(Usuario::MOLDURAS[$perfil['molduraFoto'] ?? '']) ? $perfil['molduraFoto'] : 'camafeu';
$temFoto = (bool) imagemLocal($perfil['fotoPerfil'] ?? null);
$wallpaper = imagemLocal($perfil['wallpaper'] ?? null);
?>
  <main id="conteudo">
    <section class="leiloes-cab envolve" aria-labelledby="perfil-titulo">
      <p class="rotulo">Sua sala</p>
      <h1 id="perfil-titulo">Meu <span>perfil</span></h1>
      <p>Como você aparece para quem visita a sua sala, as suas obras e os seus leilões.</p>
    </section>

    <section class="envolve" aria-label="Editar perfil">
      <form class="perfil" method="post" action="<?= e(url('perfil.save')) ?>" enctype="multipart/form-data" data-perfil>
        <?= Csrf::field() ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= \App\Core\Upload::LIMITE ?>">

        <aside class="perfil__previa" aria-label="Prévia do perfil">
          <p class="legenda">Prévia</p>
          <div class="perfil__fundo<?= $wallpaper ? ' perfil__fundo--imagem' : '' ?>" data-previa-wallpaper>
            <div class="perfil__fundo-imagem" data-previa-wallpaper-imagem<?= $wallpaper ? ' style="background-image:url(\'' . e($wallpaper) . '\')"' : '' ?>></div>
            <?php View::partial('retrato', ['pessoa' => ['molduraFoto' => $moldura] + $perfil, 'classe' => 'retrato--previa']); ?>
          </div>
          <p class="perfil__nome" data-previa-nome><?= $v('nome') ?></p>
          <p class="cartaz__especialidade" data-previa-especialidade><?= $v('especialidade') ?></p>
          <p class="cartaz__apelido" data-previa-apelido<?= ($perfil['apelido'] ?? '') === '' ? ' hidden' : '' ?>><small>apelido</small> “<span><?= $v('apelido') ?></span>”</p>
          <p class="campo__ajuda"><a href="<?= e(url('perfil')) ?>">Ver meu perfil</a> · <a href="<?= e(url('password')) ?>">Trocar minha senha</a></p>
        </aside>

        <div class="perfil__campos">
          <fieldset class="cartao-form perfil__bloco">
            <legend class="legenda">Foto de perfil</legend>
            <div class="campo">
              <label for="foto">Escolher foto (PNG, JPG ou WEBP, até 10 MB)</label>
              <input id="foto" name="foto" type="file" accept="image/png,image/jpeg,image/webp" data-previa-foto>
            </div>
            <?php if ($temFoto): ?>
              <label class="marcar">
                <input type="checkbox" name="removerFoto" value="1" data-remover-foto>
                <span>Remover a foto atual<small>A sala volta a mostrar as suas iniciais.</small></span>
              </label>
            <?php endif; ?>

            <fieldset class="escolha" style="margin-bottom:0">
              <legend class="legenda">Moldura da foto</legend>
              <div class="molduras">
                <?php foreach (Usuario::MOLDURAS as $chave => [$nome, $classe]): ?>
                  <label class="moldura-opcao">
                    <input type="radio" name="molduraFoto" value="<?= e($chave) ?>" data-classe="<?= e($classe) ?>"<?= $moldura === $chave ? ' checked' : '' ?>>
                    <?php View::partial('retrato', ['pessoa' => ['molduraFoto' => $chave] + $perfil, 'classe' => 'retrato--mini', 'decorativa' => true]); ?>
                    <span><?= e($nome) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>
          </fieldset>

          <fieldset class="cartao-form perfil__bloco">
            <legend class="legenda">Quem é você</legend>
            <div class="campo">
              <label for="nome">Nome artístico</label>
              <input id="nome" name="nome" type="text" maxlength="255" required autocomplete="nickname" value="<?= $v('nome') ?>" data-previa="nome">
            </div>
            <div class="campos-dupla">
              <div class="campo">
                <label for="apelido">Apelido</label>
                <input id="apelido" name="apelido" type="text" maxlength="60" placeholder="Ex.: Migs" value="<?= $v('apelido') ?>" data-previa="apelido">
              </div>
              <div class="campo">
                <label for="especialidade">Especialidade</label>
                <input id="especialidade" name="especialidade" type="text" maxlength="80" list="lista-especialidades" placeholder="Ex.: Pintura digital" value="<?= $v('especialidade') ?>" data-previa="especialidade">
                <datalist id="lista-especialidades">
                  <?php foreach ($especialidades as $esp): ?><option value="<?= e($esp) ?>"><?php endforeach; ?>
                </datalist>
              </div>
            </div>
            <div class="campo" style="margin-bottom:0">
              <label for="estilos">Estilos</label>
              <input id="estilos" name="estilos" type="text" maxlength="300" placeholder="Ex.: Surrealismo, Pop art, Retrato" value="<?= $v('estilos') ?>" aria-describedby="estilos-ajuda">
              <p class="campo__ajuda" id="estilos-ajuda">Separe por vírgulas. Até <?= Usuario::MAX_ESTILOS ?> estilos; eles aparecem como etiquetas na sua sala.</p>
            </div>
          </fieldset>

          <fieldset class="cartao-form perfil__bloco">
            <legend class="legenda">Papel de parede da sala</legend>
            <div class="campo">
              <label for="wallpaper">Escolher imagem (fica atrás do seu nome, no alto da sala)</label>
              <input id="wallpaper" name="wallpaper" type="file" accept="image/png,image/jpeg,image/webp" data-previa-wallpaper-arquivo>
            </div>
            <?php if ($wallpaper): ?>
              <label class="marcar" style="margin-bottom:0">
                <input type="checkbox" name="removerWallpaper" value="1" data-remover-wallpaper>
                <span>Remover o papel de parede atual<small>A sala volta ao papel de parede listrado do museu.</small></span>
              </label>
            <?php endif; ?>
          </fieldset>

          <fieldset class="cartao-form perfil__bloco">
            <legend class="legenda">Contato para vendas</legend>
            <div class="campo" style="margin-bottom:10px">
              <label for="contatoVenda">WhatsApp ou telefone (opcional)</label>
              <input id="contatoVenda" name="contatoVenda" type="tel" maxlength="40" inputmode="tel" autocomplete="tel" placeholder="(47) 99999-1234" value="<?= $v('contatoVenda') ?>" aria-describedby="contato-ajuda">
            </div>
            <p class="campo__ajuda" id="contato-ajuda" style="margin:0">Nunca fica público. Quando um leilão termina, quem arrematou e quem vendeu recebem uma notificação com o <b>e-mail da conta</b>, este número e as redes um do outro, para combinarem a entrega.</p>
          </fieldset>

          <fieldset class="cartao-form perfil__bloco">
            <legend class="legenda">Redes sociais</legend>
            <p class="campo__ajuda" style="margin:0 0 18px">Digite o <b>@usuário</b> ou cole o link. Deixe em branco a rede que você não usa.</p>
            <div class="campos-dupla">
              <?php foreach (RedeSocial::REDES as $rede => [$nome, $prefixo]): ?>
                <div class="campo">
                  <label for="rede-<?= e($rede) ?>"><?= e($nome) ?></label>
                  <input id="rede-<?= e($rede) ?>" name="rede[<?= e($rede) ?>]" type="text" maxlength="255" inputmode="url" autocomplete="off" placeholder="<?= $prefixo ? '@seuperfil' : 'https://seusite.com' ?>" value="<?= e((string) ($redes[$rede] ?? '')) ?>">
                </div>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <button class="botao botao--tinta botao--largo" type="submit">Salvar perfil</button>
        </div>
      </form>
    </section>
  </main>
