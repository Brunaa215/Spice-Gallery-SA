<?php
/**
 * Fila da curadoria (RN-07): aprovar ou reprovar leilões. Só ADMIN.
 * Variáveis: $pendentes
 */
use App\Core\Csrf;
?>
  <main id="conteudo">
    <section class="leiloes-cab envolve" aria-labelledby="curadoria-titulo">
      <p class="rotulo">Bastidores</p>
      <h1 id="curadoria-titulo">Cura<span>doria</span></h1>
      <p>Leilões esperando aprovação. Os marcados "ao aprovar" começam na hora em que você aprova; os outros abrem sozinhos na data de início.</p>
      <p><a class="link-seta" href="<?= e(url('usuarios')) ?>">Gerenciar contas</a></p>
    </section>

    <section class="secao envolve" aria-label="Leilões aguardando curadoria" style="padding-top:clamp(30px,4vw,60px)">
      <?php if (!$pendentes): ?>
        <p class="vazio">Nenhum leilão esperando a curadoria.</p>
      <?php else: ?>
        <div class="tabela-rolagem">
          <table class="tabela">
            <thead><tr><th>Lote</th><th>Obra</th><th>Artista</th><th>Inicial</th><th>Incremento</th><th>Início</th><th>Duração</th><th>Decisão</th></tr></thead>
            <tbody>
              <?php foreach ($pendentes as $l): ?>
                <?php
                  $abre = (int) $l['abreNaAprovacao'];
                  $vencido = !$abre && strtotime($l['dataFim']) <= time();
                ?>
                <tr>
                  <td><a href="<?= e(url('leiloes.show', ['id' => $l['id']])) ?>"><?= numeroLote($l['id']) ?></a></td>
                  <td><a href="<?= e(url('obras.show', ['id' => $l['id_obra']])) ?>"><?= e($l['titulo']) ?></a></td>
                  <td><?= e($l['autor']) ?></td>
                  <td><?= brl($l['valorInicial']) ?></td>
                  <td><?= brl($l['incremento']) ?></td>
                  <td><?= $abre ? 'Ao aprovar' : dataHora($l['dataInicio']) ?><?= $vencido ? ' <small>(prazo passou)</small>' : '' ?></td>
                  <td><?= duracao(\App\Models\Leilao::duracaoMinutos($l)) ?></td>
                  <td>
                    <div class="comentario__acoes" style="margin:0">
                      <?php if (!$vencido): ?>
                        <form method="post" action="<?= e(url('curadoria.leilao')) ?>">
                          <?= Csrf::field() ?>
                          <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                          <input type="hidden" name="decisao" value="aprovar">
                          <button class="texto-botao" type="submit"><?= $abre || strtotime($l['dataInicio']) <= time() ? 'Aprovar e começar' : 'Aprovar' ?></button>
                        </form>
                      <?php endif; ?>
                      <form method="post" action="<?= e(url('curadoria.leilao')) ?>" data-confirmar="Reprovar este leilão?">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                        <input type="hidden" name="decisao" value="reprovar">
                        <button class="texto-botao" type="submit">Reprovar</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
