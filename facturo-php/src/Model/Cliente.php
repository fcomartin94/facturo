<?php
declare(strict_types=1);

namespace Facturo\Model;

/**
 * Modelo inmutable que representa a un cliente perteneciente a un autónomo.
 * Cada cliente está vinculado a un único autónomo mediante autonomoId,
 * garantizando el aislamiento de datos multi-tenant.
 */
class Cliente
{
    public function __construct(
        public readonly ?int    $id,
        public readonly int     $autonomoId,
        public readonly string  $nombre,
        public readonly string  $nif,
        public readonly ?string $email,
        public readonly ?string $telefono       = null,
        public readonly ?string $direccion      = null,
        public readonly ?string $codigoPostal   = null,
        public readonly ?string $ciudad         = null,
        public readonly ?string $provincia      = null,
        public readonly ?string $pais           = null,
        public readonly ?string $creadoEn       = null,
    ) {}

    /**
     * Crea una instancia desde el array asociativo que devuelve PDO::FETCH_ASSOC.
     *
     * @param array<string, mixed> $row Fila de la tabla clientes.
     * @return self Nueva instancia hidratada.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:             (int)$row['id'],
            autonomoId:     (int)$row['autonomo_id'],
            nombre:         $row['nombre'],
            nif:            $row['nif'],
            email:          $row['email']           ?? null,
            telefono:       $row['telefono']        ?? null,
            direccion:      $row['direccion']       ?? null,
            codigoPostal:   $row['codigo_postal']   ?? null,
            ciudad:         $row['ciudad']          ?? null,
            provincia:      $row['provincia']       ?? null,
            pais:           $row['pais']            ?? 'España',
            creadoEn:       $row['creado_en']       ?? null,
        );
    }

    /**
     * Exporta el modelo como array para la respuesta JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'autonomoId'   => $this->autonomoId,
            'nombre'       => $this->nombre,
            'nif'          => $this->nif,
            'email'        => $this->email,
            'telefono'     => $this->telefono,
            'direccion'    => $this->direccion,
            'codigoPostal' => $this->codigoPostal,
            'ciudad'       => $this->ciudad,
            'provincia'    => $this->provincia,
            'pais'         => $this->pais,
            'creadoEn'     => $this->creadoEn,
        ];
    }
}