<?php
declare(strict_types=1);

namespace Facturo\Model;

/**
 * Modelo inmutable que representa la cabecera de una factura.
 * Los importes (baseImponible, cuotaIva, cuotaIrpf, total) se almacenan
 * como strings para preservar la precisión exacta de los tipos NUMERIC de
 * PostgreSQL y permitir operaciones con bcmath sin errores de coma flotante.
 */
class Factura
{
    public function __construct(
        public readonly ?int    $id,
        public readonly int     $autonomoId,
        public readonly int     $clienteId,
        public readonly string  $numeroFactura,
        public readonly string  $fechaEmision,
        public readonly ?string $fechaVencimiento = null,
        public readonly string  $porcentajeIva    = '21.00',
        public readonly string  $porcentajeIrpf   = '15.00',
        public readonly ?string $baseImponible     = null,
        public readonly ?string $cuotaIva          = null,
        public readonly ?string $cuotaIrpf         = null,
        public readonly ?string $total             = null,
        public readonly string  $estado            = 'BORRADOR',
        public readonly ?string $notas             = null,
        public readonly ?string $creadaEn          = null,
    ) {}

    /**
     * Crea una instancia desde el array asociativo que devuelve PDO::FETCH_ASSOC.
     * Los importes llegan como strings desde PostgreSQL NUMERIC — se conservan
     * como strings para que bcmath pueda operar con precisión exacta.
     *
     * @param array<string, mixed> $row Fila de la tabla facturas.
     * @return self Nueva instancia hidratada.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:               (int)$row['id'],
            autonomoId:       (int)$row['autonomo_id'],
            clienteId:        (int)$row['cliente_id'],
            numeroFactura:    $row['numero_factura'],
            fechaEmision:     $row['fecha_emision'],
            fechaVencimiento: $row['fecha_vencimiento'] ?? null,
            porcentajeIva:    $row['porcentaje_iva']    ?? '21.00',
            porcentajeIrpf:   $row['porcentaje_irpf']   ?? '15.00',
            baseImponible:    $row['base_imponible']    ?? null,
            cuotaIva:         $row['cuota_iva']         ?? null,
            cuotaIrpf:        $row['cuota_irpf']        ?? null,
            total:            $row['total']             ?? null,
            estado:           $row['estado']            ?? 'BORRADOR',
            notas:            $row['notas']             ?? null,
            creadaEn:         $row['creada_en']         ?? null,
        );
    }

    /**
     * Exporta el modelo como array para la respuesta JSON.
     * Los importes se devuelven como strings para conservar la precisión numérica.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'autonomoId'       => $this->autonomoId,
            'clienteId'        => $this->clienteId,
            'numeroFactura'    => $this->numeroFactura,
            'fechaEmision'     => $this->fechaEmision,
            'fechaVencimiento' => $this->fechaVencimiento,
            'porcentajeIva'    => $this->porcentajeIva,
            'porcentajeIrpf'   => $this->porcentajeIrpf,
            'baseImponible'    => $this->baseImponible,
            'cuotaIva'         => $this->cuotaIva,
            'cuotaIrpf'        => $this->cuotaIrpf,
            'total'            => $this->total,
            'estado'           => $this->estado,
            'notas'            => $this->notas,
            'creadaEn'         => $this->creadaEn,
        ];
    }
}