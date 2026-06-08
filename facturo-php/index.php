<?php
declare(strict_types=1);

// Evita que warnings y deprecations contaminen la salida (JSON o binario PDF).
// Los errores fatales se gestionan vía set_exception_handler más abajo.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ── CORS ──────────────────────────────────────────────────────────────────────
// Permite peticiones del frontend cuando se sirve desde un origen diferente.
// En producción reemplaza '*' por el dominio exacto del frontend.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Los navegadores envían una petición OPTIONS (preflight) antes de las reales.
// La respondemos de inmediato con 204 para no continuar con el router.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

use Dotenv\Dotenv;
use Facturo\Security\JwtUtil;
use Facturo\Security\JwtMiddleware;
use Facturo\Http\Response;
use Facturo\Exception\ValidationException;
use Facturo\Exception\BusinessException;
use Facturo\Controller\RootController;
use Facturo\Controller\AuthController;
use Facturo\Controller\ClienteController;
use Facturo\Controller\FacturaController;

// 1. Autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// 2. Cargar variables de entorno desde .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 3. Inicializar utilidades que necesitan config (JwtUtil necesita JWT_SECRET)
JwtUtil::init();

// 4. Parsear la petición entrante
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/') ?: '/';

// 5. Manejador de excepciones global — captura cualquier excepción no gestionada
set_exception_handler(function (Throwable $e) {
    if ($e instanceof ValidationException) {
        Response::json(
            ['errors' => $e->getErrors()],
            $e->getHttpStatusCode()
        );
    } elseif ($e instanceof BusinessException) {
        Response::json(
            ['error' => $e->getMessage()],
            $e->getHttpStatusCode()
        );
    } else {
        // En producción nunca exponer el mensaje interno
        $debug = (($_ENV['APP_ENV'] ?? 'production') === 'development');
        Response::serverError(
            $debug ? $e->getMessage() : 'Error interno del servidor'
        );
    }
});

// 6. Tabla de rutas
// ─── Rutas públicas (sin autenticación) ───────────────────
if ($method === 'GET'  && $uri === '/') {
    (new RootController())->index();
}

if ($method === 'POST' && $uri === '/api/auth/register') {
    (new AuthController())->register();
}

if ($method === 'POST' && $uri === '/api/auth/login') {
    (new AuthController())->login();
}

// ─── Rutas protegidas (requieren JWT válido) ───────────────
// El middleware extrae y verifica el JWT y devuelve el autonomoId
$middleware = new JwtMiddleware();

// Clientes
if ($method === 'GET'    && $uri === '/api/clientes') {
    $autonomoId = $middleware->handle();
    (new ClienteController())->index($autonomoId);
}

if ($method === 'POST'   && $uri === '/api/clientes') {
    $autonomoId = $middleware->handle();
    (new ClienteController())->store($autonomoId);
}

if ($method === 'GET'    && preg_match('#^/api/clientes/(\d+)$#', $uri, $m)) {
    $autonomoId = $middleware->handle();
    (new ClienteController())->show((int)$m[1], $autonomoId);
}

if ($method === 'PUT'    && preg_match('#^/api/clientes/(\d+)$#', $uri, $m)) {
    $autonomoId = $middleware->handle();
    (new ClienteController())->update((int)$m[1], $autonomoId);
}

if ($method === 'DELETE' && preg_match('#^/api/clientes/(\d+)$#', $uri, $m)) {
    $autonomoId = $middleware->handle();
    (new ClienteController())->destroy((int)$m[1], $autonomoId);
}

// Facturas
if ($method === 'GET'    && $uri === '/api/facturas') {
    $autonomoId = $middleware->handle();
    (new FacturaController())->index($autonomoId);
}

if ($method === 'POST'   && $uri === '/api/facturas') {
    $autonomoId = $middleware->handle();
    (new FacturaController())->store($autonomoId);
}

if ($method === 'GET'    && preg_match('#^/api/facturas/(\d+)$#', $uri, $m)) {
    $autonomoId = $middleware->handle();
    (new FacturaController())->show((int)$m[1], $autonomoId);
}

if ($method === 'PATCH'  && preg_match('#^/api/facturas/(\d+)/estado$#', $uri, $m)) {
    $autonomoId = $middleware->handle();
    (new FacturaController())->updateEstado((int)$m[1], $autonomoId);
}

if ($method === 'GET'    && preg_match('#^/api/facturas/(\d+)/pdf$#', $uri, $m)) {
    $autonomoId = $middleware->handle();
    (new FacturaController())->pdf((int)$m[1], $autonomoId);
}

// 7. Si ninguna ruta coincidió, devolver 404
Response::notFound('Ruta no encontrada: ' . $method . ' ' . $uri);