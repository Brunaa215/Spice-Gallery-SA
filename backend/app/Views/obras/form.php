<?php
/**
 * Publicar / editar obra (era publicar.html). O mesmo formulário serve para os dois:
 * id vazio = nova obra; id preenchido = edição (a imagem passa a ser opcional).
 * Variáveis: $obra (valores do formulário), $editando, $leilao (?array, leilão já existente), $categorias, $unidades
 */
use App\Core\Csrf;
use App\Models\Leilao;

$v = fn(string $campo): string => e((string) ($obra[$campo] ?? ''));
$temImagem = !empty($obra['urlImagem']);
$querLeilao = ($obra['disponivelLeilao'] ?? '') === '1';
// Leilão que ainda não começou (marcado para depois): os campos vêm preenchidos e podem ser alterados (ou o leilão cancelado).
$pendente = $leilao && Leilao::editavel($leilao);
$naData = ($obra['inicioModo'] ?? 'agora') === 'data';
?>
  <main id="conteudo">
    <form class="atelie envolve" method="post" action="<?= e(url('obras.save')) ?>" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" value="<?= $v('id') ?>">
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= \App\Core\Upload::LIMITE ?>">

      <label class="soltar<?= $temImagem ? ' tem-imagem' : '' ?>" data-soltar>
        <input type="file" name="imagem" accept="image/png,image/jpeg,image/webp"<?= $editando ? '' : ' required' ?>>
        <span class="soltar__vazio"<?= $temImagem ? ' hidden' : '' ?>>
          <svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="8" y="14" width="64" height="52"/><rect x="16" y="22" width="48" height="36" stroke-dasharray="4 4"/><path d="M40 50V30M32 37l8-8 8 8"/><circle cx="40" cy="6" r="2.5" fill="currentColor"/><path d="M40 8l-14 6M40 8l14 6"/></svg>
          <strong>Solte sua obra aqui</strong>
          <span>ou clique para escolher. PNG, JPG ou WEBP, até 10 MB.</span>
        </span>
        <span class="quadro quadro--ouro quadro--pendurado"<?= $temImagem ? '' : ' hidden' ?>>
          <span class="quadro__passe" style="display:block"><img <?= $temImagem ? 'src="' . $v('urlImagem') . '" ' : '' ?>alt="Prévia da obra escolhida"></span>
        </span>
      </label>

      <div data-cascata>
        <p class="rotulo">Ateliê · <?= $editando ? 'editar ' . numeroObra($obra['id']) : 'nova obra' ?></p>
        <h1 class="atelie__titulo"><?= $editando ? 'Retoque sua obra.' : 'Pendure sua obra.' ?></h1>
        <p class="secao__nota" style="margin-bottom:38px">
          <?= $editando
            ? 'Mude o que quiser. Se não escolher uma imagem nova, a atual continua na parede.'
            : 'Toda obra precisa de título, descrição, categoria e imagem. Depois de publicada, ela ganha um número de catálogo e uma parede só dela.' ?>
        </p>

        <div class="campo">
          <label for="titulo">Título</label>
          <input id="titulo" name="titulo" type="text" maxlength="255" required autocomplete="off" placeholder="Ex.: Sol de Açafrão" value="<?= $v('titulo') ?>">
        </div>
        <div class="campo">
          <label for="descricao">Descrição</label>
          <textarea id="descricao" name="descricao" maxlength="2000" required placeholder="Conte de onde a obra veio, técnica, inspiração..."><?= $v('descricao') ?></textarea>
        </div>
        <div class="campo">
          <label for="categoria">Categoria</label>
          <select id="categoria" name="categoria" required>
            <option value="">Escolha uma categoria</option>
            <?php foreach ($categorias as $chave => $nome): ?>
              <option value="<?= e($chave) ?>"<?= ($obra['categoria'] ?? '') === $chave ? ' selected' : '' ?>><?= e($chave === 'digital' ? 'Ilustração / pintura digital' : $nome) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <label class="marcar" id="termos">
          <input type="checkbox" name="aceiteTermos" value="1" required<?= $editando ? ' checked' : '' ?>>
          <span>Declaro que sou autor(a) desta obra e aceito os termos de direitos autorais.
            <small>A obra continua sua. O Spice Gallery só exibe e, se você quiser, leiloa.</small></span>
        </label>

        <?php if ($leilao && Leilao::travado($leilao)): ?>
          <p class="aviso">Esta obra já tem um leilão: <b><?= e($leilao['id_vencedor'] ? 'vendida' : Leilao::estado($leilao)) ?></b>. <a href="<?= e(url('leiloes.show', ['id' => $leilao['id']])) ?>">Ver leilão</a></p>
        <?php else: ?>
          <?php if ($leilao && !$pendente): ?>
            <p class="aviso">O último leilão desta obra fechou sem lances. Você pode colocá-la em leilão de novo.</p>
          <?php endif; ?>
          <label class="marcar">
            <?php if ($pendente): ?><input type="hidden" name="leilaoAgendado" value="<?= (int) $leilao['id'] ?>"><?php endif; ?>
            <input type="checkbox" name="disponivelLeilao" value="1"<?= $querLeilao ? ' checked' : '' ?>>
            <span><?= $pendente ? 'Leilão marcado (ainda não começou)' : 'Disponível para leilão' ?>
              <small><?= $pendente
                ? 'Enquanto ele não começa, dá para alterar o que quiser abaixo, ou desmarcar para cancelar o leilão.'
                : 'Só obras exclusivas. O leilão começa ao salvar ou na data que você marcar.' ?></small></span>
          </label>

          <fieldset class="leilao-campos"<?= $querLeilao ? '' : ' hidden disabled' ?>>
            <legend class="sr-only">Dados do leilão</legend>
            <div class="campos-dupla">
              <div class="campo">
                <label for="valorInicial">Lance inicial (R$)</label>
                <input id="valorInicial" name="valorInicial" type="number" min="1" step="1" inputmode="numeric" required placeholder="600" value="<?= $v('valorInicial') ?>">
              </div>
              <div class="campo">
                <label for="incremento">Incremento mínimo (R$)</label>
                <input id="incremento" name="incremento" type="number" min="1" step="1" inputmode="numeric" required placeholder="50" value="<?= $v('incremento') ?>">
              </div>
            </div>

            <!-- .escolha e não .campo: dentro de .campo, todo <label> vira rótulo em maiúsculas -->
            <fieldset class="escolha">
              <legend class="legenda">Quando começa</legend>
              <label class="marcar">
                <input type="radio" name="inicioModo" value="agora"<?= $naData ? '' : ' checked' ?>>
                <span>Começar agora<small>O leilão abre assim que você salvar e o cronômetro começa na hora.</small></span>
              </label>
              <label class="marcar">
                <input type="radio" name="inicioModo" value="data"<?= $naData ? ' checked' : '' ?>>
                <span>Em data e hora marcadas<small>O leilão abre sozinho na data e hora escolhidas. Até lá, dá para alterar ou cancelar.</small></span>
              </label>
              <div class="campo" data-inicio-data<?= $naData ? '' : ' hidden' ?> style="margin-bottom:0">
                <label for="dataInicio">Início</label>
                <input id="dataInicio" name="dataInicio" type="datetime-local" required value="<?= $v('dataInicio') ?>"<?= $naData ? '' : ' disabled' ?>>
              </div>
            </fieldset>

            <div class="campos-dupla">
              <div class="campo">
                <label for="duracao">Duração</label>
                <input id="duracao" name="duracao" type="number" min="1" step="1" inputmode="numeric" required placeholder="10" value="<?= $v('duracao') ?>">
              </div>
              <div class="campo">
                <label for="unidade">Em</label>
                <select id="unidade" name="unidade" required>
                  <?php foreach (array_keys($unidades) as $unidade): ?>
                    <option value="<?= e($unidade) ?>"<?= ($obra['unidade'] ?? 'minutos') === $unidade ? ' selected' : '' ?>><?= e($unidade) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <p class="campo__ajuda" style="margin:-8px 0 22px">De 1 minuto a <?= duracao(Leilao::DURACAO_MAXIMA) ?>. Um leilão de teste pode durar só alguns minutos. Se a obra for arrematada, você e quem arrematou recebem uma notificação com o e-mail e o WhatsApp (do perfil, se houver) um do outro.</p>
          </fieldset>
        <?php endif; ?>

        <button class="botao botao--tinta botao--largo" type="submit"><?= $editando ? 'Salvar alterações' : 'Publicar na galeria' ?></button>
        <?php if ($editando): ?>
          <p style="margin-top:18px"><a class="link-seta" href="<?= e(url('obras.show', ['id' => $obra['id']])) ?>">Voltar para a obra</a></p>
        <?php endif; ?>
      </div>
    </form>
  </main>
