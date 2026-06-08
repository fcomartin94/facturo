<?php
declare(strict_types=1);

namespace Facturo\Http;

/**
 * Helpers estáticos para construir respuestas HTTP.
 * Todos los métodos serializan el payload a JSON, establecen las cabeceras
 * apropiadas y llaman a exit() para detener la ejecución del script.
 */
class Response
{
    /**
     * Envía una respuesta JSON con el código HTTP indicado y termina la ejecución.
     * 
     * @param mixed $data       El payload (array, objeto serializable...)
     * @param int   $status     El código HTTP (200, 201, 400, 401, 404, 422, 500...)
     */
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // JSON_UNESCAPED_UNICODE evita que los acentos se conviertan en \uXXXX
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    /** Shorthand para 404 con mensaje estándar. */
    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        self::json(['error' => $message], 404);
    }

    /** Shorthand para 401. */
    public static function unauthorized(string $message = 'No autorizado'): void
    {
        self::json(['error' => $message], 401);
    }

    /** Shorthand para 403. */
    public static function forbidden(string $message = 'Acceso denegado'): void
    {
        self::json(['errors' => $message], 403);
    }

    /** Shorthand para 422 Unprocessable Entity (errores de validación). */
    public static function validationError(array $errors): void
    {
        self::json(['errors' => $errors], 422);
    }

    /** Shorthand para 500. */
    public static function serverError(string $message = 'Error interno del servidor'): void
    {
        self::json(['error' => $message], 500);
    }
}