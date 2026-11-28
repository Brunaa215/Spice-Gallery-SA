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

## Site parado, com foco nas obras

O site PHP **não carrega mais a camada de animação** (`movimento.js`, GSAP e Lenis). Saíram:
- a cortina de abertura com contador e a cortina entre páginas;
- o cursor próprio, os botões "magnéticos", a rolagem suave do Lenis e a barra de leitura;
- da home: o mergulho dentro do quadro, a faixa de categorias correndo, o selo girando e a poeira dourada;
- o corredor de destaques preso à rolagem, que virou uma parede parada ("As mais bem avaliadas");
- os quadros que inclinavam e ondulavam sob o mouse, as letras subindo nos títulos e os números contando;
- o ponto pulsando em "Leilões", os dígitos do cronômetro caindo a cada segundo e a arte "respirando" no login.

Para as obras aparecerem mais:
- o fundo é o papel de parede de peônias (`assets/img/papel-de-parede.webp`) bem apagado por um véu
  da cor da parede (`--veu-parede` no `style.css`: 0 = papel inteiro, 1 = parede lisa);
- saíram as listras e a moldura dourada fixa em volta da janela;
- os quadros ficam retos; os pendurados (`.quadro--pendurado`) ficam num prego de latão com um arame
  fino (era uma fita rosa com laço). Ao passar o mouse (ou tocar), o quadro **balança no prego** como
  um pêndulo, para o lado contrário de onde o mouse entrou, e vai parando em ~2 s (animação `balancar`,
  ligada por um ouvinte só no `app.js`). A obra em 3D da página da obra não balança;
- a galeria tem 3 colunas (eram 4);
- a obra na página dela e na do leilão ficou maior;
- os três quadros do topo da home não se cobrem mais.

O papel de parede é um ladrilho de 436×737 feito da foto enviada. As bordas foram misturadas (a de
baixo com a de cima, a da direita com a da esquerda) para ele se repetir sem deixar linha.

**Cores:** paredes em marfim quente (`--parede` `#f8f2ec`, `--parede-2` `#f1e6df`). O cabeçalho é uma
faixa de rosa bem leve (`--topo-1` `#fcf2f2` → `--topo-2` `#f8e8e9`, também no menu aberto do celular). O rosa
fica nos detalhes: cabeçalho, botões, nomes dos artistas, ponto de "ao vivo". O logo tem 3rem (2.1rem em tela baixa).

Os títulos grandes (Carteira, Notificações, Curadoria, Contas) usam duas palavras inteiras ("Sua
*carteira*"): a segunda vai para a linha de baixo em dourado, então partir uma palavra ao meio
virava "Car / teira".

**Só animações essenciais.** O `<html>` do site tem a classe `calmo` (em `layout/topo.php`), e o bloco
"site calmo" do `style.css` desliga toda transição e animação, exceto as que ajudam a entender o que
aconteceu:
- **troca de aba (página):** o `app.js` troca a página dentro de `document.startViewTransition()`. A
  página que sai some subindo um pouco e a nova sobe suave (0,45 s); voltar no histórico faz o caminho
  contrário. O cabeçalho (`view-transition-name: topo`) fica parado. Recarga por formulário usa a mesma
  animação (`@view-transition`), e navegador sem a API só faz a página nova subir (`.entrando`);
- **abas Entrar / Criar conta:** o painel novo sobe suave;
- **passarela de obras no login:** o painel da esquerda mostra as obras publicadas mais novas (até 8,
  vindas do `AuthController::form`). Cada uma fica 7 s: entra pela direita, desliza devagar e sai pela
  esquerda enquanto a próxima entra. A obra aparece inteira, na mesma moldura que tem na galeria
  (`moldura($id)`: ouro, laca verde, laca rosa ou camafeu), pendurada num prego, sobre um fundo
  desfocado dela mesma, com título e artista no alto. É `ligarPassarela()` no `app.js` com as animações
  `passar`/`deslizar` do CSS. Sem obras publicadas, volta a imagem fixa; com "reduzir movimento", fica
  só a primeira, parada;
- **obra em 3D (página da obra):** o quadro gira em 3D seguindo o mouse (até 13° para os lados e 9° para
  cima e para baixo), com a obra um pouco à frente do passe-partout e um reflexo de vidro. Ao abrir a
  página ele entra girado e assenta; no celular inclina de leve conforme a rolagem. É `ligarObra3d()`
  no `app.js` (ligado por `data-obra-3d` em `obras/show.php`), só com transform num
  `requestAnimationFrame` que para quando o quadro para;
- o aviso flutuante aparecendo e o lance novo piscando no histórico do leilão ao vivo.

Hover, foco e menu mudam na hora. Links para âncoras pulam direto, e `scroll-padding-top` faz o alvo
parar abaixo do cabeçalho fixo. Quem tem "reduzir movimento" no sistema não vê nenhuma dessas animações.

Os arquivos de animação continuam em `assets/js` (e o CSS deles no fim do `style.css`) só porque o protótipo
estático ainda usa. Para voltar com as animações no site, basta recolocar os `<script>` em
`backend/app/Views/layout/rodape.php` e a cortina em `layout/topo.php`.

O texto abaixo é o README original do protótipo.

## Navegação sem recarregar

Links internos não recarregam a página: o `app.js` baixa a página nova com `fetch`, o conteúdo do `<body>` (cabeçalho, `main`, rodapé) é trocado e o comportamento é montado de novo (no protótipo, uma cortina cobre a tela e as animações também são remontadas). Voltar e avançar do navegador funcionam e reabrem onde a pessoa estava. Passar o mouse num link já começa a baixar a página.

Com o PHP nada muda: cada rota continua devolvendo HTML completo, como devolveria sem isso. Regras para não quebrar:

- Elemento que deve sobreviver à troca (fora do conteúdo da página) leva `data-fixo`.
- Código que liga coisas em elementos da página vai dentro de `montar()` no `app.js` (ou da montagem no `movimento.js`), não solto no topo do arquivo, senão só roda na primeira página.
- `<script>` dentro do `<body>` de uma página não roda na troca. Comportamento novo vai para o `app.js`.
- Resposta que não é HTML, erro 404/500 ou falha de rede viram navegação normal automaticamente. Formulários continuam enviando normalmente (recarregam).
- O `style.css` e o `app.js` são incluídos com `asset()` no PHP (`style.css?v=<data do arquivo>`). Se o site for atualizado com uma aba aberta, a próxima troca de página percebe a versão nova e recarrega a página inteira. Sem isso, o HTML novo chegava com o estilo antigo (foi o que deixou o sino das notificações gigante).

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
