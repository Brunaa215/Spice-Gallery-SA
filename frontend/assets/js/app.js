/* Spice Gallery: comportamento das páginas (sem dependências).
   Efeitos visuais opcionais vêm de window.SpiceMov (movimento.js). */
(() => {
  'use strict';

  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const mov = () => window.SpiceMov || {};
  const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 });

  /* ---------- aviso flutuante ---------- */
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.dataset.fixo = ''; // sobrevive à troca de página
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');
  document.body.append(toast);
  let tToast;
  const avisar = (msg) => {
    toast.textContent = msg;
    toast.classList.add('visivel');
    clearTimeout(tToast);
    tToast = setTimeout(() => toast.classList.remove('visivel'), 4200);
  };

  /* =========================================================
     POR PÁGINA: tudo que se liga a elementos da página.
     Roda na carga e de novo a cada troca de página (navegação sem recarregar, no fim do arquivo).
     Devolve a função que desliga o que não morre junto com o HTML (o relógio).
     ========================================================= */
  function montar() {
    /* ---------- mensagem do servidor (flash do PHP) ---------- */
    const aviso = $('[data-aviso]');
    if (aviso) setTimeout(() => avisar(aviso.dataset.aviso), 400);

    /* ---------- menu mobile ---------- */
    const menu = $('.topo__menu');
    const nav = $('#nav');
    menu?.addEventListener('click', () => {
      const aberto = nav.classList.toggle('aberta');
      menu.setAttribute('aria-expanded', aberto);
    });

    /* ---------- filtros da galeria ---------- */
    const salao = $('[data-salao]');
    const vazio = $('[data-vazio]');
    $$('.filtro').forEach((botao) =>
      botao.addEventListener('click', () => {
        const cat = botao.dataset.filtro;
        $$('.filtro').forEach((b) => b.setAttribute('aria-pressed', b === botao));
        const obras = $$('.obra', salao);
        const aplicar = () => {
          let visiveis = 0;
          obras.forEach((o) => {
            const ok = cat === 'todas' || o.dataset.categoria === cat;
            o.hidden = !ok;
            visiveis += ok;
          });
          if (vazio) vazio.hidden = visiveis > 0;
        };
        mov().filtrar ? mov().filtrar(obras, aplicar) : aplicar();
      })
    );

    /* ---------- contagem regressiva de leilões ---------- */
    const UNIDADES = [['dias', 86400], ['h', 3600], ['min', 60], ['s', 1]];
    const contagens = $$('[data-fim]').filter((el) => !el.matches('form')).map((el) => ({
      el,
      fim: Date.parse(el.dataset.fim),
      inicio: el.dataset.inicio ? Date.parse(el.dataset.inicio) : 0,
      digitos: null,
    }));
    const tique = () => {
      const agora = Date.now();
      contagens.forEach((c) => {
        if (c.feito) return;
        if (agora >= c.fim) {
          c.feito = true;
          c.el.classList.add('contagem--fim');
          c.el.textContent = 'Leilão encerrado';
          return;
        }
        if (!c.digitos) {
          c.el.innerHTML = UNIDADES.map(([u]) => `<span><b>00</b><small>${u}</small></span>`).join('');
          c.digitos = $$('b', c.el);
        }
        let resto = Math.max(0, Math.floor(((agora < c.inicio ? c.inicio : c.fim) - agora) / 1000));
        UNIDADES.forEach(([, seg], i) => {
          const v = String(Math.floor(resto / seg)).padStart(2, '0');
          resto %= seg;
          const b = c.digitos[i];
          if (b.textContent === v) return;
          b.textContent = v;
          b.animate?.([{ transform: 'translateY(-45%)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 450, easing: 'cubic-bezier(.2,.7,.1,1)' });
        });
      });
    };
    let relogio;
    if (contagens.length) {
      tique();
      relogio = setInterval(tique, 1000);
    }

    /* ---------- lance (RN-08) ---------- */
    const formLance = $('[data-lance]');
    if (formLance) {
      const campo = formLance.elements.valor;
      const erro = $('.campo__erro', formLance);
      const minimoEl = $('[data-minimo]');
      const incremento = Number(formLance.dataset.incremento);
      const fim = Date.parse(formLance.dataset.fim);
      const atual = Number(formLance.dataset.atual);
      // data-lance-minimo vem do PHP: valor inicial (sem lances) ou lance atual + incremento
      const minimo = () => Number(formLance.dataset.lanceMinimo) || atual + incremento;
      const sincronizar = () => {
        campo.min = minimo();
        if (Number(campo.value) < minimo()) campo.value = minimo();
        minimoEl.textContent = brl.format(minimo());
      };
      sincronizar();

      $$('.rapidos button', formLance).forEach((b) =>
        b.addEventListener('click', () => {
          campo.value = Math.max(minimo(), atual + Number(b.dataset.soma));
          erro.textContent = '';
        })
      );

      // Validação rápida no navegador; o PHP (Lance::registrar) valida de novo valor, prazo, autoria e créditos.
      formLance.addEventListener('submit', (e) => {
        const valor = Math.round(Number(campo.value));
        if (Date.now() >= fim) {
          e.preventDefault();
          erro.textContent = 'Este leilão já foi encerrado.';
          return;
        }
        if (!(valor >= minimo())) {
          e.preventDefault();
          erro.textContent = `O lance mínimo é ${brl.format(minimo())}.`;
          campo.focus();
          return;
        }
        // data-disponivel: créditos livres na carteira (o servidor confere de novo)
        const disponivel = Number(formLance.dataset.disponivel);
        if (formLance.dataset.disponivel !== undefined && valor > disponivel) {
          e.preventDefault();
          erro.textContent = `Você tem ${brl.format(disponivel)} disponíveis na carteira.`;
          campo.focus();
          return;
        }
        erro.textContent = '';
        mov().faiscas?.(formLance.querySelector('[type="submit"]'));
        // sem preventDefault: o formulário segue para o servidor
      });
    }

    /* ---------- nota em estrelas ---------- */
    $$('.estrelas-input input').forEach((i) => i.addEventListener('change', () => mov().estrelas?.(i)));

    /* ---------- editar a própria avaliação (RN-06) ---------- */
    $$('[data-editar]').forEach((b) =>
      b.addEventListener('click', () => {
        const form = $('#avaliar');
        const item = b.closest('.comentario');
        form.elements.comentario.value = $('p', item).textContent.trim();
        form.elements.id_avaliacao.value = b.dataset.editar;
        const nota = form.querySelector(`[name="nota"][value="${b.dataset.nota}"]`);
        if (nota) nota.checked = true;
        $('[type="submit"]', form).textContent = 'Salvar alterações';
        mov().rolar ? mov().rolar(form) : form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        form.elements.comentario.focus({ preventScroll: true });
      })
    );

    /* ---------- upload com prévia ---------- */
    const soltar = $('[data-soltar]');
    if (soltar) {
      const input = $('input[type="file"]', soltar);
      const moldura = $('.quadro', soltar);
      const img = $('img', moldura);
      const vazioUp = $('.soltar__vazio', soltar);
      const LIMITE = 10 * 1024 * 1024;
      const mostrar = (arq) => {
        if (!arq) return;
        if (!/^image\/(png|jpeg|webp)$/.test(arq.type)) {
          input.value = '';
          return avisar('Use uma imagem PNG, JPG ou WEBP.');
        }
        if (arq.size > LIMITE) {
          input.value = '';
          return avisar('A imagem passa de 10 MB. Exporte em tamanho menor.');
        }
        if (img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
        img.src = URL.createObjectURL(arq);
        moldura.hidden = false;
        vazioUp.hidden = true;
        soltar.classList.add('tem-imagem');
        mov().pendurar?.(moldura);
      };
      input.addEventListener('change', () => mostrar(input.files[0]));
      ['dragenter', 'dragover'].forEach((t) => soltar.addEventListener(t, (e) => { e.preventDefault(); soltar.classList.add('arrastando'); }));
      ['dragleave', 'drop'].forEach((t) => soltar.addEventListener(t, () => soltar.classList.remove('arrastando')));
      soltar.addEventListener('drop', (e) => {
        e.preventDefault();
        if (!e.dataTransfer.files.length) return;
        input.files = e.dataTransfer.files;
        mostrar(input.files[0]);
      });
    }

    /* ---------- leilão no formulário de publicação (RN-07) ---------- */
    const querLeilao = $('[name="disponivelLeilao"]');
    const camposLeilao = $('.leilao-campos');
    if (querLeilao && camposLeilao) {
      querLeilao.addEventListener('change', () => {
        camposLeilao.hidden = camposLeilao.disabled = !querLeilao.checked;
        if (querLeilao.checked) mov().revelar?.(camposLeilao);
      });
      const ini = camposLeilao.querySelector('[name="dataInicio"]');
      const fim = camposLeilao.querySelector('[name="dataFim"]');
      const agoraLocal = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
      ini.min = agoraLocal;
      const checarDatas = () => {
        fim.min = ini.value || agoraLocal;
        fim.setCustomValidity(ini.value && fim.value && fim.value <= ini.value ? 'O término precisa ser depois do início.' : '');
      };
      ini.addEventListener('change', checarDatas);
      fim.addEventListener('change', checarDatas);
    }

    /* ---------- abas entrar / criar conta ---------- */
    const abas = $$('[role="tab"]');
    const ativar = (aba, animar = true) => {
      abas.forEach((a) => {
        const sel = a === aba;
        a.setAttribute('aria-selected', sel);
        a.tabIndex = sel ? 0 : -1;
        document.getElementById(a.getAttribute('aria-controls')).hidden = !sel;
      });
      if (animar) mov().revelar?.(document.getElementById(aba.getAttribute('aria-controls')));
    };
    abas.forEach((a, i) => {
      a.addEventListener('click', () => ativar(a));
      a.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
        const prox = abas[(i + (e.key === 'ArrowRight' ? 1 : -1) + abas.length) % abas.length];
        prox.focus();
        ativar(prox);
      });
    });
    if (abas.length && location.hash === '#cadastro') ativar(abas[1], false);

    /* ---------- mostrar/ocultar senha ---------- */
    $$('.olho button').forEach((b) =>
      b.addEventListener('click', () => {
        const campo = b.previousElementSibling;
        const ver = campo.type === 'password';
        campo.type = ver ? 'text' : 'password';
        b.textContent = ver ? 'Ocultar' : 'Mostrar';
        b.setAttribute('aria-pressed', ver);
      })
    );

    return () => clearInterval(relogio);
  }
  let desmontar = montar();

  /* ---------- confirmação antes de excluir ---------- */
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset.confirmar;
    if (msg && !confirm(msg)) e.preventDefault();
  });

  /* =========================================================
     NAVEGAÇÃO SEM RECARREGAR
     Link interno: baixa a página nova com fetch, a cortina cobre, troca o conteúdo do <body>
     (cabeçalho, main, rodapé) e remonta comportamento + animações. Cortina, cursor, aviso e
     scripts ficam (marcados com .cortina, script ou data-fixo). Funciona igual com as páginas
     geradas pelo PHP: ele só precisa devolver HTML completo, como já devolveria.
     Em file:// o navegador bloqueia fetch: aí cada link recarrega a página, com a cortina.
     Se algo falhar (404, resposta que não é HTML, rede), cai na navegação normal.
     ========================================================= */
  const pjax = /^https?:$/.test(location.protocol);
  const fixo = (el) => el.matches('.cortina, script, [data-fixo]');
  const semHash = (u) => u.split('#')[0];
  let atual = semHash(location.href);

  // URL de destino se o link for de outra página deste site; null se o navegador deve cuidar
  const destino = (a) => {
    if (!a || a.target === '_blank' || a.hasAttribute('download') || a.classList.contains('pular')) return null;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || (url.hash && semHash(url.href) === atual)) return null;
    return url.href;
  };

  const baixar = (url) => fetch(url).then((r) => {
    if (!r.ok || !r.headers.get('content-type')?.includes('text/html')) throw new Error(`HTTP ${r.status}`);
    return r.text().then((html) => ({ html, url: r.url }));
  });

  // pré-busca: ao passar o mouse (ou tocar) num link, a página já começa a baixar
  // shortcut: vale 30 s; se o PHP tiver dado que muda a cada segundo nessa página, encurtar.
  const buscas = new Map();
  const fresca = (b) => b && Date.now() - b.t < 30000;
  const buscar = (url) => {
    const b = buscas.get(semHash(url));
    buscas.delete(semHash(url));
    return fresca(b) ? b.p : baixar(url);
  };
  const preBuscar = (e) => {
    const url = destino(e.target.closest?.('a[href]'));
    if (!url || fresca(buscas.get(semHash(url)))) return;
    const p = baixar(url);
    p.catch(() => buscas.delete(semHash(url)));
    buscas.set(semHash(url), { p, t: Date.now() });
  };

  let vez = 0;
  async function ir(url, empurrar, y = 0) {
    const minha = ++vez;
    // o "voltar" reabre onde a pessoa estava ao tocar no link (no celular a inércia do dedo
    // continua rolando por baixo da cortina)
    if (empurrar) history.replaceState({ y: scrollY }, '');
    try {
      const [pagina] = await Promise.all([buscar(url), mov().cobrir?.()]);
      if (minha !== vez) return; // outro clique chegou depois deste
      const doc = new DOMParser().parseFromString(pagina.html, 'text/html');
      const hash = new URL(url).hash;
      if (empurrar) history.pushState({ y: 0 }, '', pagina.url + hash);
      desmontar();
      mov().desmontar?.();
      document.title = doc.title;
      document.body.className = doc.body.className;
      [...document.body.children].filter((el) => !fixo(el)).forEach((el) => el.remove());
      document.body.prepend(...[...doc.body.children].filter((el) => !fixo(el)));
      atual = semHash(location.href);
      scrollTo({ top: 0, behavior: 'instant' });
      const main = $('main');
      if (main) {
        main.tabIndex = -1; // leitor de tela e teclado começam pela página nova
        main.focus({ preventScroll: true });
      }
      desmontar = montar();
      const alvo = hash && document.getElementById(decodeURIComponent(hash.slice(1)));
      if (mov().montar) mov().montar(y, alvo);
      else if (alvo) alvo.scrollIntoView();
      else scrollTo({ top: y, behavior: 'instant' });
    } catch (erro) {
      console.warn('Navegação sem recarregar falhou, recarregando:', erro);
      if (minha === vez) location.href = url;
    }
  }

  document.addEventListener('click', (e) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const url = destino(e.target.closest('a[href]'));
    if (!url) return;
    if (pjax) {
      e.preventDefault();
      ir(url, true);
    } else if (mov().cobrir) {
      e.preventDefault();
      mov().cobrir().then(() => (location.href = url));
    }
  });

  if (pjax) {
    history.scrollRestoration = 'manual'; // quem reposiciona no "voltar" é o ir()
    document.addEventListener('pointerover', preBuscar, { passive: true });
    document.addEventListener('touchstart', preBuscar, { passive: true });
    addEventListener('popstate', (e) => {
      if (semHash(location.href) !== atual) ir(location.href, false, e.state?.y || 0);
    });
  }
})();
