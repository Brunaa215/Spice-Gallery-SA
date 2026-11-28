  <footer class="rodape">
    <div class="envolve">
      <div class="rodape__grade">
        <div>
          <h2>Um museu sem medo de julgamento.</h2>
          <p>Spice Gallery é um museu online onde jovens artistas publicam, recebem avaliações e levam suas obras a leilão.</p>
        </div>
        <div>
          <h3>Visitar</h3>
          <ul><li><a href="<?= e(url('home')) ?>#galeria">Galeria</a></li><li><a href="<?= e(url('artistas')) ?>">Artistas</a></li><li><a href="<?= e(url('leiloes')) ?>">Leilões</a></li></ul>
        </div>
        <div>
          <h3>Criar</h3>
          <ul><li><a href="<?= e(url('obras.create')) ?>">Publicar arte</a></li><li><a href="<?= e(url('login')) ?>#cadastro">Criar conta</a></li><li><a href="<?= e(url('login')) ?>">Entrar</a></li></ul>
        </div>
        <div>
          <h3>A casa</h3>
          <ul><li><a href="<?= e(url('home')) ?>#regras">Regras da casa</a></li><li><a href="<?= e(url('obras.create')) ?>#termos">Direitos autorais</a></li><li><a href="<?= e(url('leiloes')) ?>#como-funciona">Como funciona o leilão</a></li></ul>
        </div>
      </div>
      <p class="rodape__marca" aria-hidden="true">Spice</p>
      <div class="rodape__base"><span>© <?= date('Y') ?> Spice Gallery · Projeto SA, SENAI Joinville</span><span>Miguel · Bruna · Uriel</span></div>
    </div>
  </footer>

  <!-- Sem a camada de animação (GSAP, Lenis, movimento.js): o site fica parado e a atenção vai para as obras.
       Os arquivos continuam em assets/js porque o protótipo estático (frontend/prototipo) ainda usa. -->
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
