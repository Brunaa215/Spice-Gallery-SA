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
   **Banco criado antes da carteira de créditos?** Rode uma vez `mysql -u root -p < sql/atualizar_creditos.sql`
   (acrescenta as colunas e a tabela novas, dá R$ 10 a cada artista e cobre com créditos os lances que já estavam na frente).
3. Configure a conexão por variáveis de ambiente (padrões entre parênteses):
   `DB_HOST` (127.0.0.1), `DB_PORT` (3306), `DB_NAME` (spice_galery), `DB_USER` (root), `DB_PASS` (vazio).
4. Rode o servidor **apontando para a pasta `frontend`** (não na raiz do projeto nem na `backend`).
   Da raiz do projeto: `php -S localhost:8000 -t frontend`
   (ou `cd frontend` e depois `php -S localhost:8000`). Rodando na raiz, `http://localhost:8000` dá
   "Not Found" e o navegador passa a enxergar a pasta `backend/`.
5. Acesse `http://localhost:8000`. A curadoria entra com `admin@spice.com` / `Admin@12345!`.
   **Troque a senha no primeiro acesso** (Minha sala → Minha senha).

No XAMPP: copie a pasta inteira `Spice-Gallery-SA` (com `frontend` e `backend` lado a lado) para
`C:\xampp\htdocs\` e abra `http://localhost/Spice-Gallery-SA/frontend/`.
O Live Server do VS Code não roda PHP: com ele só abre o protótipo estático.

## Estrutura
```
../frontend/        única pasta exposta na web: index.php, assets/, uploads/ e prototipo/ (as telas .html originais)
app/
  bootstrap.php     autoloader, sessão e funções de apoio (e, url, go, flash, brl, nota...)
  Core/             Auth, Csrf, Database, View, Upload
  Models/           um por tabela: Usuario, Obra, Leilao, Avaliacao, Lance, Movimentacao
  Controllers/      Home, Auth, Obra, Artista, Avaliacao, Leilao, Carteira, Curadoria, Usuario
  Views/            layout/ (topo e rodapé), partials/, uma pasta por controller
sql/                schema.sql, dados_exemplo.sql, atualizar_creditos.sql (bancos antigos)
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
| `artistas` / `artistas.show&id=` | GET | lista de artistas / sala do artista | `artista.html` |
| `avaliacoes.save` / `avaliacoes.delete` | POST | cria ou edita / exclui a própria avaliação | |
| `leiloes` / `leiloes.show&id=` | GET | lista de lotes / um leilão | `leiloes.html` / `leilao.html` |
| `lances.save` | POST | dá um lance (com créditos) | |
| `carteira` / `carteira.comprar` | GET / POST | saldo, extrato, obras arrematadas / compra simulada de créditos | (novo) |
| `login`, `login.submit`, `register`, `logout`, `password`, `password.save` | | conta | `entrar.html` |
| `curadoria`, `curadoria.leilao`, `usuarios`, `usuarios.toggle` | | só ADMIN | (novo) |

## Regras de negócio no servidor
- **RN-01/02** cadastro sempre como `ARTISTA`, senha com `password_hash`, aceite dos termos gravado em `aceiteTermosEm`.
- **RN-03** obra exige título, descrição, categoria e imagem (PNG/JPG/WEBP até 10 MB, tipo conferido pelo conteúdo do arquivo).
- **RN-04/05** artista não avalia nem dá lance na própria obra.
- **RN-06** só quem escreveu edita ou exclui a avaliação (uma avaliação por pessoa em cada obra).
- **RN-07** leilão nasce `AGUARDANDO_CURADORIA`; a curadoria aprova ou reprova. Aprovado, ele fica
  "em breve", "ao vivo" ou "encerrado" sozinho, pelas datas.
- **RN-08** lance ≥ valor inicial (primeiro) ou ≥ maior lance + incremento, dentro do prazo, validado
  numa transação com `SELECT ... FOR UPDATE` para dois lances simultâneos não passarem com o mesmo mínimo.

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

Segurança herdada do CRUD: roteador com lista fechada de rotas e métodos, token CSRF em todo POST,
consultas preparadas (PDO), `htmlspecialchars` em tudo que vem do banco, sessão com `httponly`/`samesite`
e `app/` e `sql/` bloqueados por `.htaccess`.

## O que mudou em relação ao SQL e ao front originais
- Colunas novas (marcadas `[NOVO]` no `schema.sql`): `Usuario.ativo`, `Usuario.aceiteTermosEm`,
  `Usuario.criadoEm`, `Obra.categoria`, `Obra.criadoEm`, `Leilao.incremento`. Eram as lacunas
  listadas no README do front. Da carteira: `Usuario.saldo`, `Leilao.id_vencedor`, `Leilao.liquidadoEm`
  e a tabela `Movimentacao`.
- `frontend/assets/js/app.js`: removido o bloco `PROTÓTIPO`; o lance agora é enviado ao servidor (o
  navegador só faz a checagem rápida); mensagens do PHP (flash) aparecem no aviso flutuante.
- `frontend/assets/css/style.css`: no fim, estilos do botão "Sair" e das tabelas da curadoria.
