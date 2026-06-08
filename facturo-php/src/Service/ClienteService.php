<?php
declare(strict_types=1);

namespace Facturo\Service;

use Facturo\Model\Cliente;
use Facturo\Repository\ClienteRepository;
use Facturo\Validation\Validator;
use Facturo\Exception\NotFoundException;
use Facturo\Exception\ValidationException;

class ClienteService
{
    private ClienteRepository $clienteRepo;

    public function __construct()
    {
        $this->clienteRepo = new ClienteRepository();
    }

    /**
     * Lista todos los clientes del autónomo como arrays serializables.
     *
     * @param int $autonomoId ID del autónomo autenticado.
     * @return array<int, array<string, mixed>>
     */
    public function getAll(int $autonomoId): array
    {
        return array_map(
            fn(Cliente $c) => $c->toArray(),
            $this->clienteRepo->findAllByAutonomo($autonomoId)
        );
    }

    /**
     * Obtiene un cliente concreto del autónomo.
     *
     * @param int $id         ID del cliente.
     * @param int $autonomoId ID del autónomo autenticado.
     * @return array<string, mixed>
     * @throws NotFoundException Si el cliente no existe o no pertenece al autónomo.
     */
    public function getById(int $id, int $autonomoId): array
    {
        $cliente = $this->clienteRepo->findByIdAndAutonomo($id, $autonomoId);
        if ($cliente === null) {
            throw new NotFoundException('Cliente', $id);
        }
        return $cliente->toArray();
    }

    /**
     * Crea un nuevo cliente validando el payload primero.
     *
     * @param array<string, mixed> $data       Payload del JSON (nombre y nif son obligatorios).
     * @param int                   $autonomoId ID del autónomo autenticado.
     * @return array<string, mixed> El cliente creado.
     * @throws ValidationException Si los datos del cliente no son válidos.
     */
    public function create(array $data, int $autonomoId): array
    {
        (new Validator())
            ->required($data['nombre'] ?? null, 'nombre')
            ->required($data['nif']    ?? null, 'nif')
            ->email($data['email']     ?? '',   'email')
            ->throwIfInvalid();

        $cliente = new Cliente(
            id:           null,
            autonomoId:   $autonomoId,
            nombre:       $data['nombre'],
            nif:          $data['nif'],
            email:        $data['email']        ?? null,
            telefono:     $data['telefono']     ?? null,
            direccion:    $data['direccion']    ?? null,
            codigoPostal: $data['codigoPostal'] ?? null,
            ciudad:       $data['ciudad']       ?? null,
            provincia:    $data['provincia']    ?? null,
            pais:         $data['pais']         ?? 'España',
        );

        return $this->clienteRepo->create($cliente)->toArray();
    }

    /**
     * Actualiza un cliente existente aplicando el patrón fetch → merge → save.
     * Los campos no presentes en $data conservan el valor del registro existente.
     *
     * @param int                   $id         ID del cliente a actualizar.
     * @param array<string, mixed> $data       Campos a modificar.
     * @param int                   $autonomoId ID del autónomo autenticado.
     * @return array<string, mixed> El cliente actualizado.
     * @throws NotFoundException   Si el cliente no existe o no pertenece al autónomo.
     * @throws ValidationException Si los datos enviados no son válidos.
     */
    public function update(int $id, array $data, int $autonomoId): array
    {
        $existing = $this->clienteRepo->findByIdAndAutonomo($id, $autonomoId);
        if ($existing === null) {
            throw new NotFoundException('Cliente', $id);
        }

        (new Validator())
            ->required($data['nombre'] ?? null, 'nombre')
            ->required($data['nif']    ?? null, 'nif')
            ->throwIfInvalid();

        $updated = new Cliente(
            id:           $id,
            autonomoId:   $autonomoId,
            nombre:       $data['nombre'],
            nif:          $data['nif'],
            email:        $data['email']        ?? $existing->email,
            telefono:     $data['telefono']     ?? $existing->telefono,
            direccion:    $data['direccion']    ?? $existing->direccion,
            codigoPostal: $data['codigoPostal'] ?? $existing->codigoPostal,
            ciudad:       $data['ciudad']       ?? $existing->ciudad,
            provincia:    $data['provincia']    ?? $existing->provincia,
            pais:         $data['pais']         ?? $existing->pais,
        );

        return $this->clienteRepo->update($updated)->toArray();
    }

    /**
     * Elimina un cliente del autónomo autenticado.
     *
     * @param int $id         ID del cliente a eliminar.
     * @param int $autonomoId ID del autónomo autenticado.
     * @return void
     * @throws NotFoundException Si el cliente no existe o no pertenece al autónomo.
     */
    public function delete(int $id, int $autonomoId): void
    {
        $deleted = $this->clienteRepo->delete($id, $autonomoId);
        if (!$deleted) {
            throw new NotFoundException('Cliente', $id);
        }
    }
}