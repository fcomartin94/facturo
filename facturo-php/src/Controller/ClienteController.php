<?php
declare(strict_types=1);

namespace Facturo\Controller;

use Facturo\Http\Response;
use Facturo\Service\ClienteService;

/**
 * Controlador REST para la gestión de clientes del autónomo autenticado.
 * Todos los métodos reciben el autonomoId extraído por JwtMiddleware.
 */
class ClienteController
{
    private ClienteService $clienteService;

    public function __construct()
    {
        $this->clienteService = new ClienteService();
    }

    /** GET /api/clientes — lista todos los clientes del autónomo autenticado. */
    public function index(int $autonomoId): void
    {
        Response::json($this->clienteService->getAll($autonomoId));
    }

    /** GET /api/clientes/{id} — obtiene un cliente concreto verificando pertenencia. */
    public function show(int $id, int $autonomoId): void
    {
        Response::json($this->clienteService->getById($id, $autonomoId));
    }

    /** POST /api/clientes — crea un nuevo cliente y devuelve 201. */
    public function store(int $autonomoId): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->clienteService->create($data, $autonomoId);
        Response::json($result, 201);
    }

    /** PUT /api/clientes/{id} — actualiza un cliente existente del autónomo. */
    public function update(int $id, int $autonomoId): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->clienteService->update($id, $data, $autonomoId);
        Response::json($result);
    }

    /** DELETE /api/clientes/{id} — elimina un cliente del autónomo. */
    public function destroy(int $id, int $autonomoId): void
    {
        $this->clienteService->delete($id, $autonomoId);
        Response::json(['message' => 'Cliente eliminado correctamente']);
    }
}