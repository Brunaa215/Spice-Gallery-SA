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

  <script src="assets/js/vendor/gsap.min.js" defer></script>
  <script src="assets/js/vendor/ScrollTrigger.min.js" defer></script>
  <script src="assets/js/vendor/SplitText.min.js" defer></script>
  <script src="assets/js/vendor/Flip.min.js" defer></script>
  <script src="assets/js/vendor/ScrambleTextPlugin.min.js" defer></script>
  <script src="assets/js/vendor/lenis.min.js" defer></script>
  <script src="assets/js/movimento.js" defer></script>
  <script src="assets/js/app.js" defer></script>
</body>
</html>
