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

  /* ---------- formulário de lance (RN-08) ----------
     Fora do montar(): o painel do leilão ao vivo é trocado pelo servidor e o formulário novo
     precisa ser ligado de novo. */
  function ligarLance(formLance) {
    if (!formLance) return;
    const campo = formLance.elements.valor;
    const erro = $('.campo__erro', formLance);
    const minimoEl = $('[data-minimo]', formLance);
    const incremento = Number(formLance.dataset.incremento);
    const fim = Date.parse(formLance.dataset.fim);
    const atual = Number(formLance.dataset.atual);
    // data-lance-minimo vem do PHP: valor inicial (sem lances) ou lance atual + incremento
    const minimo = () => Number(formLance.dataset.lanceMinimo) || atual + incremento;
    campo.min = minimo();
    if (Number(campo.value) < minimo()) campo.value = minimo();
    minimoEl.textContent = brl.format(minimo());

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
      formLance.dataset.enviando = ''; // o leilão ao vivo não troca o painel durante o envio
      mov().faiscas?.(formLance.querySelector('[type="submit"]'));
      // sem preventDefault: o formulário segue para o servidor
    });
  }

  /* =========================================================
     POR PÁGINA: tudo que se liga a elementos da página.
     Roda na carga e de novo a cada troca de página (navegação sem recarregar, no fim do arquivo).
     Devolve a função que desliga o que não morre junto com o HTML (o relógio e o leilão ao vivo).
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

    /* ---------- contagem regressiva de leilões ----------
       Os relógios são procurados a cada segundo (e não só ao montar): o painel do leilão ao vivo
       é trocado pelo servidor e pode trazer um relógio novo ou um data-fim adiado (prorrogação). */
    const UNIDADES = [['dias', 86400], ['h', 3600], ['min', 60], ['s', 1]];
    const relogios = new WeakMap(); // elemento -> os <b> dos dígitos já desenhados
    const tique = () => {
      const agora = Date.now();
      $$('[data-fim]:not(form)').forEach((el) => {
        if (el.classList.contains('contagem--fim')) return;
        const fim = Date.parse(el.dataset.fim);
        const inicio = el.dataset.inicio ? Date.parse(el.dataset.inicio) : 0;
        if (agora >= fim) {
          el.classList.add('contagem--fim');
          el.textContent = 'Leilão encerrado';
          return;
        }
        let digitos = relogios.get(el);
        if (!digitos) {
          el.innerHTML = UNIDADES.map(([u]) => `<span><b>00</b><small>${u}</small></span>`).join('');
          digitos = $$('b', el);
          relogios.set(el, digitos);
        }
        let resto = Math.max(0, Math.floor(((agora < inicio ? inicio : fim) - agora) / 1000));
        UNIDADES.forEach(([, seg], i) => {
          const v = String(Math.floor(resto / seg)).padStart(2, '0');
          resto %= seg;
          const b = digitos[i];
          if (b.textContent === v) return;
          b.textContent = v;
          b.animate?.([{ transform: 'translateY(-45%)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 450, easing: 'cubic-bezier(.2,.7,.1,1)' });
        });
      });
    };
    let relogio;
    if ($('[data-fim]:not(form), [data-leilao-vivo]')) {
      tique();
      relogio = setInterval(tique, 1000);
    }

    /* ---------- lance (RN-08) ---------- */
    ligarLance($('[data-lance]'));

    /* ---------- leilão ao vivo ----------
       A cada 2 s (5 s antes de abrir) pergunta ao servidor se algo mudou, mandando a versão que tem.
       Se mudou, o servidor devolve o painel e o histórico prontos (os mesmos partials do PHP) e só
       esses pedaços são trocados: lance novo, prorrogação, abertura e martelo aparecem sem recarregar.
       Perguntar também é o que faz o servidor fechar o leilão na hora certa. */
    const painel = $('[data-leilao-vivo]');
    const historico = $('[data-historico]');
    let consulta;
    const perguntar = async () => {
      const estadoAntes = painel.dataset.estado;
      if (estadoAntes === 'ENCERRADO' || estadoAntes === 'REPROVADO') return; // nada mais vai mudar
      consulta = setTimeout(perguntar, estadoAntes === 'AO_VIVO' ? 2000 : 5000);
      // aba escondida não pergunta; lance sendo enviado não pode ter o formulário trocado no meio
      if (document.hidden || $('[data-lance][data-enviando]', painel)) return;
      try {
        const url = `${painel.dataset.leilaoVivo}&v=${encodeURIComponent(painel.dataset.versao)}`;
        const r = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!r.ok) return;
        const d = await r.json();
        if (!painel.isConnected) return; // a pessoa já foi para outra página
        const saldo = $('.nav__saldo');
        if (saldo && d.saldo) saldo.textContent = d.saldo;
        // sino: o aviso de quem venceu (ou vendeu) chega quando o martelo bate
        const sino = $('.nav__sino');
        if (sino && typeof d.notificacoes === 'number') {
          let numero = $('.nav__aviso', sino);
          if (d.notificacoes > 0) {
            if (!numero) ($('.nav__icone', sino) || sino).append((numero = Object.assign(document.createElement('span'), { className: 'nav__aviso' })));
            numero.textContent = d.notificacoes;
          } else numero?.remove();
          sino.setAttribute('aria-label', 'Notificações' + (d.notificacoes ? ` (${d.notificacoes} novas)` : ''));
        }
        if (d.versao === painel.dataset.versao || !d.painel) return;

        // guarda o lance que a pessoa estava digitando
        const campo = $('[data-lance] [name="valor"]', painel);
        const digitado = campo ? campo.value : '';
        const focado = campo && document.activeElement === campo;
        const eraLider = painel.dataset.lider === '1';

        painel.innerHTML = d.painel;
        if (historico) historico.innerHTML = d.historico;
        Object.assign(painel.dataset, { versao: d.versao, estado: d.estado, lider: d.souLider ? '1' : '0' });

        const form = $('[data-lance]', painel);
        ligarLance(form);
        const campoNovo = form?.elements.valor;
        if (campoNovo && digitado && Number(digitado) >= Number(campoNovo.min)) campoNovo.value = digitado;
        if (campoNovo && focado) campoNovo.focus({ preventScroll: true });
        $('[data-lance-atual]', painel)?.animate?.([{ transform: 'scale(1.1)' }, { transform: 'none' }], { duration: 600, easing: 'cubic-bezier(.2,.7,.1,1)' });

        if (estadoAntes !== d.estado) {
          avisar({ AO_VIVO: 'O leilão abriu! Já dá para dar lances.', ENCERRADO: 'Martelo batido: o leilão foi encerrado.' }[d.estado] || 'A situação do leilão mudou.');
        } else if (eraLider && !d.souLider) {
          avisar('Seu lance foi coberto! Dê um novo lance para voltar à frente.');
        }
      } catch {
        /* rede instável: tenta de novo no próximo ciclo */
      }
    };
    if (painel) consulta = setTimeout(perguntar, 2000);

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
      // "Em data e hora marcadas" mostra o campo de início; "assim que a curadoria aprovar" o esconde
      const blocoData = $('[data-inicio-data]', camposLeilao);
      const ini = $('[name="dataInicio"]', camposLeilao);
      // data e hora locais no formato do <input type="datetime-local"> (2026-11-24T21:30)
      const local = (ms) => new Date(ms - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
      ini.min = local(Date.now());
      $$('[name="inicioModo"]', camposLeilao).forEach((r) =>
        r.addEventListener('change', () => {
          const naData = r.value === 'data' && r.checked;
          blocoData.hidden = ini.disabled = !naData;
          if (!naData) return;
          // nunca aparece vazio nem no passado: sugere daqui a 10 minutos
          if (!ini.value || ini.value < ini.min) ini.value = local(Date.now() + 10 * 60000);
          ini.focus();
        })
      );
    }

    /* ---------- meu perfil: prévia ao vivo ---------- */
    const perfil = $('[data-perfil]');
    if (perfil) {
      const retratoPrevia = $('.perfil__previa [data-retrato]', perfil);
      const molduras = $$('[name="molduraFoto"]', perfil);
      const classesMoldura = molduras.map((r) => r.dataset.classe).filter(Boolean);
      molduras.forEach((r) =>
        r.addEventListener('change', () => {
          retratoPrevia.classList.remove(...classesMoldura);
          if (r.dataset.classe) retratoPrevia.classList.add(r.dataset.classe);
        })
      );
      // mesma checagem do upload da obra; o PHP confere de novo pelo conteúdo do arquivo
      const imagemValida = (campo) => {
        const arq = campo.files[0];
        if (!arq) return null;
        if (!/^image\/(png|jpeg|webp)$/.test(arq.type)) {
          campo.value = '';
          avisar('Use uma imagem PNG, JPG ou WEBP.');
          return null;
        }
        if (arq.size > 10 * 1024 * 1024) {
          campo.value = '';
          avisar('A imagem passa de 10 MB. Exporte em tamanho menor.');
          return null;
        }
        return URL.createObjectURL(arq);
      };
      // foto nova aparece na prévia e nas miniaturas de cada moldura
      $('[data-previa-foto]', perfil)?.addEventListener('change', (e) => {
        const url = imagemValida(e.target);
        if (!url) return;
        $$('[data-retrato] .quadro__passe', perfil).forEach((passe) => {
          const img = new Image();
          img.src = url;
          img.alt = '';
          passe.replaceChildren(img);
        });
        const remover = $('[data-remover-foto]', perfil);
        if (remover) remover.checked = false;
      });
      const fundo = $('[data-previa-wallpaper]', perfil);
      $('[data-previa-wallpaper-arquivo]', perfil)?.addEventListener('change', (e) => {
        const url = imagemValida(e.target);
        if (!url) return;
        $('[data-previa-wallpaper-imagem]', fundo).style.backgroundImage = `url("${url}")`;
        fundo.classList.add('perfil__fundo--imagem');
        const remover = $('[data-remover-wallpaper]', perfil);
        if (remover) remover.checked = false;
      });
      $('[data-remover-wallpaper]', perfil)?.addEventListener('change', (e) => fundo.classList.toggle('perfil__fundo--imagem', !e.target.checked));
      // nome, especialidade e apelido escritos aparecem na prévia
      $$('[data-previa]', perfil).forEach((campo) =>
        campo.addEventListener('input', () => {
          const alvo = $(`[data-previa-${campo.dataset.previa}]`, perfil);
          const texto = campo.value.trim();
          if (campo.dataset.previa === 'apelido') {
            alvo.hidden = !texto;
            $('span', alvo).textContent = texto;
          } else alvo.textContent = texto;
        })
      );
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

    return () => {
      clearInterval(relogio);
      clearTimeout(consulta);
    };
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
      // Site atualizado enquanto a aba estava aberta (style.css/app.js com outro ?v=)? Carrega a página
      // inteira: trocar só o conteúdo traria o HTML novo com o estilo velho (foi o que deixou o sino gigante).
      const versoes = (d) => [...d.querySelectorAll('link[rel="stylesheet"][href*="assets/css/"], script[src*="assets/js/"]')]
        .map((el) => el.getAttribute('href') || el.getAttribute('src')).join(' ');
      if (versoes(doc) !== versoes(document)) {
        location.href = pagina.url + hash;
        return;
      }
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
