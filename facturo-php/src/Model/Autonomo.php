<?php
declare(strict_types=1);

namespace Facturo\Model;

/**
 * Modelo inmutable que representa a un autónomo registrado en el sistema.
 * Las propiedades son de solo lectura para garantizar la inmutabilidad
 * tras la hidratación desde la base de datos.
 */
class Autonomo
{
    public function __construct(
        public readonly ?int    $id,
        public readonly string  $email,
        public readonly string  $password,      // almacenado como hash BCrypt
        public readonly string  $nombre,
        public readonly string  $apellidos,
        public readonly string  $nif,
        public readonly ?string $direccion      = null,
        public readonly ?string $codigoPostal   = null,
        public readonly ?string $ciudad         = null,
        public readonly ?string $provincia      = null,
        public readonly ?string $telefono       = null,
        public readonly ?string $creadoEn       = null,
    ) {}

    /**
     * Crea una instancia desde el array asociativo que devuelve PDO::FETCH_ASSOC.
     * Centraliza el mapeo columna_sql -> propiedad_php en un único sitio.
     *
     * @param array<string, mixed> $row Fila de la tabla autonomos.
     * @return self Nueva instancia hidratada.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:             (int)$row['id'],
            email:          $row['email'],
            password:       $row['password'],
            nombre:         $row['nombre'],
            apellidos:      $row['apellidos'],
            nif:            $row['nif'],
            direccion:      $row['direccion']       ?? null,
            codigoPostal:   $row['codigo_postal']   ?? null,
            ciudad:         $row['ciudad']          ?? null,
            provincia:      $row['provincia']       ?? null,
            telefono:       $row['telefono']        ?? null,
            creadoEn:       $row['creado_en']       ?? null,
        );
    }

    /** Exporta el modelo como array para la respuesta JSON (sin el hash de contraseña). */
    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'email'             => $this->email,
            'nombre'            => $this->nombre,
            'apellidos'         => $this->apellidos,
            'nif'               => $this->nif,
            'direccion'         => $this->direccion,
            'codigoPostal'      => $this->codigoPostal,
            'ciudad'            => $this->ciudad,
            'provincia'         => $this->provincia,
            'telefono'          => $this->telefono,
            'creadoEn'          => $this->creadoEn,
        ];
    }
}