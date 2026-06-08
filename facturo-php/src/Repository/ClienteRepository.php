<?php
declare(strict_types=1);

namespace Facturo\Repository;

use PDO;
use Facturo\Database\Database;
use Facturo\Model\Cliente;

/**
 * Repositorio de acceso a datos para la entidad Cliente.
 * Todas las consultas filtran por autonomo_id para garantizar el aislamiento
 * de datos entre autónomos (prevención de IDOR).
 */
class ClienteRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Lista todos los clientes del autónomo autenticado.
     * 
     * @param int $autonomoId ID del autónomo propietario.
     * @return Cliente[] Lista ordenada alfabéticamente por nombre.
     */
    public function findAllByAutonomo(int $autonomoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clientes WHERE autonomo_id = :aid ORDER BY nombre ASC'
        );
        $stmt->execute(['aid' => $autonomoId]);
        return array_map([Cliente::class, 'fromRow'], $stmt->fetchAll());
    }

    /**
     * Busca un cliente concreto, verificando que pertenece al autónomo (evita IDOR).
     * 
     * @param int $id           ID del cliente a buscar.
     * @param int $autonomoId   ID del autónomo propietario.
     * @return Cliente|null     Cliente encontrado, o null si no existe o no pertenece al autónomo.
     */
    public function findByIdAndAutonomo(int $id, int $autonomoId): ?Cliente
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clientes WHERE id = :id AND autonomo_id = :aid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'aid' => $autonomoId]);
        $row = $stmt->fetch();
        return $row ? Cliente::fromRow($row) : null;
    }

    /**
     * Inserta un nuevo cliente en la base de datos.
     *
     * @param Cliente $cliente Objeto cliente a persistir (id = null).
     * @return Cliente El cliente persistido con id y creado_en asignados por la DB.
     */
    public function create(Cliente $cliente): Cliente
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clientes
             (autonomo_id, nombre, nif, email, telefono, direccion, codigo_postal, ciudad, provincia, pais)
             VALUES (:aid, :nombre, :nif, :email, :telefono, :direccion, :cp, :ciudad, :provincia, :pais)
             RETURNING *'
        );
        $stmt->execute([
            'aid'      => $cliente->autonomoId,
            'nombre'   => $cliente->nombre,
            'nif'      => $cliente->nif,
            'email'    => $cliente->email,
            'telefono' => $cliente->telefono,
            'direccion'=> $cliente->direccion,
            'cp'       => $cliente->codigoPostal,
            'ciudad'   => $cliente->ciudad,
            'provincia'=> $cliente->provincia,
            'pais'     => $cliente->pais,
        ]);
        return Cliente::fromRow($stmt->fetch());
    }

    /**
     * Actualiza los datos de un cliente existente.
     *
     * @param Cliente $cliente Objeto con los datos actualizados (id y autonomoId obligatorios).
     * @return Cliente El cliente actualizado tal como quedó en la DB.
     */
    public function update(Cliente $cliente): Cliente
    {
        $stmt = $this->pdo->prepare(
            'UPDATE clientes
             SET nombre=:nombre, nif=:nif, email=:email, telefono=:telefono,
                 direccion=:direccion, codigo_postal=:cp, ciudad=:ciudad,
                 provincia=:provincia, pais=:pais
             WHERE id=:id AND autonomo_id=:aid
             RETURNING *'
        );
        $stmt->execute([
            'id'       => $cliente->id,
            'aid'      => $cliente->autonomoId,
            'nombre'   => $cliente->nombre,
            'nif'      => $cliente->nif,
            'email'    => $cliente->email,
            'telefono' => $cliente->telefono,
            'direccion'=> $cliente->direccion,
            'cp'       => $cliente->codigoPostal,
            'ciudad'   => $cliente->ciudad,
            'provincia'=> $cliente->provincia,
            'pais'     => $cliente->pais,
        ]);
        return Cliente::fromRow($stmt->fetch());
    }

    /**
     * Elimina un cliente verificando que pertenece al autónomo.
     *
     * @param int $id         ID del cliente a eliminar.
     * @param int $autonomoId ID del autónomo propietario (evita borrar recursos ajenos).
     * @return bool true si se eliminó correctamente, false si no existía o era de otro autónomo.
     */
    public function delete(int $id, int $autonomoId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM clientes WHERE id = :id AND autonomo_id = :aid'
        );
        $stmt->execute(['id' => $id, 'aid' => $autonomoId]);
        return $stmt->rowCount() > 0;
    }
}