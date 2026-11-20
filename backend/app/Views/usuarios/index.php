<?php
/**
 * Contas (igual a users/index do CRUD): a curadoria ativa ou desativa contas.
 * Conta desativada não entra e some da galeria (obras, avaliações e leilões).
 * Variáveis: $usuarios
 */
use App\Core\Csrf;
?>
  <main id="conteudo">
    <section class="leiloes-cab envolve" aria-labelledby="contas-titulo">
      <p class="rotulo">Bastidores</p>
      <h1 id="contas-titulo">Con<span>tas</span></h1>
      <p><a class="link-seta" href="<?= e(url('curadoria')) ?>">Voltar para a curadoria</a></p>
    </section>

    <section class="secao envolve" aria-label="Contas" style="padding-top:clamp(30px,4vw,60px)">
      <div class="tabela-rolagem">
        <table class="tabela">
          <thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Créditos</th><th>Desde</th><th>Situação</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
              <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><a href="<?= e(url('artistas.show', ['id' => $u['id']])) ?>"><?= e($u['nome']) ?></a></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['perfil']) ?></td>
                <td><?= brl($u['saldo']) ?></td>
                <td><?= dataCurta($u['criadoEm']) ?></td>
                <td><?= (int) $u['ativo'] ? 'Ativa' : 'Desativada' ?></td>
                <td>
                  <?php if ((int) $u['id'] !== (int) $current['id']): ?>
                    <form method="post" action="<?= e(url('usuarios.toggle')) ?>" style="margin:0">
                      <?= Csrf::field() ?>
                      <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                      <button class="texto-botao" type="submit"><?= (int) $u['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
