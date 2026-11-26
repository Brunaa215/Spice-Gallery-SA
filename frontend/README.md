# Spice Gallery: front-end

**Esta pasta está ligada ao backend.** Ela é a pasta que o navegador acessa: o `index.php` daqui recebe
todas as páginas e chama o PHP de `../backend` (controllers, models, banco). O visual (`assets/`) é
usado pelo site de verdade, e as imagens enviadas pelos artistas caem em `uploads/`.

Para abrir o site (com o MySQL do XAMPP ligado e o banco importado, veja `../backend/README.md`):

```
php -S localhost:8000 -t frontend
```

(rodado da raiz do projeto; ou `cd frontend` e `php -S localhost:8000`) e acesse `http://localhost:8000`.
Sem o `-t frontend`, rodando da raiz, a página inicial dá "Not Found". O Live Server do VS Code não roda PHP, então não serve para isso.

As telas `.html` originais do protótipo estão em `prototipo/`, só como referência. Elas viraram as
views PHP em `../backend/app/Views` (tabela abaixo) e mostram dados fixos.

O texto abaixo é o README original do protótipo.

## Navegação sem recarregar

Links internos não recarregam a página: o `app.js` baixa a página nova com `fetch`, a cortina cobre a tela, o conteúdo do `<body>` (cabeçalho, `main`, rodapé) é trocado e o comportamento e as animações são montados de novo. Voltar e avançar do navegador funcionam e reabrem onde a pessoa estava. Passar o mouse num link já começa a baixar a página.

Com o PHP nada muda: cada rota continua devolvendo HTML completo, como devolveria sem isso. Regras para não quebrar:

- Elemento que deve sobreviver à troca (fora do conteúdo da página) leva `data-fixo`.
- Código que liga coisas em elementos da página vai dentro de `montar()` no `app.js` (ou da montagem no `movimento.js`), não solto no topo do arquivo, senão só roda na primeira página.
- `<script>` dentro do `<body>` de uma página não roda na troca. Comportamento novo vai para o `app.js`.
- Resposta que não é HTML, erro 404/500 ou falha de rede viram navegação normal automaticamente. Formulários continuam enviando normalmente (recarregam).
- O `style.css`, o `app.js` e o `movimento.js` são incluídos com `asset()` no PHP (`style.css?v=<data do arquivo>`). Se o site for atualizado com uma aba aberta, a próxima troca de página percebe a versão nova e recarrega a página inteira. Sem isso, o HTML novo chegava com o estilo antigo (foi o que deixou o sino das notificações gigante).

## Celular, tablet e zoom

Testado com Playwright em 1440×900, 1366×768, tablet 820×1180, iPhone 390×844, Android 360×740 e zoom do navegador de 150%, 200% e 300% (inclusive dando zoom no meio da página). As seções presas da home (mergulho, corredor, regras) só existem com pelo menos 560px de altura de tela; abaixo disso (zoom forte, celular deitado) elas viram layout comum e o corredor passa a deslizar para o lado. Ao dar ou tirar zoom, a página volta para o mesmo ponto de leitura.

## Páginas

| Arquivo | O que é | Vira no PHP (MVC) |
|---|---|---|
| `index.html` | Home: hero, mergulho, corredor de destaques, acervo com filtros, leilão em destaque, regras | `views/home.php` |
| `obra.html` | Detalhe da obra + avaliações (nota 1 a 5 e comentário) | `views/obra/mostrar.php` |
| `artista.html` | Galeria individual do artista | `views/artista/mostrar.php` |
| `leiloes.html` | Lista de leilões (ao vivo, em breve, encerrado) | `views/leilao/listar.php` |
| `leilao.html` | Leilão com contagem regressiva e lances | `views/leilao/mostrar.php` |
| `publicar.html` | Upload de obra (e leilão opcional) | `views/obra/criar.php` |
| `entrar.html` | Login e cadastro em abas | `views/auth/entrar.php` |

Cabeçalho e rodapé são iguais em todas as páginas. No PHP eles viram `views/partials/topo.php` e `views/partials/rodape.php` (há um comentário `<!-- PHP: ... -->` no lugar de cada `include`). Os laços (`foreach`) também estão marcados com comentários `<!-- PHP: ... -->`.

## Formulários e banco

Os `name` dos campos já usam os nomes das colunas de `banco de dados SA.sql`.

| Formulário | `action` | Campos |
|---|---|---|
| Login | `entrar.php` | `email`, `senha` → conferir com `password_verify` contra `Usuario.senhaHash` |
| Cadastro | `cadastrar.php` | `nome`, `email`, `senha` (gravar `password_hash`), `aceiteTermos` |
| Publicar | `publicar.php` (multipart) | `imagem` (vira `urlImagem`), `titulo`, `descricao`, `categoria`, `aceiteTermos`, `disponivelLeilao`, `valorInicial`, `incremento`, `dataInicio`, `dataFim` |
| Avaliar | `avaliar.php` | `id_obra`, `id_avaliacao` (vazio = nova), `nota`, `comentario` |
| Excluir avaliação | `avaliacao-excluir.php` | `id_avaliacao` |
| Lance | `lance.php` | `id_leilao`, `valor` |

**O banco atual não tem colunas para alguns campos que as regras de negócio pedem:**

- `Obra.categoria` (RN-03 exige categoria)
- `Leilao.incremento` (RN-08 fala em incremento mínimo)
- marcação "disponível para leilão" e aprovação da curadoria (RN-07), se o `status` da obra não cobrir isso
- registro do aceite dos termos (RN-02)

## Antes de ligar o PHP

1. Em `assets/js/app.js`, apague o bloco marcado `PROTÓTIPO` no fim do arquivo. Hoje ele segura todo envio de formulário e mostra um aviso.
2. O lance em `leilao.html` é simulado no navegador. O servidor precisa validar de novo: valor ≥ lance atual + incremento, leilão dentro do prazo e que quem dá o lance não é o autor da obra (RN-05, RN-08). Validação no navegador não basta.
3. Mostrar/ocultar o formulário de avaliação, os botões Editar/Excluir e o formulário de lance conforme o usuário logado (os comentários no HTML dizem onde).
4. Escapar com `htmlspecialchars` tudo o que vier do banco (títulos, comentários, nomes).

## Estrutura

```
assets/css/style.css       todo o visual (tokens de cor no topo; .noite troca para a paleta noturna)
assets/js/app.js           comportamento: navegação sem recarregar, filtros, contagem, lance, upload, abas, senha
assets/js/movimento.js     animações (GSAP + Lenis); se faltar, o site funciona sem animação
assets/js/vendor/          GSAP 3.13 (ScrollTrigger, SplitText, Flip, ScrambleText) e Lenis 1.3.4, locais
assets/img/obras/          obras de exemplo em WebP (placeholders; no PHP vem de Obra.urlImagem)
assets/img/moldura-*.svg   molduras rococó (border-image de 9 partes: ouro, laca rosa-bebê e laca verde-sálvia) e crista.svg
```

Quem tem "reduzir movimento" ligado no sistema recebe o site sem animações. Em máquinas com até 4 núcleos, a poeira dourada e a ondulação dos quadros ficam desligadas.

**Desempenho, para quem for mexer nas animações:** anime só `transform` e `opacity`. `filter`, `clip-path`, `box-shadow`, `background-size` e `width`/`height` repintam a área inteira a cada frame e foram exatamente o que travava. A obra do mergulho está embutida como `<svg>` no `index.html` de propósito: ampliada 22×, um `<img>` viraria um bitmap gigante a cada frame. As outras obras são WebP, e não SVG, porque SVG com filtro (desfoque, turbulência) é re-executado toda vez que o quadro é redesenhado; no PHP, prefira JPG/WebP para os uploads. Também não anime um `<svg>` direto: gire ou mova uma `div` em volta dele. As fontes (Cormorant Garamond, EB Garamond, Marcellus SC e Pinyon Script, no tema rococó) vêm do Google Fonts. Sem internet, o navegador usa fontes parecidas no lugar.
