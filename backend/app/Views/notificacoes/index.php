<?php
/**
 * Aba "Notificações" (nova): avisos de fim de leilão. Nos de compra e venda aparece o contato da outra
 * parte (e-mail da conta, WhatsApp/telefone do perfil e redes) para combinarem a entrega.
 * Variáveis: $avisos (Notificacao::doUsuario, com lidaEm de antes desta visita), $redes ([id => [rede => url]])
 */
use App\Models\Notificacao;
use App\Models\RedeSocial;

$novas = count(array_filter($avisos, fn($a) => $a['lidaEm'] === null));
$rotulos = [
    Notificacao::ARREMATE => 'Você arrematou',
    Notificacao::VENDA => 'Obra vendida',
    Notificacao::NAO_VENCEU => 'Leilão encerrado',
    Notificacao::SEM_LANCES => 'Sem lances',
];
?>
  <main id="conteudo">
    <section class="leiloes-cab envolve" aria-labelledby="notificacoes-titulo">
      <p class="rotulo">Recados da casa<?= $novas ? ' · ' . $novas . ($novas === 1 ? ' nova' : ' novas') : '' ?></p>
      <h1 id="notificacoes-titulo">Notifi<span>cações</span></h1>
      <p>Quando um leilão em que você participa termina, o aviso chega aqui. Quem arremata recebe o contato do artista, e o artista recebe o contato de quem arrematou, para combinarem a entrega.</p>
    </section>

    <section class="secao envolve" aria-label="Notificações" style="padding-top:0">
      <?php if (!$avisos): ?>
        <p class="vazio">Nenhuma notificação ainda. Que tal dar uma olhada nos <a href="<?= e(url('leiloes')) ?>">leilões</a>?</p>
      <?php else: ?>
        <ol class="avisos">
          <?php foreach ($avisos as $a): ?>
            <?php
              [$titulo, $mensagem] = Notificacao::texto($a);
              $nova = $a['lidaEm'] === null;
              $temContato = $a['id_contato'] && $a['contatoNome'];
              $assunto = rawurlencode('Spice Gallery: obra “' . $a['obra'] . '”');
              $zap = whatsapp($a['contatoVenda']);
            ?>
            <li class="aviso-leilao aviso-leilao--<?= e(strtolower($a['tipo'])) ?><?= $nova ? ' aviso-leilao--nova' : '' ?>">
              <div class="aviso-leilao__cab">
                <span class="aviso-leilao__tipo"><?= e($rotulos[$a['tipo']] ?? 'Aviso') ?></span>
                <?php if ($nova): ?><span class="aviso-leilao__nova">Nova</span><?php endif; ?>
                <time datetime="<?= dataIso($a['criadaEm']) ?>"><?= quando($a['criadaEm']) ?></time>
              </div>
              <h2 class="aviso-leilao__titulo"><?= e($titulo) ?></h2>
              <p class="aviso-leilao__texto"><?= e($mensagem) ?></p>

              <?php if ($temContato): ?>
                <div class="contato">
                  <p class="legenda"><?= $a['tipo'] === Notificacao::ARREMATE ? 'Contato do artista' : 'Contato de quem arrematou' ?></p>
                  <a class="ficha__artista" href="<?= e(url('artistas.show', ['id' => $a['id_contato']])) ?>">
                    <?= avatar($a['id_contato'], $a['contatoNome'], $a['contatoFoto']) ?>
                    <span><strong><?= e($a['contatoNome']) ?></strong><small>Ver a sala</small></span>
                  </a>
                  <dl class="contato__lista">
                    <div><dt>E-mail</dt><dd><a href="mailto:<?= e($a['contatoEmail']) ?>?subject=<?= e($assunto) ?>"><?= e($a['contatoEmail']) ?></a></dd></div>
                    <?php if ($a['contatoVenda']): ?>
                      <div><dt>WhatsApp / telefone</dt><dd><?= e($a['contatoVenda']) ?><?php if ($zap): ?> · <a href="<?= e($zap) ?>" target="_blank" rel="noopener noreferrer">abrir no WhatsApp</a><?php endif; ?></dd></div>
                    <?php endif; ?>
                    <?php foreach ($redes[$a['id_contato']] ?? [] as $rede => $link): ?>
                      <div><dt><?= e(RedeSocial::REDES[$rede][0]) ?></dt><dd><a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer"><?= e(preg_replace('#^https?://(www\.)?#', '', $link)) ?></a></dd></div>
                    <?php endforeach; ?>
                  </dl>
                </div>
              <?php endif; ?>

              <p class="aviso-leilao__links">
                <?php if ($a['id_leilao']): ?><a class="link-seta" href="<?= e(url('leiloes.show', ['id' => $a['id_leilao']])) ?>">Ver o leilão</a><?php endif; ?>
                <?php if ($a['tipo'] === Notificacao::ARREMATE): ?><a class="link-seta" href="<?= e(url('artistas.show', ['id' => $a['id_usuario']])) ?>#colecao">Ver na sua coleção</a><?php endif; ?>
                <?php if ($a['tipo'] === Notificacao::SEM_LANCES && $a['id_obra']): ?><a class="link-seta" href="<?= e(url('obras.edit', ['id' => $a['id_obra']])) ?>">Leiloar de novo</a><?php endif; ?>
                <?php if (in_array($a['tipo'], [Notificacao::VENDA, Notificacao::NAO_VENCEU], true)): ?><a class="link-seta" href="<?= e(url('carteira')) ?>">Ver a carteira</a><?php endif; ?>
              </p>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
  </main>
