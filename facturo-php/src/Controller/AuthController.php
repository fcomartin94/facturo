<?php
declare(strict_types=1);

namespace Facturo\Controller;

use Facturo\Http\Response;
use Facturo\Service\AuthService;

/**
 * Controlador REST para el registro y autenticación de autónomos.
 * Expone los endpoints públicos POST /api/auth/register y POST /api/auth/login.
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /** POST /api/auth/register — registra un autónomo y devuelve 201 con el JWT inicial. */
    public function register(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->authService->register($data);
        Response::json($result, 201);
    }

    /** POST /api/auth/login — autentica credenciales y devuelve 200 con el JWT. */
    public function login(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->authService->login($data);
        Response::json($result);
    }
}