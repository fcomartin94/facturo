<?php
declare(strict_types=1);

namespace Facturo\Security;

use Facturo\Http\Response;
use Facturo\Repository\AutonomoRepository;

/**
 * Middleware de autenticación JWT.
 * Extrae y valida el token Bearer del header Authorization antes de
 * ejecutar cualquier endpoint protegido.
 */
class JwtMiddleware
{
    private AutonomoRepository $autonomoRepo;

    public function __construct()
    {
        $this->autonomoRepo = new AutonomoRepository();
    }

    /**
     * Verifica el token JWT del header Authorization: Bearer <token>.
     * Si es válido, retorna el id del autónomo autenticado.
     * Si no, envía 401 y termina la ejecución (exit vía Response::unauthorized).
     *
     * @return int ID del autónomo autenticado, extraído del claim 'sub' del token.
     */
    public function handle(): int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            Response::unauthorized('Token JWT requerido');
        }

        $token     = substr($header, 7);
        $autonomoId = JwtUtil::validate($token);

        if ($autonomoId === null) {
            Response::unauthorized('Token JWT inválido o expirado');
        }

        // Verificación adicional: el autónomo sigue existiendo en la DB
        $autonomo = $this->autonomoRepo->findById($autonomoId);
        if ($autonomo === null) {
            Response::unauthorized('Autónomo no encontrado');
        }

        return $autonomoId;
    }
}