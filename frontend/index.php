<?php
declare(strict_types=1);
// FRONT CONTROLLER: todas as requisições HTTP passam por este arquivo.
// Aqui o frontend se liga ao backend: esta pasta (frontend/) é a que o navegador acessa,
// com o visual em assets/; as regras, o banco e as telas em PHP ficam em ../backend/app.
require dirname(__DIR__) . '/backend/app/bootstrap.php';

use App\Controllers\ArtistaController;
use App\Controllers\AuthController;
use App\Controllers\AvaliacaoController;
use App\Controllers\CarteiraController;
use App\Controllers\CuradoriaController;
use App\Controllers\HomeController;
use App\Controllers\LeilaoController;
use App\Controllers\ObraController;
use App\Controllers\PerfilController;
use App\Controllers\UsuarioController;
use App\Models\Leilao;

// Roteador explícito evita executar métodos arbitrários a partir da URL.
// Página do protótipo (frontend/prototipo) => rota:
//   index.html => home | obra.html => obras.show | artista.html => artistas.show
//   leiloes.html => leiloes | leilao.html => leiloes.show | publicar.html => obras.create | entrar.html => login
$routes = [
    'home'              => ['GET',  HomeController::class,      'index'],

    'login'             => ['GET',  AuthController::class,      'form'],
    'login.submit'      => ['POST', AuthController::class,      'login'],
    'register'          => ['POST', AuthController::class,      'register'],
    'logout'            => ['POST', AuthController::class,      'logout'],
    'password'          => ['GET',  AuthController::class,      'passwordForm'],
    'password.save'     => ['POST', AuthController::class,      'changePassword'],

    'obras.show'        => ['GET',  ObraController::class,      'show'],
    'obras.create'      => ['GET',  ObraController::class,      'form'],
    'obras.edit'        => ['GET',  ObraController::class,      'form'],
    'obras.save'        => ['POST', ObraController::class,      'save'],
    'obras.delete'      => ['POST', ObraController::class,      'delete'],

    'artistas'          => ['GET',  ArtistaController::class,   'index'],
    'artistas.show'     => ['GET',  ArtistaController::class,   'show'],
    'perfil'            => ['GET',  PerfilController::class,    'form'],
    'perfil.save'       => ['POST', PerfilController::class,    'save'],

    'avaliacoes.save'   => ['POST', AvaliacaoController::class, 'save'],
    'avaliacoes.delete' => ['POST', AvaliacaoController::class, 'delete'],

    'leiloes'           => ['GET',  LeilaoController::class,    'index'],
    'leiloes.show'      => ['GET',  LeilaoController::class,    'show'],
    'leiloes.estado'    => ['GET',  LeilaoController::class,    'estado'],   // JSON: leilão ao vivo
    'lances.save'       => ['POST', LeilaoController::class,    'bid'],

    'carteira'          => ['GET',  CarteiraController::class,  'index'],
    'carteira.comprar'  => ['POST', CarteiraController::class,  'comprar'],

    'curadoria'         => ['GET',  CuradoriaController::class, 'index'],
    'curadoria.leilao'  => ['POST', CuradoriaController::class, 'decide'],
    'usuarios'          => ['GET',  UsuarioController::class,   'index'],
    'usuarios.toggle'   => ['POST', UsuarioController::class,   'toggle'],
];

$route = (string) ($_GET['r'] ?? 'home');
if (!isset($routes[$route])) {
    http_response_code(404);
    exit('Página não encontrada.');
}

[$method, $controller, $action] = $routes[$route];
if ($_SERVER['REQUEST_METHOD'] !== $method) {
    header('Allow: ' . $method);
    http_response_code(405);
    exit('Método não permitido.');
}

try {
    // Antes de qualquer página: leilões que passaram do horário final fecham aqui
    // (o vencedor paga com créditos e o artista recebe). Assim o leilão fecha sozinho.
    (new Leilao())->liquidarEncerrados();
    (new $controller())->$action();
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    exit('Falha interna. Consulte o registro de erros do PHP.');
}
