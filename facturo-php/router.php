<?php
/**
 * Router para el servidor de desarrollo built-in de PHP.
 *
 * Uso: php -S localhost:8080 router.php
 *
 * Lógica:
 *  - Si la URI apunta a un archivo real en la raíz del proyecto (incluido public/)
 *    devuelve false → el servidor built-in lo sirve directamente.
 *  - En cualquier otro caso delega en index.php (API).
 */

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

require __DIR__ . '/index.php';
