<?php
declare(strict_types=1);

namespace Facturo\Repository;

use PDO;
use Facturo\Database\Database;
use Facturo\Model\Autonomo;

/**
 * Repositorio de acceso a datos para la entidad Autonomo.
 * Todas las consultas usan sentencias preparadas para prevenir inyección SQL.
 */
class AutonomoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /** Busca por email - usado en el login para comparar credenciales. */
    public function findByEmail(string $email): ?Autonomo
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM autonomos WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? Autonomo::fromRow($row) : null;
    }

    /** Busca por id - usado en JwtMiddleware para verificar que el autónomo sigue activo. */
    public function findById(int $id): ?Autonomo
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM autonomos WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Autonomo::fromRow($row) : null;
    }

    /**
     * Registra un nuevo autónomo.
     * La contraseña llega ya hasheada con password_hash() desde AuthService.
     */
    public function create(Autonomo $autonomo): Autonomo
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO autonomos
             (email, password, nombre, apellidos, nif, direccion, codigo_postal, ciudad, provincia, telefono)
             VALUES (:email, :password, :nombre, :apellidos, :nif,
                     :direccion, :codigoPostal, :ciudad, :provincia, :telefono)
             RETURNING *'
        );
        $stmt->execute([
            'email'        => $autonomo->email,
            'password'     => $autonomo->password,
            'nombre'       => $autonomo->nombre,
            'apellidos'    => $autonomo->apellidos,
            'nif'          => $autonomo->nif,
            'direccion'    => $autonomo->direccion,
            'codigoPostal' => $autonomo->codigoPostal,
            'ciudad'       => $autonomo->ciudad,
            'provincia'    => $autonomo->provincia,
            'telefono'     => $autonomo->telefono,
        ]);
        return Autonomo::fromRow($stmt->fetch());
    }

    /** Comprueba si el email ya está en uso. */
    public function existsByEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM autonomos WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        return (bool)$stmt->fetch();
    }
}