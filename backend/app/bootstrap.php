<?php
declare(strict_types=1);

// Horário de Brasília para o PHP; o Database ajusta o MySQL para o mesmo fuso.
date_default_timezone_set('America/Sao_Paulo');

// Autoloader: App\Models\Obra -> app/Models/Obra.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix))
        return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file))
        require_once $file;
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/'
    ]);
    session_start();
}

/* ---------- requisição e resposta ---------- */

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function url(string $route, array $args = []): string
{
    return 'index.php?' . http_build_query(array_merge(['r' => $route], $args));
}
function go(string $route, array $args = [], string $hash = ''): never
{
    header('Location: ' . url($route, $args) . ($hash !== '' ? '#' . $hash : ''));
    exit;
}
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function takeFlash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
// Guarda o que a pessoa digitou para devolver ao formulário quando a validação falha.
function remember(array $input): void
{
    $_SESSION['old'] = $input;
}
function takeOld(): array
{
    $o = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return is_array($o) ? $o : [];
}
function positiveId(mixed $v): ?int
{
    $n = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $n === false ? null : $n;
}
function money(string $v): ?string
{
    $v = trim(str_replace(',', '.', $v));
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $v))
        return null;
    return number_format((float) $v, 2, '.', '');
}
// Data do <input type="datetime-local"> (2026-12-05T21:00) -> "2026-12-05 21:00:00" ou null.
function localDateTime(string $v): ?string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', trim($v));
    return $d && $d->format('Y-m-d\TH:i') === trim($v) ? $d->format('Y-m-d H:i:s') : null;
}

/* ---------- formatação usada nas views ---------- */

const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
const CORES_AVATAR = ['paprica', 'anil', 'cardamomo', 'canela', 'rosa', 'acafrao'];

// R$ 1.480 / R$ 8,50: os centavos só aparecem quando existem.
function brl(float|string|null $v): string
{
    $v = (float) $v;
    $casas = centavos(abs($v)) % 100 === 0 ? 0 : 2;
    return 'R$ ' . number_format($v, $casas, ',', '.');
}
// Dinheiro em centavos (inteiro): comparar e somar assim evita erro de arredondamento de float.
function centavos(float|string|null $v): int
{
    return (int) round((float) $v * 100);
}
function nota(float|string|null $v): string
{
    return number_format((float) $v, 1, ',', '');
}
function numeroObra(int|string $id): string
{
    return 'Nº ' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}
function numeroLote(int|string $id): string
{
    return 'Lote ' . str_pad((string) $id, 2, '0', STR_PAD_LEFT);
}
function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome)) ?: [''];
    $i = mb_substr($partes[0], 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '');
    return mb_strtoupper($i);
}
function corAvatar(int|string $id): string
{
    return CORES_AVATAR[(int) $id % count(CORES_AVATAR)];
}
// Moldura do quadro escolhida pelo id, para a parede não ficar repetitiva.
function moldura(int|string $id): string
{
    return ['quadro--ouro', '', 'quadro--madeira', 'quadro--fino'][(int) $id % 4];
}
function dataCurta(string $data): string   // 14 out 2026
{
    $t = strtotime($data);
    return date('j', $t) . ' ' . MESES[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}
function dataHora(string $data): string    // 5 dez, 21h  /  5 dez, 21h30
{
    $t = strtotime($data);
    $min = date('i', $t);
    return date('j', $t) . ' ' . MESES[(int) date('n', $t) - 1] . ', ' . date('G', $t) . 'h' . ($min !== '00' ? $min : '');
}
function dataIso(string $data): string     // 2026-12-05T21:00:00-03:00 (lido pelo app.js)
{
    return date('c', strtotime($data));
}
function quando(string $data): string      // hoje, 14:12 / ontem / há 3 dias / 7 nov
{
    $t = strtotime($data);
    $dias = (int) floor((strtotime('today') - strtotime(date('Y-m-d', $t))) / 86400);
    return match (true) {
        $dias <= 0 => 'hoje, ' . date('H:i', $t),
        $dias === 1 => 'ontem, ' . date('H:i', $t),
        $dias < 30 => "há {$dias} dias",
        default => dataCurta($data),
    };
}
