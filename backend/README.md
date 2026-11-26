# Spice Gallery: back-end

Back-end em PHP puro (MVC), PDO e MySQL, organizado igual ao CRUD_COMPLETO (Estoque Didático MVC).
As telas são as do protótipo do front, convertidas em views PHP (`app/Views`).

**Como front e back se ligam:** a pasta `../frontend` é a única que o navegador acessa (faz o papel da
`public/` do CRUD). Lá ficam o `index.php` (front controller), o visual (`assets/`: CSS, JS, imagens) e
as imagens enviadas (`uploads/`). O `frontend/index.php` carrega `backend/app/bootstrap.php`; daí em
diante controllers, models e banco ficam aqui no `backend/`, fora do alcance do navegador.

## Requisitos
PHP 8.1+ com as extensões `pdo_mysql`, `mbstring` e `fileinfo` · MySQL 5.7+ ou MariaDB 10.3+ (o do XAMPP serve)

## Instalação
1. Crie o banco e as tabelas: `mysql -u root -p < sql/schema.sql`.
2. Opcional, para ver o site cheio: `mysql -u root -p < sql/dados_exemplo.sql`
   (artistas `lia@spice.com`, `caio@spice.com`, `nina@spice.com`, `rafa@spice.com`, `bento@spice.com`, senha `Spice@12345`;
   cada um começa com R$ 5.010 em créditos).
   **Banco criado antes?** Rode uma vez cada script que ainda não rodou, nesta ordem:
   1. `mysql -u root -p < sql/atualizar_creditos.sql` (carteira: colunas e tabela novas, R$ 10 a cada artista,
      créditos para cobrir os lances que já estavam na frente);
   2. `mysql -u root -p < sql/atualizar_leilao_minutos.sql` (leilão por minutos e "começa quando a curadoria
      aprovar"; leilões pendentes que já tinham perdido a data passam a começar na aprovação);
   3. `mysql -u root -p < sql/atualizar_perfil.sql` (perfil do artista: colunas novas em `Usuario` e a tabela
      `RedeSocial`);
   4. `mysql -u root -p < sql/atualizar_notificacoes.sql` (notificações de fim de leilão e `Usuario.contatoVenda`;
      leilões que já tinham fechado recebem os avisos também);
   5. `mysql -u root -p < sql/atualizar_sem_curadoria.sql` (leilões sem aprovação da curadoria: os que
      esperavam aprovação começam na hora, com a mesma duração, ou ficam marcados para a data escolhida;
      os marcados que já passaram do fim sem lances são apagados).
   Se um script já rodou, ele para no primeiro passo ("Duplicate column") sem mexer em nada. O passo 5 pode
   rodar de novo sem problema (não acha mais nada para mudar).
3. Configure a conexão por variáveis de ambiente (padrões entre parênteses):
   `DB_HOST` (127.0.0.1), `DB_PORT` (3306), `DB_NAME` (spice_galery), `DB_USER` (root), `DB_PASS` (vazio).
4. Rode o servidor **apontando para a pasta `frontend`** (não na raiz do projeto nem na `backend`).
   Da raiz do projeto: `php -S localhost:8000 -t frontend`
   (ou `cd frontend` e depois `php -S localhost:8000`). Rodando na raiz, `http://localhost:8000` dá
   "Not Found" e o navegador passa a enxergar a pasta `backend/`.
5. Acesse `http://localhost:8000`. A curadoria entra com `admin@spice.com` / `Admin@12345!`.
   **Troque a senha no primeiro acesso** (Perfil → Editar perfil → Trocar minha senha).

No XAMPP: copie a pasta inteira `Spice-Gallery-SA` (com `frontend` e `backend` lado a lado) para
`C:\xampp\htdocs\` e abra `http://localhost/Spice-Gallery-SA/frontend/`.
O Live Server do VS Code não roda PHP: com ele só abre o protótipo estático.

**MySQL do XAMPP caindo sozinho?** Se o MySQL some sem aviso, ou se o `mysql_error.log` mostra
`Assertion failure in file ...os0file.cc` / `Failing assertion: slot`, a E/S assíncrona nativa do Windows está
quebrando o InnoDB na máquina. Acrescente `innodb_use_native_aio=0` logo abaixo de `[mysqld]` em
`C:\xampp\mysql\bin\my.ini` e religue o MySQL. Se o log também acusar páginas com "log sequence number ...
in the future" ou o arquivo `data\multi-master.info` cheio de texto, o banco já foi danificado: exporte tudo
(com `--innodb-force-recovery=1`), recrie a pasta `data` a partir de `C:\xampp\mysql\backup` e importe de volta.

## Estrutura
```
../frontend/        única pasta exposta na web: index.php, assets/, uploads/ e prototipo/ (as telas .html originais)
app/
  bootstrap.php     autoloader, sessão e funções de apoio (e, url, go, flash, brl, nota...)
  Core/             Auth, Csrf, Database, View, Upload
  Models/           um por tabela: Usuario, Obra, Leilao, Avaliacao, Lance, Movimentacao, RedeSocial, Notificacao
  Controllers/      Home, Auth, Obra, Artista, Perfil, Avaliacao, Leilao, Carteira, Notificacao, Curadoria, Usuario
  Views/            layout/ (topo e rodapé), partials/, uma pasta por controller
sql/                schema.sql, dados_exemplo.sql, atualizar_*.sql (para bancos antigos)
```
Os nomes das pastas em `app/` devem coincidir com os namespaces (`App\Core` → `app/Core`),
pois o autoloader diferencia maiúsculas de minúsculas no Linux.

## Rotas (`index.php?r=...`)
| Rota | Método | O que faz | Era no protótipo |
|---|---|---|---|
| `home` | GET | galeria, destaques, leilão em destaque | `index.html` |
| `obras.show&id=` | GET | obra + avaliações | `obra.html` |
| `obras.create` / `obras.edit&id=` | GET | publicar / editar obra (logado) | `publicar.html` |
| `obras.save` / `obras.delete` | POST | grava (com upload e leilão opcional) / exclui | |
| `artistas` / `artistas.show&id=` | GET | lista de artistas / sala do artista (perfil, obras e coleção) | `artista.html` |
| `perfil` | GET | aba Perfil: a própria sala, como os outros a veem (com obras, leilões e coleção) | (novo) |
| `perfil.editar` / `perfil.save` | GET / POST | botão "Editar perfil": foto, moldura, papel de parede, redes... (salvou, volta para `perfil`) | (novo) |
| `notificacoes` | GET | avisos de fim de leilão, com o contato entre comprador e artista (abrir marca como lidas) | (novo) |
| `avaliacoes.save` / `avaliacoes.delete` | POST | cria ou edita / exclui a própria avaliação | |
| `leiloes` / `leiloes.show&id=` | GET | lista de lotes / um leilão | `leiloes.html` / `leilao.html` |
| `leiloes.estado&id=&v=` | GET (JSON) | leilão ao vivo: o `app.js` pergunta a cada 2 s e recebe painel e histórico novos se algo mudou | (novo) |
| `lances.save` | POST | dá um lance (com créditos) | |
| `carteira` / `carteira.comprar` | GET / POST | saldo, extrato, obras arrematadas / compra simulada de créditos | (novo) |
| `login`, `login.submit`, `register`, `logout`, `password`, `password.save` | | conta | `entrar.html` |
| `curadoria`, `curadoria.leilao`, `usuarios`, `usuarios.toggle` | | só ADMIN | (novo) |

## Regras de negócio no servidor
- **RN-01/02** cadastro sempre como `ARTISTA`, senha com `password_hash`, aceite dos termos gravado em `aceiteTermosEm`.
- **RN-03** obra exige título, descrição, categoria e imagem (PNG/JPG/WEBP até 10 MB, tipo conferido pelo conteúdo do arquivo).
- **RN-04/05** artista não avalia nem dá lance na própria obra.
- **RN-06** só quem escreveu edita ou exclui a avaliação (uma avaliação por pessoa em cada obra).
- **RN-07** (mudada pelo grupo) o leilão **não passa mais pela curadoria**: nasce `APROVADO` e fica
  "em breve", "ao vivo" ou "encerrado" sozinho, pelas datas. `AGUARDANDO_CURADORIA` e `REPROVADO` só existem
  em dados antigos.
- **RN-08** lance ≥ valor inicial (primeiro) ou ≥ maior lance + incremento, dentro do prazo, validado
  numa transação com `SELECT ... FOR UPDATE` para dois lances simultâneos não passarem com o mesmo mínimo.

## Leilão
- **Duração** de 1 minuto a 30 dias (número + minutos/horas/dias).
- **Começar o leilão (RN-07):** quem começa é o próprio artista, no formulário da obra ("Disponível para
  leilão"). **Começar agora** (o padrão) abre o leilão no momento em que ele salva; **Em data e hora marcadas**
  abre sozinho na data escolhida. Não há aprovação: a rota `curadoria.leilao` e o painel de decisão só servem
  para leilões antigos que ficaram pendentes.
- Enquanto o leilão marcado não começa, o artista **altera ou cancela** editando a obra (`Leilao::editavel`).
  Se ele começar enquanto o artista edita, o servidor recusa a mudança e avisa (campo `leilaoAgendado`).
  Obra cujo leilão fechou sem lances (ou foi reprovado, em dados antigos) pode ir a leilão de novo. Em andamento,
  ou vendido, fica travado.
- `Leilao.abreNaAprovacao` ficou de dados antigos (o leilão começava na aprovação da curadoria, com a mesma
  duração); leilão novo grava sempre 0.
- **Ao vivo:** a página do lote pergunta à rota `leiloes.estado` a cada 2 s (5 s antes de abrir). O painel e o
  histórico são partials (`Views/partials/leilao_painel.php` e `leilao_historico.php`): a página os desenha e a
  rota devolve os mesmos HTMLs quando a versão (hash dos dois) muda. Lance novo, "seu lance foi coberto", abertura,
  prorrogação e martelo aparecem sem recarregar.
- **Prorrogação:** lance que chega com menos de 1 minuto para o fim empurra o fim para 1 minuto depois do lance
  (`Leilao::PRORROGACAO_SEGUNDOS`; 0 desliga).

## Carteira de créditos (dinheiro fictício)
- Toda conta nova ganha **R$ 10** em créditos (`Movimentacao::BONUS_BOAS_VINDAS`). Mais créditos são
  "comprados" na Carteira em pacotes de R$ 10 a R$ 1.000: compra simulada, sem pagamento. Teto de R$ 100.000.
- O saldo fica em `Usuario.saldo`; cada entrada ou saída vira uma linha em `Movimentacao` (o extrato).
  O saldo sempre bate com a soma do extrato e nunca fica negativo.
- **Lance precisa caber no crédito disponível** = saldo − lances em que a pessoa está na frente. Esse valor
  fica *reservado*: se alguém cobrir, volta a ficar disponível. Quem já está na frente não cobre o próprio lance.
  A conta é travada (`FOR UPDATE`) no lance, então dois lances simultâneos não usam o mesmo crédito.
- **Martelo:** a cada acesso, o `index.php` chama `Leilao::liquidarEncerrados()`. Cada leilão que passou do
  horário final fecha uma vez só (`Leilao.liquidadoEm`): o valor sai da carteira de quem venceu
  (`ARREMATE`) e entra na do artista (`VENDA`), e o vencedor fica em `Leilao.id_vencedor`.
  Não precisa de cron: o leilão fecha no primeiro acesso ao site depois do horário.
- Obra arrematada não pode mais ser excluída (ela foi paga).

## Perfil do artista e coleção
- Aba **Perfil** no menu (`perfil`): abre direto a própria sala (o cabeçalho com foto, apelido, estilos e redes,
  depois obras, leilões e coleção), no lugar da antiga "Minha sala". O formulário só aparece pelo botão
  **Editar perfil** (`perfil.editar`); ao salvar, volta para a sala.
- No formulário: nome artístico, apelido, especialidade, estilos (separados por vírgula, até 8,
  viram etiquetas), foto de perfil, **moldura da foto** (camafeu dourado, ouro entalhado, laca rosa, laca
  verde-sálvia ou sem moldura: as mesmas das obras), **papel de parede** da sala e links de redes sociais. A
  prévia à esquerda muda na hora, antes de salvar.
- Redes: aceita `@usuario` (vira o link da rede) ou o endereço inteiro, que precisa ser do domínio daquela rede
  (`RedeSocial::REDES`); "Site pessoal" aceita qualquer http(s). Os links abrem em nova aba.
- Fotos e papéis de parede vão para `frontend/uploads/perfis` (mesmas regras do upload de obra). Imagem trocada ou
  removida é apagada do disco. No HTML, caminhos de imagem passam por `imagemLocal()` antes de entrar num `style`.
- A foto aparece na sala (retrato na moldura) e ao lado do nome nas obras, nos leilões e nos comentários (`avatar()`).
- **Coleção:** obra arrematada aparece na sala de quem arrematou, na seção "Coleção" (`Obra::arrematadasPor`). O valor
  pago só o dono da sala vê. Na página da obra, "Arrematada por" leva até essa coleção.

## Notificações
- Quando o martelo bate (`Leilao::liquidar`, na mesma transação do pagamento), `Notificacao::avisarFechamento` cria:
  **ARREMATE** para quem venceu (com o contato do artista), **VENDA** para o artista (com o contato de quem
  arrematou), **NAO_VENCEU** para quem deu lance e não venceu, ou **SEM_LANCES** para o artista.
- Aba com sino no menu (número de não lidas). Na página do leilão ao vivo, o sino se atualiza no martelo sem recarregar.
- Contato = nome, e-mail da conta, `Usuario.contatoVenda` (WhatsApp/telefone opcional do Perfil, com link wa.me) e
  redes. Ele é lido do perfil na hora de mostrar (sempre o atual) e **só aparece para as duas partes da venda**:
  nunca nas páginas públicas. O site avisa isso no Perfil, no painel do leilão e no formulário da obra.
- A tabela guarda só os fatos (tipo, obra, valores, ids); o texto é montado em `Notificacao::texto()`.

Segurança herdada do CRUD: roteador com lista fechada de rotas e métodos, token CSRF em todo POST,
consultas preparadas (PDO), `htmlspecialchars` em tudo que vem do banco, sessão com `httponly`/`samesite`
e `app/` e `sql/` bloqueados por `.htaccess`.

## O que mudou em relação ao SQL e ao front originais
- Colunas novas (marcadas `[NOVO]` no `schema.sql`): `Usuario.ativo`, `Usuario.aceiteTermosEm`,
  `Usuario.criadoEm`, `Obra.categoria`, `Obra.criadoEm`, `Leilao.incremento`. Eram as lacunas
  listadas no README do front. Da carteira: `Usuario.saldo`, `Leilao.id_vencedor`, `Leilao.liquidadoEm`
  e a tabela `Movimentacao`. Do leilão por minutos: `Leilao.abreNaAprovacao`. Do perfil: `Usuario.apelido`,
  `especialidade`, `estilos`, `fotoPerfil`, `molduraFoto`, `wallpaper` e a tabela `RedeSocial`.
- `frontend/assets/js/app.js`: removido o bloco `PROTÓTIPO`; o lance agora é enviado ao servidor (o
  navegador só faz a checagem rápida); mensagens do PHP (flash) aparecem no aviso flutuante.
- `frontend/assets/css/style.css`: no fim, estilos do botão "Sair" e das tabelas da curadoria.
