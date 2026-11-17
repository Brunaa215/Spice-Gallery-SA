/* Spice Gallery: camada de movimento (GSAP + Lenis).
   Só decoração: se este arquivo ou o GSAP faltar, o site funciona igual, sem animação.
   Expõe window.SpiceMov para o app.js chamar efeitos (lance, nota, upload, filtro). */
(() => {
  'use strict';

  const html = document.documentElement;
  const cortina = document.querySelector('.cortina');
  const reduz = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fino = matchMedia('(hover: hover) and (pointer: fine)').matches;
  // máquina modesta (até 4 núcleos): sem poeira nem ondulação, o resto continua
  const leve = (navigator.hardwareConcurrency || 8) <= 4;
  // altura mínima para as seções presas (mergulho, corredor, regras) caberem; igual no CSS (.presas)
  const ALTURA_PRESAS = '(min-height: 560px)';
  // peças pequenas de conteúdo que servem de âncora de leitura no zoom
  const CONTEUDO = 'h1, h2, h3, p, img, dt, dd, label, .rotulo, .placa, .botao, .filtro';

  window.SpiceMov = {};
  if (!window.gsap || !window.ScrollTrigger || !window.SplitText || reduz) {
    if (cortina) cortina.style.display = 'none';
    return;
  }

  gsap.registerPlugin(ScrollTrigger, SplitText, Flip, ScrambleTextPlugin);
  ScrollTrigger.config({ ignoreMobileResize: true }); // barra do navegador no celular não dispara recálculo
  html.classList.add('movimento');

  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const rnd = gsap.utils.random;
  const fmt = new Intl.NumberFormat('pt-BR');
  const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 });
  const CORES = ['#c9a24a', '#e3a5b2', '#a8445f', '#f3dc9a', '#c4d5e8', '#cfe0d0', '#fbf6f1'];
  const TITULOS = '.secao__titulo, .corredor__titulo, .vitrine__titulo, .chamada__titulo, .cartaz__nome, .leiloes-cab h1, .ficha__titulo, .atelie__titulo, .porta__arte p';

  // Tudo que aparece na tela espera a cortina abrir (promessa nova a cada página montada).
  let abrir;
  let aberto = Promise.resolve();
  let pedidos = 0; // trocas de página pedidas (só a última monta)
  // o que cada página liga fora do GSAP (tickers, observers) e precisa desligar na troca
  const limpar = [];
  const aoEntrar = (alvos, animar, start = 'top 88%') =>
    alvos.length && ScrollTrigger.batch(alvos, { start, once: true, onEnter: (lote) => aberto.then(() => animar(lote)) });

  /* ---------- rolagem suave ---------- */
  const lenis = new Lenis({ lerp: 0.085 });
  lenis.on('scroll', ScrollTrigger.update);
  // enquanto rola, os efeitos de hover dos quadros não disparam (quadro passando sob o
  // cursor parado ativava ondulação + inclinação no meio da rolagem)
  const rolando = () => !!lenis.isScrolling;
  gsap.ticker.add((t) => lenis.raf(t * 1000));
  gsap.ticker.lagSmoothing(0);
  lenis.stop();

  /* ---------- cortina: intro na 1ª visita, transição entre páginas ---------- */
  const paineis = $$('.cortina__painel');
  cortina.style.animation = 'none';

  const sairCortina = () =>
    gsap.timeline({ onStart: abrir, onComplete: () => (cortina.style.visibility = 'hidden') })
      .to([paineis[1], paineis[0]], { yPercent: -100, duration: 1.1, ease: 'expo.inOut', stagger: 0.12 });

  // Pré-carga real: fontes + todas as obras decodificadas antes da cortina abrir
  // (assim nenhuma imagem decodifica no meio de uma animação). Teto de 4s.
  function preCarregar() {
    const imgs = $$('img');
    imgs.forEach((i) => (i.loading = 'eager'));
    const tudo = Promise.allSettled([document.fonts.ready, ...imgs.map((i) => (i.decode ? i.decode() : Promise.resolve()))]);
    return Promise.race([tudo, new Promise((r) => setTimeout(r, 4000))]);
  }

  function intro(pronto, depois) {
    const marca = $('.cortina__marca');
    const contador = $('.cortina__contador');
    const letras = SplitText.create(marca, { type: 'chars', mask: 'chars' }).chars;
    const n = { v: 0 };
    const escrever = () => (contador.textContent = String(Math.round(n.v)).padStart(3, '0'));
    gsap.set([marca, contador], { visibility: 'visible' });
    const tl = gsap.timeline();
    tl.from(letras, { yPercent: 115, duration: 1.1, ease: 'expo.out', stagger: 0.07 })
      .to(n, { v: 88, duration: 1.4, ease: 'power2.out', onUpdate: escrever }, 0)
      // o contador espera aqui até tudo estar carregado de verdade
      .add(() => { tl.pause(); pronto.then(() => tl.resume()); })
      .to(n, { v: 100, duration: 0.35, ease: 'power1.in', onUpdate: escrever })
      .to(letras, { yPercent: -115, duration: 0.7, ease: 'expo.in', stagger: 0.045 }, '>-0.05')
      .to(contador, { autoAlpha: 0, duration: 0.3 }, '<')
      .add(sairCortina(), '>-0.25')
      .add(depois, '-=0.95');
  }

  // troca de página (app.js): a cortina sobe e cobre a tela; chamar de novo no meio devolve a mesma espera
  let cobrindo = null;
  const cobrir = () => (cobrindo ||= new Promise((pronto) => {
    cortina.style.visibility = 'visible';
    gsap.set($$('.cortina__marca, .cortina__contador'), { visibility: 'hidden' });
    lenis.stop();
    gsap.fromTo(paineis, { yPercent: 100 }, { yPercent: 0, duration: 0.75, ease: 'expo.inOut', stagger: 0.1, overwrite: true, onComplete: pronto });
  }));

  // âncora na mesma página: rolagem suave (links para outra página ficam com o app.js)
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href*="#"]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || a.classList.contains('pular')) return;
    const url = new URL(a.href, location.href);
    if (url.href.split('#')[0] !== location.href.split('#')[0]) return;
    const alvo = document.getElementById(decodeURIComponent(url.hash.slice(1)));
    if (!alvo) return;
    e.preventDefault();
    lenis.scrollTo(alvo, { offset: -90, duration: 1.6 });
    history.replaceState(history.state, '', url.hash);
  });

  // voltar pelo histórico (bfcache) traz a página com a cortina fechada
  addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    cobrindo = null;
    gsap.set(paineis, { yPercent: -100 });
    cortina.style.visibility = 'hidden';
    lenis.start();
  });

  /* ---------- utilidades expostas ---------- */
  function contar(el, de, ate, dur = 1.6) {
    const moeda = el.hasAttribute('data-moeda');
    const o = { v: de };
    return gsap.to(o, { v: ate, duration: dur, ease: 'power3.out', onUpdate: () => (el.textContent = (moeda ? brl : fmt).format(Math.round(o.v))) });
  }

  function faiscas(el, n = 34) {
    const b = el.getBoundingClientRect();
    const cx = b.left + b.width / 2;
    const cy = b.top + b.height / 2;
    for (let i = 0; i < n; i++) {
      const p = document.createElement('i');
      const s = rnd(4, 11);
      p.className = 'faisca';
      Object.assign(p.style, {
        left: cx + 'px', top: cy + 'px', width: s + 'px', height: (i % 3 ? s : s * 0.4) + 'px',
        background: CORES[i % CORES.length], borderRadius: i % 2 ? '50%' : '1px',
      });
      document.body.append(p);
      const ang = rnd(0, Math.PI * 2);
      const dist = rnd(50, 230);
      gsap.timeline({ onComplete: () => p.remove() })
        .set(p, { xPercent: -50, yPercent: -50 })
        .to(p, { x: Math.cos(ang) * dist, y: Math.sin(ang) * dist * 0.6 - rnd(40, 140), rotation: rnd(-540, 540), duration: rnd(0.55, 0.9), ease: 'power3.out' })
        .to(p, { y: '+=' + rnd(140, 280), autoAlpha: 0, duration: rnd(0.9, 1.4), ease: 'power2.in' });
    }
  }

  const pendurar = (el) =>
    gsap.fromTo(el, { y: -150, rotation: -14, autoAlpha: 0, transformOrigin: '50% -40px' },
      { y: 0, rotation: 0, autoAlpha: 1, duration: 2, ease: 'elastic.out(1, 0.33)' });

  const revelar = (el) =>
    gsap.from(el.children, { y: 24, autoAlpha: 0, duration: 0.7, ease: 'expo.out', stagger: 0.06, clearProps: 'all' });

  window.SpiceMov = {
    contar, faiscas, pendurar, revelar, cobrir, desmontar,
    montar(y, alvo) {
      // a página nova pode pedir fontes que ainda não baixaram: o layout forçado as pede,
      // e o SplitText só corta o texto depois delas (senão mede a fonte errada)
      const n = ++pedidos;
      const carregado = preCarregar();
      void document.body.offsetHeight;
      document.fonts.ready.then(() => n === pedidos && montar(carregado, y, alvo));
    },
    rolar: (el) => lenis.scrollTo(el, { offset: -140, duration: 1.4 }),
    estrelas(input) {
      const acesas = $$(`#${input.id} ~ label`);
      gsap.fromTo(acesas, { scale: 1.6, rotation: -20 }, { scale: 1, rotation: 0, duration: 0.9, ease: 'elastic.out(1, 0.4)', stagger: 0.05 });
      faiscas(input.nextElementSibling, 16);
    },
    filtrar(obras, aplicar) {
      const estado = Flip.getState(obras);
      aplicar();
      Flip.from(estado, {
        duration: 0.85, ease: 'expo.inOut', absolute: true, stagger: 0.015,
        onEnter: (els) => gsap.fromTo(els, { autoAlpha: 0, y: 50, scale: 0.94 }, { autoAlpha: 1, y: 0, scale: 1, duration: 0.7, delay: 0.3, stagger: 0.05 }),
        onLeave: (els) => gsap.to(els, { autoAlpha: 0, scale: 0.9, duration: 0.35 }),
        onComplete: () => ScrollTrigger.refresh(),
      });
    },
  };

  /* ---------- poeira dourada (canvas) ---------- */
  function poeira(canvas) {
    const ctx = canvas.getContext('2d');
    const noite = canvas.dataset.tom === 'noite' || document.body.classList.contains('noite');
    const tons = noite ? ['255,214,150', '217,178,94', '255,240,210'] : ['201,162,74', '227,165,178', '159,187,149'];
    const area = canvas.parentElement;
    let w = 0, h = 0, parts = [], ativo = false, impar = false;
    const mouse = { x: -9999, y: -9999 };

    // resolução 1x e ~60 partículas: poeira é desfocada por natureza, ninguém nota a diferença
    const medir = () => {
      w = canvas.clientWidth; h = canvas.clientHeight;
      canvas.width = w; canvas.height = h;
      parts = Array.from({ length: Math.min(60, Math.round((w * h) / 16000)) }, () => ({
        x: Math.random() * w, y: Math.random() * h, r: rnd(0.7, 2.6), vx: rnd(-0.24, 0.24), vy: rnd(-0.64, -0.08),
        a: rnd(0, 6.28), t: tons[(Math.random() * tons.length) | 0], dx: 0, dy: 0,
      }));
    };
    // desenha a 30 fps (frame sim, frame não); velocidades dobradas compensam
    const passo = () => {
      impar = !impar;
      if (!ativo || impar) return;
      ctx.clearRect(0, 0, w, h);
      for (const p of parts) {
        p.a += 0.036;
        const mx = p.x - mouse.x, my = p.y - mouse.y, d2 = mx * mx + my * my;
        if (d2 < 16000) { const f = (1 - d2 / 16000) * 1.6; p.dx += (mx / Math.sqrt(d2 + 1)) * f; p.dy += (my / Math.sqrt(d2 + 1)) * f; }
        p.dx *= 0.92; p.dy *= 0.92;
        p.x += p.vx + Math.sin(p.a) * 0.18 + p.dx;
        p.y += p.vy + p.dy;
        if (p.y < -6) { p.y = h + 6; p.x = Math.random() * w; }
        if (p.x < -6) p.x = w + 6; else if (p.x > w + 6) p.x = -6;
        const alpha = (noite ? 0.45 : 0.32) + Math.sin(p.a * 1.7) * 0.25;
        ctx.fillStyle = `rgba(${p.t},${Math.max(0, alpha)})`;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 6.283); ctx.fill();
      }
    };
    medir();
    const ro = new ResizeObserver(medir);
    const io = new IntersectionObserver(([e]) => (ativo = e.isIntersecting));
    ro.observe(canvas);
    io.observe(canvas);
    area.addEventListener('pointermove', (e) => { const b = canvas.getBoundingClientRect(); mouse.x = e.clientX - b.left; mouse.y = e.clientY - b.top; });
    area.addEventListener('pointerleave', () => (mouse.x = mouse.y = -9999));
    gsap.ticker.add(passo);
    limpar.push(() => { ro.disconnect(); io.disconnect(); gsap.ticker.remove(passo); });
  }

  /* ---------- cursor de galeria ---------- */
  function cursor() {
    const cur = document.createElement('div');
    cur.className = 'cursor';
    cur.dataset.fixo = ''; // sobrevive à troca de página
    cur.setAttribute('aria-hidden', 'true');
    cur.innerHTML = '<div class="cursor__anel"><span class="cursor__circulo"></span><span class="cursor__texto"></span></div><div class="cursor__ponto"></div>';
    document.body.append(cur);
    html.classList.add('cursor-on');
    const [anel, ponto] = cur.children;
    const texto = cur.querySelector('.cursor__texto');
    const ax = gsap.quickTo(anel, 'x', { duration: 0.55, ease: 'power3' });
    const ay = gsap.quickTo(anel, 'y', { duration: 0.55, ease: 'power3' });
    const px = gsap.quickTo(ponto, 'x', { duration: 0.1 });
    const py = gsap.quickTo(ponto, 'y', { duration: 0.1 });
    let visivel = false;
    gsap.set(cur, { autoAlpha: 0 });
    addEventListener('pointermove', (e) => {
      if (!visivel) { visivel = true; gsap.to(cur, { autoAlpha: 1, duration: 0.3 }); }
      ax(e.clientX); ay(e.clientY); px(e.clientX); py(e.clientY);
    }, { passive: true });
    document.addEventListener('pointerover', (e) => {
      const rot = e.target.closest('[data-cursor]');
      const link = e.target.closest('a, button, label, select, [role="tab"]');
      cur.classList.toggle('rotulado', !!rot);
      cur.classList.toggle('sobre', !rot && !!link);
      if (rot) texto.textContent = rot.dataset.cursor;
    });
    html.addEventListener('pointerleave', () => { visivel = false; gsap.to(cur, { autoAlpha: 0, duration: 0.3 }); });
  }

  /* ---------- tinta líquida: filtro SVG de ondulação ---------- */
  document.body.insertAdjacentHTML('beforeend',
    '<svg data-fixo width="0" height="0" style="position:absolute" aria-hidden="true"><filter id="liquido" x="-5%" y="-5%" width="110%" height="110%">' +
    '<feTurbulence type="fractalNoise" baseFrequency="0.012 0.03" numOctaves="1" seed="7"/>' +
    '<feDisplacementMap in="SourceGraphic" scale="0" xChannelSelector="R" yChannelSelector="G"/></filter></svg>');
  const disp = $('#liquido feDisplacementMap');
  let ondulando = null;
  // filtro SVG roda na CPU: 1 oitava, turbulência fixa, 0.9s, e só em quadros pequenos
  function ondular(img) {
    if (leve || !img || rolando()) return;
    if (ondulando && ondulando !== img) ondulando.style.filter = '';
    ondulando = img;
    img.style.filter = 'url(#liquido)';
    gsap.fromTo(disp, { attr: { scale: 45 } }, { attr: { scale: 0 }, duration: 0.9, ease: 'power3.out', overwrite: true,
      onComplete: () => { img.style.filter = ''; ondulando = null; } });
  }

  /* ---------- quadros da galeria: inclinação 3D, reflexo, ondulação ---------- */
  function quadrosVivos() {
    $$('.obra__link').forEach((link) => {
      const q = link.querySelector('.quadro');
      const img = link.querySelector('img');
      const brilho = document.createElement('span');
      brilho.className = 'brilho';
      (q.querySelector('.quadro__passe') || q).append(brilho); // o passe recorta o reflexo; o quadro fica livre para a crista
      const r0 = parseFloat(getComputedStyle(q).getPropertyValue('--r')) || 0;
      gsap.set(q, { rotation: r0, transformPerspective: 900 });
      const rx = gsap.quickTo(q, 'rotationX', { duration: 0.6, ease: 'power3' });
      const ry = gsap.quickTo(q, 'rotationY', { duration: 0.6, ease: 'power3' });
      link.addEventListener('pointerenter', () => {
        if (rolando()) return;
        gsap.to(q, { rotation: 0, y: -8, duration: 0.7, ease: 'power3.out', overwrite: 'auto' });
        ondular(img);
      });
      link.addEventListener('pointermove', (e) => {
        if (rolando()) return;
        const b = q.getBoundingClientRect();
        const x = (e.clientX - b.left) / b.width;
        const y = (e.clientY - b.top) / b.height;
        rx((0.5 - y) * 12);
        ry((x - 0.5) * 14);
        // reflexo anda por transform (camada própria), sem repintar o quadro
        gsap.set(brilho, { x: (x - 0.5) * q.offsetWidth, y: (y - 0.5) * q.offsetHeight });
      });
      link.addEventListener('pointerleave', () => {
        rx(0); ry(0);
        gsap.to(q, { rotation: r0, y: 0, duration: 1.4, ease: 'elastic.out(1, 0.4)', overwrite: 'auto' });
      });
    });
  }

  /* ---------- botões magnéticos ---------- */
  function magneticos() {
    $$('.botao, [data-magnetico]').forEach((el) => {
      const mx = gsap.quickTo(el, 'x', { duration: 0.5, ease: 'power3' });
      const my = gsap.quickTo(el, 'y', { duration: 0.5, ease: 'power3' });
      el.addEventListener('pointermove', (e) => {
        const b = el.getBoundingClientRect();
        mx((e.clientX - b.left - b.width / 2) * 0.3);
        my((e.clientY - b.top - b.height / 2) * 0.35);
      });
      el.addEventListener('pointerleave', () => gsap.to(el, { x: 0, y: 0, duration: 1.1, ease: 'elastic.out(1, 0.35)', overwrite: true }));
    });
  }

  /* ---------- holofote que segue o mouse ---------- */
  function holofotes() {
    // a luz é um disco com gradiente que só se move por transform; o fundo nunca repinta
    $$('[data-holofote]').forEach((el) => {
      const luz = document.createElement('span');
      luz.className = 'luz';
      luz.setAttribute('aria-hidden', 'true');
      el.prepend(luz);
      el.classList.add('com-luz');
      const centro = () => [el.offsetWidth * 0.5, el.offsetHeight * 0.22];
      gsap.set(luz, { x: centro()[0], y: centro()[1] });
      const qx = gsap.quickTo(luz, 'x', { duration: 1, ease: 'power3' });
      const qy = gsap.quickTo(luz, 'y', { duration: 1, ease: 'power3' });
      el.addEventListener('pointermove', (e) => {
        const b = el.getBoundingClientRect();
        qx(e.clientX - b.left);
        qy(e.clientY - b.top);
      });
      el.addEventListener('pointerleave', () => { const [x, y] = centro(); qx(x); qy(y); });
    });
  }

  /* ---------- faixa: velocidade e direção seguem a rolagem ---------- */
  function faixa() {
    const trilho = $('.faixa__trilho');
    const anim = trilho && trilho.getAnimations()[0];
    if (!anim) return;
    anim.currentTime = 38000 * 200; // margem para tocar ao contrário sem chegar ao início
    // só a velocidade da animação CSS muda (fica na GPU); sem inclinar o texto, que repintava a faixa inteira
    const v = { rate: 1 };
    const ritmo = gsap.quickTo(v, 'rate', { duration: 0.5, ease: 'power2', onUpdate: () => (anim.playbackRate = v.rate) });
    limpar.push(lenis.on('scroll', ({ velocity, direction }) => ritmo((direction < 0 ? -1 : 1) * (1 + Math.min(Math.abs(velocity) / 5, 6)))));
  }

  /* ---------- entrada da home ---------- */
  function hero() {
    const el = $('.hero');
    if (!el) return null;
    const titulo = $('.hero__titulo', el);
    const st = SplitText.create(titulo, { type: 'lines,words', mask: 'lines', linesClass: 'linha' });
    const quadros = $$('.hero__parede .quadro', el);
    gsap.set(quadros, { transformOrigin: '50% -40px' });
    const tl = gsap.timeline({ paused: true, onComplete: () => st.revert() })
      .from(st.words, { yPercent: 118, rotation: 5, duration: 1.4, ease: 'expo.out', stagger: 0.075 })
      .fromTo($('em', titulo), { backgroundSize: '0% 0.2em' }, { backgroundSize: '100% 0.2em', duration: 1.1, ease: 'expo.inOut' }, 0.7)
      .from($$('.hero__lead, .hero__acoes, .hero__numeros', el), { y: 34, autoAlpha: 0, duration: 1.1, ease: 'expo.out', stagger: 0.1 }, 0.35)
      .from(quadros, { y: -170, rotation: '-=16', autoAlpha: 0, duration: 2.3, ease: 'elastic.out(1, 0.32)', stagger: 0.17 }, 0.05)
      .from('.selo', { scale: 0, rotation: -220, duration: 1.5, ease: 'back.out(1.7)' }, 0.85)
      .add(() => {
        // parallax de profundidade com o mouse, depois da entrada
        if (!fino) return;
        const fx = quadros.map((q) => gsap.quickTo(q, 'x', { duration: 1.2, ease: 'power3' }));
        const fy = quadros.map((q) => gsap.quickTo(q, 'y', { duration: 1.2, ease: 'power3' }));
        el.addEventListener('pointermove', (e) => {
          const nx = e.clientX / innerWidth - 0.5;
          const ny = e.clientY / innerHeight - 0.5;
          quadros.forEach((_, i) => { fx[i](nx * (14 + i * 12)); fy[i](ny * (10 + i * 9)); });
        });
        quadros.forEach((q) => q.addEventListener('pointerenter', () => ondular(q.querySelector('img'))));
      });
    $$('[data-contar]', el).forEach((dd) => tl.add(contar(dd, 0, +dd.dataset.contar, 2), 0.6));

    // fora da tela, o selo para de girar (não gasta frame à toa)
    const io = new IntersectionObserver(([e]) => el.classList.toggle('pausado', !e.isIntersecting));
    io.observe(el);
    limpar.push(() => io.disconnect());

    gsap.to('.hero__parede', { yPercent: -14, scale: 0.9, rotation: -2.5, ease: 'none', scrollTrigger: { trigger: el, start: 'top top', end: 'bottom top', scrub: true } });
    gsap.to('.hero__texto', { yPercent: 7, ease: 'none', scrollTrigger: { trigger: el, start: 'top top', end: 'bottom top', scrub: true } });
    return tl;
  }

  /* ---------- revelações ao rolar ---------- */
  function revelacoes() {
    const hero = $('.hero');
    const foraDoHero = (el) => !hero || !hero.contains(el);

    // títulos: letras sobem de dentro de máscaras
    $$(TITULOS).forEach((el) => {
      const st = SplitText.create(el, { type: 'lines,words,chars', mask: 'lines', linesClass: 'linha' });
      gsap.set(st.chars, { yPercent: 118 });
      aoEntrar([el], () => gsap.to(st.chars, { yPercent: 0, duration: 1.25, ease: 'expo.out', stagger: 0.022, onComplete: () => st.revert() }));
    });

    // rótulos: linha desenha e texto decodifica
    $$('.rotulo').forEach((el) => {
      const texto = el.textContent;
      gsap.set(el, { '--l': 0, autoAlpha: 0 });
      aoEntrar([el], () => gsap.timeline()
        .to(el, { autoAlpha: 1, duration: 0.2 })
        .to(el, { '--l': 1, duration: 1, ease: 'expo.out' }, 0)
        .to(el, { duration: 1.2, scrambleText: { text: texto, chars: 'SPICE0123456789·', speed: 0.6, revealDelay: 0.25 } }, 0));
    }, 'top 92%');

    // blocos simples
    const blocos = $$('[data-revelar]');
    blocos.length && gsap.set(blocos, { y: 44, autoAlpha: 0 });
    aoEntrar(blocos, (lote) => gsap.to(lote, { y: 0, autoAlpha: 1, duration: 1.1, ease: 'expo.out', stagger: 0.1, clearProps: 'transform' }));

    // contêineres em cascata (filhos um a um), exceto títulos que já se animam sozinhos
    $$('[data-cascata]').forEach((el) => {
      const filhos = [...el.children].filter((c) => !c.matches(TITULOS));
      gsap.set(filhos, { y: 32, autoAlpha: 0 });
      aoEntrar([el], () => gsap.to(filhos, { y: 0, autoAlpha: 1, duration: 1, ease: 'expo.out', stagger: 0.07, clearProps: 'transform' }), 'top 92%');
    });

    // barras da distribuição de notas crescem
    const barras = $$('.barra i');
    barras.length && gsap.set(barras, { scaleX: 0, transformOrigin: '0 50%' });
    aoEntrar(barras, (lote) => gsap.to(lote, { scaleX: 1, duration: 1.4, ease: 'expo.out', stagger: 0.08 }));

    // números que contam
    $$('[data-contar]').filter(foraDoHero).forEach((el) => {
      aoEntrar([el], () => contar(el, 0, +el.dataset.contar, 2));
    });

    // obras: sobem e a tela se desvela de cima para baixo dentro do passe-partout
    // (antes: clip-path na imagem, que repintava o card a cada frame; agora um véu da cor
    //  do passe-partout recolhe por transform e é removido no fim)
    const obras = $$('.obra');
    obras.forEach((o) => {
      const veu = document.createElement('span');
      veu.className = 'veu';
      o.querySelector('.quadro__passe').append(veu);
    });
    obras.length && gsap.set(obras, { y: 90, autoAlpha: 0 });
    aoEntrar(obras, (lote) => {
      gsap.to(lote, { y: 0, autoAlpha: 1, duration: 1.2, ease: 'expo.out', stagger: 0.11 });
      gsap.to(lote.map((o) => o.querySelector('.veu')), { scaleY: 0, duration: 1.5, ease: 'expo.inOut', stagger: 0.11, delay: 0.15, force3D: true });
    }, 'top 92%');

    // lotes de leilão: viram de baixo para cima como cartas
    const lotes = $$('.lote');
    lotes.length && gsap.set(lotes, { y: 120, rotationX: -32, autoAlpha: 0, transformPerspective: 1200, transformOrigin: '50% 0%', transition: 'none' });
    aoEntrar(lotes, (lote) => gsap.to(lote, { y: 0, rotationX: 0, autoAlpha: 1, duration: 1.4, ease: 'expo.out', stagger: 0.13, clearProps: 'transform,transition' }));

    // quadros principais (obra, leilão) chegam pendurados
    const solo = $$('.parede-luz .quadro');
    solo.length && gsap.set(solo, { autoAlpha: 0 });
    aoEntrar(solo, (lote) => lote.forEach((q) => pendurar(q)));

    // vitrine noturna: um holofote se abre sobre a página
    const vit = $('.vitrine');
    if (vit) {
      // um disco noturno cresce (transform, GPU) e o conteúdo aparece por cima
      const lua = document.createElement('div');
      lua.className = 'vitrine__lua';
      lua.setAttribute('aria-hidden', 'true');
      vit.prepend(lua);
      const cobrir = () => (Math.hypot(vit.offsetWidth / 2, vit.offsetHeight * 0.55) / (lua.offsetWidth / 2)) * 1.05;
      gsap.timeline({ scrollTrigger: { trigger: vit, start: 'top 98%', end: 'top 10%', scrub: 0.6, invalidateOnRefresh: true } })
        .fromTo(lua, { scale: 0.35 }, { scale: cobrir, ease: 'none', duration: 1 })
        .fromTo($$(':scope > .envolve, :scope > .poeira', vit), { autoAlpha: 0 }, { autoAlpha: 1, ease: 'none', duration: 0.45 }, 0.5);
      gsap.fromTo($('.quadro', vit), { rotation: -6 }, { rotation: 3, ease: 'none', scrollTrigger: { trigger: vit, start: 'top bottom', end: 'bottom top', scrub: 1.2 } });
    }

    // letreiro do rodapé
    const marca = $('.rodape__marca');
    if (marca) {
      const st = SplitText.create(marca, { type: 'chars', charsClass: 'letra' });
      gsap.set(st.chars, { yPercent: 70, rotation: 8, autoAlpha: 0 });
      aoEntrar([marca], () => gsap.to(st.chars, { yPercent: 0, rotation: 0, autoAlpha: 1, duration: 1.5, ease: 'expo.out', stagger: 0.07, clearProps: 'transform' }), 'top 98%');
    }

    // barra de leitura
    const barra = document.createElement('div');
    barra.className = 'progresso';
    document.body.append(barra);
    gsap.to(barra, { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: 0.3 } });
  }

  /* =========================================================
     SEQUÊNCIAS DE SCROLL DA HOME (seções fixadas, animação presa à rolagem)
     ========================================================= */

  // 1. Mergulho: a câmera atravessa a moldura e entra no sol de açafrão
  function mergulho() {
    const m = $('.mergulho');
    if (!m) return;
    const q = $('.mergulho__quadro', m);
    const passe = $('.quadro__passe', q);
    const fa = $('.mergulho__frase--a', m);
    const fb = $('.mergulho__frase--b', m);
    // A obra está embutida como <svg> (não <img>): ampliada 22×, o navegador desenha só o
    // pedaço visível, em vetor. Com <img> ele gerava um bitmap de ~5600px a cada frame.
    const mirar = () => {
      // mira um ponto dentro do sol: 50% × 30% da tela da obra
      const cs = getComputedStyle(passe);
      const esq = passe.offsetLeft + parseFloat(cs.paddingLeft);
      const topo = passe.offsetTop + parseFloat(cs.paddingTop);
      const larg = passe.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
      gsap.set(q, { transformOrigin: `${esq + larg * 0.5}px ${topo + larg * 1.25 * 0.3}px` });
    };
    mirar();
    ScrollTrigger.addEventListener('refreshInit', mirar);
    limpar.push(() => ScrollTrigger.removeEventListener('refreshInit', mirar));
    // a sombra ampliada é o que mais pesa; some quando o quadro já passou da câmera
    const aproximar = () => q.classList.toggle('sem-sombra', gsap.getProperty(q, 'scale') > 1.6);

    // Entrada e mergulho são duas animações presas à rolagem, ambas com atraso (scrub). Se
    // mexessem no mesmo elemento, rolando rápido a entrada ainda "alcançando" desfazia o
    // mergulho (frases gigantes por cima do texto final). Por isso: a entrada anima o
    // invólucro (frase, moldura) e o mergulho anima o miolo (texto, quadro).
    const [ma, mb] = $$('.mergulho__miolo', m);
    gsap.timeline({ scrollTrigger: { trigger: m, start: 'top 85%', end: 'top top', scrub: 1 } })
      .from(fa, { xPercent: -60, autoAlpha: 0, ease: 'power2.out' })
      .from(fb, { xPercent: 60, autoAlpha: 0, ease: 'power2.out' }, 0)
      .from($('.mergulho__moldura', m), { scale: 0.55, rotation: -9, ease: 'power2.out' }, 0);

    gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: { trigger: m, start: 'top top', end: '+=260%', pin: true, scrub: 1, anticipatePin: 1 } })
      .to({}, { duration: 0.35 })
      .to(q, { scale: 22, duration: 3, ease: 'power3.in', onUpdate: aproximar })
      .to(ma, { xPercent: -150, scale: 1.5, autoAlpha: 0, duration: 1.7, ease: 'power2.in' }, '<')
      .to(mb, { xPercent: 150, scale: 1.5, autoAlpha: 0, duration: 1.7, ease: 'power2.in' }, '<')
      .set(m, { backgroundColor: '#f3d3da' })
      .set(q, { autoAlpha: 0 })
      .from($$('.mergulho__alem > *', m), { yPercent: 90, autoAlpha: 0, stagger: 0.28, duration: 1, ease: 'power3.out' })
      .to({}, { duration: 0.9 });
  }

  // 2. Corredor: rolar para baixo caminha para o lado; os quadros balançam no prego
  function corredor() {
    const c = $('.corredor');
    if (!c) return;
    const trilho = $('.corredor__trilho', c);
    const itens = $$('.corredor__item', c);
    const quadros = itens.map((i) => $('.quadro', i));
    const num = $('.corredor__contador b', c);
    const ultimo = itens[itens.length - 1];
    const distancia = () => Math.max(0, ultimo.offsetLeft + ultimo.offsetWidth / 2 - innerWidth / 2);

    gsap.set(quadros, { transformOrigin: '50% -40px' });
    const balanco = { r: 0 };
    let rAnterior = 0;
    const balancar = () => {
      if (balanco.r === rAnterior) return;
      rAnterior = balanco.r;
      quadros.forEach((q, i) => gsap.set(q, { rotation: balanco.r * (i % 2 ? 0.75 : 1.2) }));
    };
    gsap.ticker.add(balancar);
    limpar.push(() => gsap.ticker.remove(balancar));
    const assentar = gsap.delayedCall(0.12, () => gsap.to(balanco, { r: 0, duration: 2.4, ease: 'elastic.out(1, 0.2)', overwrite: true })).pause();

    let atual = 0;
    const tl = gsap.timeline({
      defaults: { ease: 'none' },
      scrollTrigger: {
        trigger: c, start: 'top top', end: () => '+=' + distancia(), pin: true, scrub: 0.8, invalidateOnRefresh: true, anticipatePin: 1,
        onUpdate(self) {
          gsap.to(balanco, { r: gsap.utils.clamp(-10, 10, -self.getVelocity() / 220), duration: 0.35, overwrite: true });
          assentar.restart(true);
          const i = Math.round(self.progress * (itens.length - 1));
          if (i === atual) return;
          atual = i;
          num.textContent = String(i + 1).padStart(2, '0');
          gsap.fromTo(num, { yPercent: self.direction > 0 ? 60 : -60, autoAlpha: 0 }, { yPercent: 0, autoAlpha: 1, duration: 0.5, ease: 'expo.out', overwrite: true });
        },
      },
    });
    tl.to(trilho, { x: () => -distancia() })
      .to($('.corredor__fundo', c), { xPercent: -42 }, 0)
      .to($('.corredor__barra i', c), { scaleX: 1 }, 0);

    // quem ainda está fora da tela chega crescendo
    itens.filter((it) => it.offsetLeft > innerWidth * 0.75).forEach((it) =>
      gsap.from(it, { scale: 0.78, autoAlpha: 0.25, ease: 'none', scrollTrigger: { trigger: it, containerAnimation: tl, start: 'left right', end: 'left 45%', scrub: true } }));
  }

  // 3. Regras: cartas sobem e se empilham; a de baixo recua e escurece
  function regras() {
    const caixa = $('.regras');
    if (!caixa) return;
    const cartas = $$('.regra', caixa);
    gsap.set(caixa, { overflow: 'hidden' });
    // Tudo aqui anima só transform/opacity (GPU). Antes: filter + background-size em texto
    // recortado gigante, que repintavam a carta inteira a cada frame.
    const sombras = cartas.map((c) => {
      const s = document.createElement('span');
      s.className = 'regra__sombra';
      s.setAttribute('aria-hidden', 'true');
      c.append(s);
      return s;
    });
    // "tinta": cópia preenchida do numeral dentro de uma máscara que sobe
    const tintas = cartas.map((c) => {
      const num = $('.regra__num', c);
      const mascara = document.createElement('span');
      mascara.className = 'regra__tinta';
      mascara.innerHTML = `<span>${num.textContent}</span>`;
      num.append(mascara);
      return [mascara, mascara.firstChild];
    });
    tintas.forEach(([mascara, tinta]) => { gsap.set(mascara, { yPercent: 100 }); gsap.set(tinta, { yPercent: -100 }); });
    // tela baixa (zoom) desliga a sequência: as peças criadas aqui saem junto
    limpar.push(() => [...sombras, ...tintas.map(([m]) => m)].forEach((el) => el.remove()));
    gsap.set(cartas.slice(1), { yPercent: 115 });
    const tl = gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: { trigger: caixa, start: 'center center', end: '+=' + cartas.length * 85 + '%', pin: true, scrub: 1, anticipatePin: 1 } });
    tl.to(tintas[0], { yPercent: 0, duration: 1 });
    cartas.slice(1).forEach((carta, i) => {
      tl.to(carta, { yPercent: 0, duration: 1.4, ease: 'power2.inOut' })
        .to(cartas[i], { scale: 0.9, yPercent: -4, duration: 1.4, ease: 'power2.inOut' }, '<')
        .to(sombras[i], { opacity: 0.3, duration: 1.4, ease: 'power2.inOut' }, '<')
        .to(tintas[i + 1], { yPercent: 0, duration: 1 });
    });
  }

  // 4. Chamada final desacelera em escala
  function detalhesDeScroll() {
    const chamada = $('.chamada');
    if (chamada) gsap.from(chamada, { scale: 0.82, ease: 'none', scrollTrigger: { trigger: chamada, start: 'top bottom', end: 'center center', scrub: 1 } });
  }

  /* ---------- partida ---------- */
  let primeira = true;
  try { primeira = !sessionStorage.getItem('spice-visitou'); sessionStorage.setItem('spice-visitou', '1'); } catch { /* sem storage: sempre intro curta */ primeira = false; }

  // Monta as animações da página atual. Roda na carga e a cada troca de página sem recarregar
  // (app.js); y/alvo = onde a página nova deve abrir (voltar no histórico, link com #).
  let ctx = null;
  let mm = null;
  let montagens = 0;
  function montar(carregado, y, alvo) {
    const vez = ++montagens;
    cobrindo = null;
    aberto = new Promise((r) => (abrir = r));
    let entradaHero;
    ctx = gsap.context(() => {
      if (!leve) $$('canvas.poeira').forEach(poeira);
      if (fino) { quadrosVivos(); magneticos(); }
      holofotes();
      faixa();
      entradaHero = hero();
      detalhesDeScroll();
      revelacoes();
    });
    // Seções presas (mergulho, corredor, regras) só cabem com altura. Abaixo de 560px (zoom
    // de 150% num notebook, celular deitado) viram layout comum; o matchMedia liga e desliga
    // ao vivo quando a pessoa dá ou tira zoom. A classe .presas troca o CSS junto.
    mm = gsap.matchMedia();
    mm.add(ALTURA_PRESAS, () => {
      const antes = limpar.length;
      html.classList.add('presas');
      mergulho();
      corredor();
      regras();
      ScrollTrigger.sort();
      const meus = limpar.splice(antes);
      return () => { meus.forEach((f) => f()); html.classList.remove('presas'); };
    });
    ScrollTrigger.sort(); // gatilhos criados fora da ordem da página: reordena antes de medir
    ScrollTrigger.refresh();

    const depois = () => { lenis.start(); entradaHero && entradaHero.play(); };
    if (primeira) { primeira = false; return intro(carregado, depois); }
    carregado.then(() => {
      if (vez !== montagens) return; // outra troca começou enquanto esta carregava
      // troca de página (y definido): posiciona no fim, inclusive no topo; algum recálculo no
      // meio do caminho pode ter devolvido a rolagem da página anterior
      if (y !== undefined) {
        ScrollTrigger.refresh();
        lenis.resize();
        lenis.scrollTo(alvo || y, { offset: alvo ? -90 : 0, immediate: true, force: true });
        // animações presas à rolagem (scrub) seguem com atraso de propósito; depois de um salto
        // elas apareceriam "alcançando" o ponto quando a cortina abre: vão direto
        ScrollTrigger.update();
        ScrollTrigger.getAll().forEach((st) => { const tw = st.getTween(); if (tw && tw.progress) tw.progress(1); });
      }
      sairCortina().add(depois, 0.25);
    });
  }

  function desmontar() {
    limpar.splice(0).forEach((f) => f());
    mm && mm.revert();
    ctx && ctx.revert();
  }

  // Zoom/redimensionar: as seções presas mudam de altura junto com a tela, então o mesmo
  // scrollY cairia em outra seção. Guarda o ponto de leitura (progresso dentro da seção presa,
  // ou o elemento logo abaixo do cabeçalho) e volta para ele a cada recálculo.
  // No zoom, quando o 'resize' chega a página já está no layout novo; por isso a medida usada é
  // a de quando a rolagem parou (marco), e vale pela rajada toda (1,5 s): o matchMedia liga/
  // desliga as seções presas DEPOIS do primeiro recálculo, e é preciso reposicionar de novo.
  // Fora do zoom (carga de imagem, filtro), mede no início do recálculo e vale só para ele.
  const medir = () => {
    const y = scrollY;
    if (y < 2) return { y: 0 };
    const st = ScrollTrigger.getAll().find((s) => s.pin && y >= s.start && y <= s.end);
    if (st) return { el: st.trigger, p: (y - st.start) / (st.end - st.start || 1) };
    // o primeiro texto/imagem visível abaixo do cabeçalho (uma seção inteira seria grande demais
    // para servir de âncora) e que fração dele já passou da linha do cabeçalho
    // (fora das seções presas: as cartas escondidas por overflow ainda têm retângulo e se movem)
    const topo = $('.topo')?.offsetHeight || 0;
    const presas = ScrollTrigger.getAll().filter((s) => s.pin).map((s) => s.trigger);
    const el = $$(CONTEUDO, $('main') || undefined).find((e) => {
      const r = e.getBoundingClientRect();
      return r.height > 0 && r.bottom > topo && r.top < innerHeight && !presas.some((p) => p.contains(e));
    });
    if (!el) return { y };
    const r = el.getBoundingClientRect();
    return { el, f: (topo - r.top) / r.height };
  };
  let ancora = null;
  let marco = null;
  let tMarco;
  const remarcar = () => { clearTimeout(tMarco); tMarco = setTimeout(() => (marco = medir()), 150); };
  addEventListener('scroll', remarcar, { passive: true });
  // só no computador: no celular o resize vem da barra do navegador sumindo durante a rolagem
  // (o ScrollTrigger ignora esse) e a âncora ficaria velha
  let tAncora;
  addEventListener('resize', () => {
    if (!fino) return;
    if (!ancora) { clearTimeout(tMarco); ancora = marco || medir(); }
    clearTimeout(tAncora);
    tAncora = setTimeout(() => (ancora = null), 1500);
  });
  // a pessoa voltou a rolar logo depois do zoom: a âncora não vale mais (ctrl+roda é o próprio zoom)
  const soltar = (e) => { if (!e.ctrlKey && ancora && !ancora.unica) ancora = null; };
  addEventListener('wheel', soltar, { passive: true });
  addEventListener('keydown', soltar);
  // seções presas ligaram/desligaram: recalcula de novo (com a mesma âncora) no quadro seguinte
  matchMedia(ALTURA_PRESAS).addEventListener('change', () => requestAnimationFrame(() => ScrollTrigger.refresh()));
  ScrollTrigger.addEventListener('refreshInit', () => {
    if (!ancora) ancora = { ...medir(), unica: true };
  });
  ScrollTrigger.addEventListener('refresh', () => {
    const a = ancora;
    remarcar();
    if (!a) return;
    if (a.unica) ancora = null;
    let y = a.y;
    // a seção pode ter virado presa (ou deixado de ser) no meio: procura pelo elemento
    const st = a.el && ScrollTrigger.getAll().find((s) => s.pin && s.trigger === a.el);
    if (st) y = st.start + (a.p || 0) * (st.end - st.start);
    else if (a.el && a.el.isConnected) {
      const r = a.el.getBoundingClientRect();
      y = scrollY + r.top - ($('.topo')?.offsetHeight || 0) + (a.f || 0) * r.height;
    }
    lenis.resize(); // o Lenis só remede a página 250 ms depois e limitaria a rolagem pela altura velha
    if (Math.abs(y - scrollY) > 2) lenis.scrollTo(y, { immediate: true, force: true });
  });

  const carregado = preCarregar(); // começa já: imagens e fontes baixam enquanto a cortina anima

  document.fonts.ready.then(() => {
    if (fino) cursor();
    montar(carregado);
    addEventListener('load', () => ScrollTrigger.refresh());
  });
})();
