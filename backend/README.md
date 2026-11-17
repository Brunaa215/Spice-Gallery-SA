# Spice Gallery: back-end

Back-end em PHP puro (MVC), PDO e MySQL, organizado igual ao CRUD_COMPLETO (Estoque Didático MVC).
As telas são as do protótipo em `../frontend`, convertidas em views PHP.

## Requisitos
PHP 8.1+ com as extensões `pdo_mysql`, `mbstring` e `fileinfo` · MySQL 5.7+ ou MariaDB 10.3+ (o do XAMPP serve)

## Instalação
1. Crie o banco e as tabelas: `mysql -u root -p < sql/schema.sql`
   (já importou o `banco de dados SA.sql` original? rode `sql/atualizar.sql` no lugar).
2. Opcional, para ver o site cheio: `mysql -u root -p < sql/dados_exemplo.sql`
   (artistas `lia@spice.com`, `caio@spice.com`, `nina@spice.com`, `rafa@spice.com`, `bento@spice.com`, senha `Spice@12345`).
3. Configure a conexão por variáveis de ambiente (padrões entre parênteses):
   `DB_HOST` (127.0.0.1), `DB_NAME` (spice_galery), `DB_USER` (root), `DB_PASS` (vazio).
4. Aponte o servidor web para a pasta `public/`, ou para testar localmente:
   `cd public && php -S localhost:8080`
5. Acesse `http://localhost:8080`. A curadoria entra com `admin@spice.com` / `Admin@12345!`.
   **Troque a senha no primeiro acesso** (Minha sala → Minha senha).

No XAMPP: copie a pasta `backend` para `C:\xampp\htdocs\spice` e abra `http://localhost/spice/public/`.

## Estrutura
```
app/
  bootstrap.php     autoloader, sessão e funções de apoio (e, url, go, flash, brl, nota...)
  Core/             Auth, Csrf, Database, View, Upload
  Models/           um por tabela: Usuario, Obra, Leilao, Avaliacao, Lance
  Controllers/      Home, Auth, Obra, Artista, Avaliacao, Leilao, Curadoria, Usuario
  Views/            layout/ (topo e rodapé), partials/, uma pasta por controller
public/             única pasta exposta na web (index.php + assets + uploads)
sql/                schema.sql, atualizar.sql, dados_exemplo.sql
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
| `lances.save` | POST | dá um lance | |
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

Segurança herdada do CRUD: roteador com lista fechada de rotas e métodos, token CSRF em todo POST,
consultas preparadas (PDO), `htmlspecialchars` em tudo que vem do banco, sessão com `httponly`/`samesite`
e `app/` e `sql/` bloqueados por `.htaccess`.

## O que mudou em relação ao SQL e ao front originais
- Colunas novas (marcadas `[NOVO]` no `schema.sql`): `Usuario.ativo`, `Usuario.aceiteTermosEm`,
  `Usuario.criadoEm`, `Obra.categoria`, `Obra.criadoEm`, `Leilao.incremento`. Eram as lacunas
  listadas no README do front.
- `public/assets/js/app.js`: removido o bloco `PROTÓTIPO`; o lance agora é enviado ao servidor (o
  navegador só faz a checagem rápida); mensagens do PHP (flash) aparecem no aviso flutuante.
- `public/assets/css/style.css`: no fim, estilos do botão "Sair" e das tabelas da curadoria.
